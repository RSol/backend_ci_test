var app = new Vue({
	el: '#app',
	data: {
		login: '',
		pass: '',
		post: false,
		invalidLogin: false,
		invalidPass: false,
		invalidSum: false,
		posts: [],
		addSum: 0,
		amount: 0,
		likes: 0,
		commentText: '',
		packs: [
			{
				id: 1,
				price: 5
			},
			{
				id: 2,
				price: 20
			},
			{
				id: 3,
				price: 50
			},
		],
		answerText: '',
		answerId: 0,
	},
	computed: {
		test: function () {
			var data = [];
			return data;
		}
	},
	created(){
		var self = this
		axios
			.get('/main_page/get_all_posts')
			.then(function (response) {
				self.posts = response.data.posts;
			})
	},
	methods: {
		logout: function () {
			console.log ('logout');
		},
		logIn: function () {
			var self= this;
			if(self.login === ''){
				self.invalidLogin = true
			}
			else if(self.pass === ''){
				self.invalidLogin = false
				self.invalidPass = true
			}
			else{
				self.invalidLogin = false
				self.invalidPass = false
				axios.post('/main_page/login', {
					login: self.login,
					password: self.pass
				})
					.then(function (response) {
						setTimeout(function () {
							$('#loginModal').modal('hide');
						}, 500);
					})
			}
		},
		fiilIn: function () {
			var self= this;
			if(self.addSum === 0){
				self.invalidSum = true
			}
			else{
				self.invalidSum = false
				axios.post('/main_page/add_money', {
					sum: self.addSum,
				})
					.then(function (response) {
						setTimeout(function () {
							$('#addModal').modal('hide');
						}, 500);
					})
			}
		},
		openPost: function (id) {
			var self= this;
			axios
				.get('/main_page/get_post/' + id)
				.then(function (response) {
					self.post = response.data.post;
					if(self.post){
						setTimeout(function () {
							$('#postModal').modal('show');
						}, 500);
					}
				})
		},
		addLike: function (id) {
			var self= this;
			axios
				.get('/main_page/like')
				.then(function (response) {
					self.likes = response.data.likes;
				})

		},
		buyPack: function (id) {
			var self= this;
			axios.post('/main_page/buy_boosterpack', {
				id: id,
			})
				.then(function (response) {
					self.amount = response.data.amount
					if(self.amount !== 0){
						setTimeout(function () {
							$('#amountModal').modal('show');
						}, 500);
					}
				})
		},
		addComment() {
			if (this.commentText) {
				var self = this;
				var bodyFormData = new FormData();
				bodyFormData.set('message', this.commentText);
				axios.post('/main_page/comment/' + this.post.id, bodyFormData)
					.then(function (response) {
						self.post = response.data.post;
						self.commentText = '';
					})
			}
		},
		showAnswer(id) {
			if (id === this.answerId) {
				this.answerId = 0;
			} else {
				this.answerText = '';
				this.answerId = id;
			}
		},
		addAnswer() {
			if (this.answerText) {
				var self = this;
				var bodyFormData = new FormData();
				bodyFormData.set('message', this.answerText);
				axios.post('/main_page/comment_answer/' + this.answerId, bodyFormData)
					.then(function (response) {
						self.post = response.data.post;
						self.answerText = '';
						self.answerId = 0;
					})
			}
		},
		deleteAnswer(id) {
			var self = this;
			axios.delete('/main_page/comment_delete/' + id)
				.then(function (response) {
					self.post = response.data.post;
				});
		},
		dashes: function (level) {
			var str = '';
			for(var i = 1; i < level; i++) {
				str += '-';
			}
			return str;
		},
	}
});

