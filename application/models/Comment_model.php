<?php

/**
 * Created by PhpStorm.
 * User: mr.incognito
 * Date: 27.01.2020
 * Time: 10:10
 */
class Comment_model extends CI_Emerald_Model
{
    const CLASS_TABLE = 'comment';


    /** @var int */
    protected $user_id;
    /** @var int */
    protected $assign_id;
    /** @var string */
    protected $text;
    /** @var int */
    protected $level;

    /** @var string */
    protected $time_created;
    /** @var string */
    protected $time_updated;

    // generated
    protected $comments;
    protected $likes;
    protected $user;


    /**
     * @return int
     */
    public function get_user_id(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     *
     * @return bool
     */
    public function set_user_id(int $user_id)
    {
        $this->user_id = $user_id;
        return $this->save('user_id', $user_id);
    }

    /**
     * @return int
     */
    public function get_assign_id(): int
    {
        return $this->assign_id;
    }

    /**
     * @param int $assign_id
     *
     * @return bool
     */
    public function set_assign_id(int $assign_id)
    {
        $this->assign_id = $assign_id;
        return $this->save('assign_id', $assign_id);
    }


    /**
     * @return int
     */
    public function get_level(): int
    {
        return $this->level;
    }

    /**
     * @return string
     */
    public function get_text(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return bool
     */
    public function set_text(string $text)
    {
        $this->text = $text;
        return $this->save('text', $text);
    }


    /**
     * @return string
     */
    public function get_time_created(): string
    {
        return $this->time_created;
    }

    /**
     * @param string $time_created
     *
     * @return bool
     */
    public function set_time_created(string $time_created)
    {
        $this->time_created = $time_created;
        return $this->save('time_created', $time_created);
    }

    /**
     * @return string
     */
    public function get_time_updated(): string
    {
        return $this->time_updated;
    }

    /**
     * @param string $time_updated
     *
     * @return bool
     */
    public function set_time_updated(int $time_updated)
    {
        $this->time_updated = $time_updated;
        return $this->save('time_updated', $time_updated);
    }

    // generated

    /**
     * @return mixed
     */
    public function get_likes()
    {
        return $this->likes;
    }

    /**
     * @return mixed
     */
    public function get_comments()
    {
        return $this->comments;
    }

    /**
     * @return User_model
     */
    public function get_user():User_model
    {
        if (empty($this->user))
        {
            try {
                $this->user = new User_model($this->get_user_id());
            } catch (Exception $exception)
            {
                $this->user = new User_model();
            }
        }
        return $this->user;
    }

    function __construct($id = NULL)
    {
        parent::__construct();

        App::get_ci()->load->model('User_model');


        $this->set_id($id);
    }

    public function reload(bool $for_update = FALSE)
    {
        parent::reload($for_update);

        return $this;
    }

    public static function create(array $data)
    {
        $root = self::getRoot($data);
        return self::createAnswer($root['id'], $data);
    }

    private static function addRoot(array $data)
    {
        $s = App::get_ci()->s;

        $root = $data;
        $root['lft'] = 1;
        $root['text'] = 'Root';
        $root['rgt'] = 2;
        $root['level'] = 0;
        $s->from(self::CLASS_TABLE)
            ->insert($root)
            ->execute();
        $root['id'] = $s->get_insert_id();
        return $root;
    }

    private static function getRoot(array $data)
    {
        $s = App::get_ci()->s;
        $root = $s->from(self::CLASS_TABLE)
            ->where([
                'assign_id' => $data['assign_id'],
                'level' => 0,
            ])
            ->one();
        if ($root) {
            return $root;
        }
        return self::addRoot($data);
    }

    public static function createAnswer($id, array $data)
    {
        $s = App::get_ci()->s;
        $parentComment = $s->from(self::CLASS_TABLE)->where([
            'id' => $id,
        ])->one();
        if (!$parentComment) {
            throw new Exception('Don\'t find source');
        }

        $s
            ->from(self::CLASS_TABLE)
            ->where([
                'assign_id' => $parentComment['assign_id'],
                'lft >' => $parentComment['rgt']
            ])
            ->update('lft = rgt + 1')
            ->execute();

        $s
            ->from(self::CLASS_TABLE)
            ->where([
                'assign_id' => $parentComment['assign_id'],
                'rgt >=' => $parentComment['rgt'],
            ])
            ->update('rgt = rgt + 2')
            ->execute();

        $data['lft'] = $parentComment['rgt'];
        $data['rgt'] = $parentComment['rgt'] + 1;
        $data['level'] = $parentComment['level'] + 1;
        $s
            ->from(self::CLASS_TABLE)
            ->insert($data)
            ->execute();

        return new static($s->get_insert_id());
    }

    public function delete()
    {
        $this->is_loaded(TRUE);

        $s = App::get_ci()->s;

        $targetComment = $s->from(self::CLASS_TABLE)->where([
            'id' => $this->get_id(),
        ])->one();
        if (!$targetComment) {
            throw new Exception('Don\'t find source');
        }

        $s
            ->from(self::CLASS_TABLE)
            ->where(['assign_id' => $targetComment['assign_id']])
            ->between(' AND lft', $targetComment['lft'], $targetComment['rgt'])
            ->delete()
            ->execute();
        $affected_rows = $s->get_affected_rows();

        $width = $targetComment['rgt'] - $targetComment['lft'] + 1;
        $s
            ->from(self::CLASS_TABLE)
            ->where([
                'assign_id' => $targetComment['assign_id'],
                'rgt >' => $targetComment['rgt']
            ])
            ->update("rgt = rgt - {$width}")
            ->execute();

        $s
            ->from(self::CLASS_TABLE)
            ->where([
                'assign_id' => $targetComment['assign_id'],
                'lft >' => $targetComment['rgt']
            ])
            ->update("lft = lft - {$width}")
            ->execute();

        return ($affected_rows > 0);
    }

    /**
     * @param int $assting_id
     * @return self[]
     * @throws Exception
     */
    public static function get_all_by_assign_id(int $assting_id)
    {
        $tableName = self::CLASS_TABLE;
        $data = App::get_ci()->s
            ->from("{$tableName} AS node, {$tableName} AS parent")
            ->where([
                'parent.assign_id' => $assting_id,
                'node.assign_id' => $assting_id,
                'node.level >' => 0,
                'parent.level' => 0,
                ' AND node.lft>=parent.lft' => false,
                ' AND node.lft<=parent.rgt' => false,
            ])
            ->orderBy('node.lft','ASC')
            ->select('node.*')
            ->many();
        $ret = [];
        foreach ($data as $i)
        {
            $ret[] = (new self())->set($i);
        }
        return $ret;
    }

    /**
     * @param self|self[] $data
     * @param string $preparation
     * @return stdClass|stdClass[]
     * @throws Exception
     */
    public static function preparation($data, $preparation = 'default')
    {
        switch ($preparation)
        {
            case 'full_info':
                return self::_preparation_full_info($data);
            default:
                throw new Exception('undefined preparation type');
        }
    }


    /**
     * @param self[] $data
     * @return stdClass[]
     */
    private static function _preparation_full_info($data)
    {
        $ret = [];

        foreach ($data as $d){
            $o = new stdClass();

            $o->id = $d->get_id();
            $o->text = $d->get_text();

            $o->user = User_model::preparation($d->get_user(),'main_page');

            $o->likes = rand(0, 25);
            $o->level = $d->get_level();

            $o->time_created = $d->get_time_created();
            $o->time_updated = $d->get_time_updated();

            $ret[] = $o;
        }


        return $ret;
    }


}
