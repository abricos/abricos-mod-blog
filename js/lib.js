/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: 'uprofile', files: ['users.js']},
        {name: 'widget', files: ['lib.js']},
        {name: 'blog', files: ['roles.js']}
	]		
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		R = NS.roles;
	var SysNS = Brick.mod.sys;
	var UP = Brick.mod.uprofile;

	var buildTemplate = this.buildTemplate;
	buildTemplate({},'');
	
	NS.lif = function(f){return L.isFunction(f) ? f : function(){}; };
	NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
		f = NS.lif(f); f(p1, p2, p3, p4, p5, p6, p7);
	};
	NS.Item = SysNS.Item;
	NS.ItemList = SysNS.ItemList;
	
	var WS = "#app={C#MODNAMEURI}/wspace/ws/";
	
	NS.navigator = {
		'home': function(){ return WS; }, 
		'topic': {
			'list': function(){
				return WS+'topic/TopicListWidget/';
			},
			'view': function(topicid){
				return WS+'topic/TopicViewWidget/'+topicid+'/';
			}
		},
		'about': function(){
			return WS+'about/AboutWidget/';
		},
		'go': function(url){
			Brick.Page.reload(url);
		}
	};		
	
	var Tag = function(d){
		d = L.merge({
			'id': 0,
			'tl': '',
			'nm': ''
		}, d || {});
		Tag.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Tag, SysNS.Item, {
		update: function(d){
			this.title = d['tl'];
			this.name =  d['nm'];
		}
	});	
	NS.Tag = Tag;
	
	var TagList = function(d){
		TagList.superclass.constructor.call(this, d, Tag);
	};
	YAHOO.extend(TagList, SysNS.ItemList, {});
	NS.TagList = TagList;
	
	// Информация о топике
	var TopicInfo = function(d){
		d = L.merge({
			'catid': 0,
			'tl': '',
			'dl': 0, 
			'uid': Brick.env.user.id,
			'cmt': 0,
			'bdlen': 0,
			'intro': '',
			'tags': []
		}, d || {});
		
		TopicInfo.superclass.constructor.call(this, d);
	};
	YAHOO.extend(TopicInfo, SysNS.Item, {
		init: function(d){
			
			this.tagList = new TagList();
			this.type = 'info';
			
			TopicInfo.superclass.init.call(this, d);
		},
		update: function(d){
			this.catid = d['catid']*1;				// идентификатор раздела
			this.title = d['tl'];				// заголовок
			this.userid = d['uid'];				// идентификатор автора

			UP.viewer.users.update([d['user']]);
			
			// дата публикации
			this.date = d['dl']==0 ? null : new Date(d['dl']*1000);
			this.intro = d['intro'];
			this.commentCount = d['cmt']*1;
			this.contentid = d['ctid']*1;
			
			this.tagList.update(d['tags']);

			this.isBody = d['bdlen']>0;
		}
	});
	
	var Topic = function(d){
		d = L.merge({
			'bd': ''
		}, d || {});
		
		Topic.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Topic, TopicInfo, {
		init: function(d){
			this.type = 'full';
			Topic.superclass.init.call(this, d);
		},
		update: function(d){
			Topic.superclass.update.call(this, d);
			this.body = d['bd'];
		}
	});
	NS.Topic = Topic;
	
	var TopicList = function(d){
		TopicList.superclass.constructor.call(this, d, TopicInfo);
	};
	YAHOO.extend(TopicList, SysNS.ItemList, {});
	NS.TopicList = TopicList;	
	
	var Category = function(d){
		d = L.merge({
			'tl': '',
			'nm': ''
		}, d || {});
		Category.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Category, SysNS.Item, {
		init: function(d){
			Category.superclass.init.call(this, d);
		},
		update: function(d){
			this.title = d['tl'];
			this.name = d['nm'];
		}
	});		
	NS.Category = Category;
	
	var CategoryList = function(d){
		CategoryList.superclass.constructor.call(this, d, Category);
	};
	YAHOO.extend(CategoryList, SysNS.ItemList, {
		getByName: function(catName){
			var category = null;
			
			this.foreach(function(cat){
				if (cat.name == catName){
					category = cat;
					return true;
				}
			});
			
			return category;
		}
	});
	NS.CategoryList = CategoryList;
	
	/*
	var BlogManager = function(data){
		data = L.merge({
			'categories': [],
			'topics': [],
			'users': []
		}, data || {});
		this.init(data);
	};
	BlogManager.prototype = {
		init: function(data){
			NS.blogManager = this;
			
			this._loadPages = {};
			
			// список категорий
			this.categoryList = new CategoryList(data['categories']);
			this.tagList = new TagList();
			this.topicList = new TopicList();
			this.users = Brick.mod.uprofile.viewer.users;

			this.updateData(data);
		},
		updateData: function(data){ // обновить данные
			this.users.update(data['users']);
			
			this.updateTagList(data['tags']);

			// общий список записей в блоге
			this.updateTopicList(data['topics']);
			
			this.updateTopicTag(data['toptags'])
		},
		updateTagList: function(d){
			d = d || [];
			
			for (var i=0;i<d.length;i++){
				this.tagList.add(new Tag(d[i]));
			}
		},
		updateTopicList: function(d){
			d = d || [];
			
			var cats = this.categoryList;
			
			for (var i=0;i<d.length;i++){
				
				var di = d[i], 
					id = di['id']*1,
					topic = this.topicList.get(id);
				
				if (!L.isNull(topic)){
					topic.update(di);
				} else {
					topic = new Topic(di);
					var category = cats.get(topic.catid);
					
					if (!L.isNull(category)){
						category.topicList.add(topic);
					}
					this.topicList.add(topic);
					
					topic.user = this.users.get(topic.userid);
				}
			}
		},
		updateTopicTag: function(d){
			d = d || [];
			
			for (var i=0;i<d.length;i++){
				
				var di = d[i],
					topic = this.topicList.get(di['tid']),
					tag = this.tagList.get(di['gid']);

				if (!L.isNull(topic) && !L.isNull(tag)){
					topic.tagList.add(tag);
				}
			}
			
		},
		foreach: function (f, catid){
			var tpList = this.topicList;
			if (catid > 0){
				var cat = this.categoryList.get(catid);
				if (!L.isNull(cat)){
					tpList = cat.topicList;
				}
			}
			tpList.foreach(f);
		},
		loadPage: function(catid, inc, callback){ // inc>0 подгрузить на одну страницу больше
			catid = catid || 0;	
			inc = inc || 0;
			
			callback = L.isFunction(callback) ? callback : function(){};
			callback();
		},
		topicLoad: function(topicid, callback){
			var __self = this;
			Brick.ajax('blog', {
				'data': {
					'do': 'boardtopic',
					'topicid': topicid
				},
				'event': function(request){
					__self.updateData(request.data);
					if (L.isFunction(callback)){
						var topic = __self.topicList.get(topicid);
						callback(topic);
					}
				}
			});
		}
	};
	NS.BlogManager = BlogManager;
	NS.blogManager = null;
	
	NS.buildBlogManager = function(callback){
		if (!L.isNull(NS.blogManager)){
			callback(NS.blogManager);
			return;
		}
		R.load(function(){
			Brick.ajax('blog', {
				'data': {'do': 'boardinit'},
				'event': function(request){
					NS.blogManager = new BlogManager(request.data);
					callback(NS.blogManager);
				}
			});
		});
	};
	/**/

	var Manager = function (callback){
		this.init(callback);
	};
	Manager.prototype = {
		init: function(callback){
			NS.manager = this;
			
			this.users = Brick.mod.uprofile.viewer.users;
			this.categoryList = new NS.CategoryList();
			
			var __self = this;
			R.load(function(){
				__self.categoryListLoad(function(){
					NS.life(callback, __self);
				});
			});
		},
		ajax: function(data, callback){
			data = data || {};

			Brick.ajax('{C#MODNAME}', {
				'data': data,
				'event': function(request){
					NS.life(callback, request.data);
				}
			});
		},
		categoryListLoad: function(callback){
			var __self = this;
			this.ajax({'do': 'categorylist'}, function(d){
				var list = __self.categoryList;
				
				if (!L.isNull(d) && !L.isNull(d['categories'])){
					list.clear();
					list.update(d['categories']);
				}
				
				NS.life(callback, list);
			});			
		},
		topicListLoad: function(callback, cfg){
			cfg = L.merge({
				'catid': 0,
				'page': 1
			}, cfg || {});
			
			cfg['do'] = 'topiclist';
			var __self = this;
			this.ajax(cfg, function(d){
				var list = null;
				
				if (!L.isNull(d)){
					if (!L.isNull(d['users'])){
						__self.users.update(d['users']);
					}
					if (!L.isNull(d['topics'])){
						list = new NS.TopicList(d['topics']);
					}
				}
				
				NS.life(callback, list);
			});
		},
		topicLoad: function(topicid, callback){
			
			this.ajax({
				'do': 'topic',
				'topicid': topicid
			}, function(d){
				var topic = null;
				
				if (!L.isNull(d) && !L.isNull(d['topic'])){
					topic = new NS.Topic(d['topic']);
				}
				
				NS.life(callback, topic);
			});
		},
		commentLiveListLoad: function(callback){
			this.ajax({
				'do': 'commentlivelist'
			}, function(d){
				Brick.console(d);
				var list = null;
				
				if (!L.isNull(d) && !L.isNull(d['comments'])){
					// list = new NS.CommentLiveList(d['comments']);
				}
				
				NS.life(callback, list);
			});
		}
	};
	NS.manager = null;
	
	NS.initManager = function(callback){
		if (L.isNull(NS.manager)){
			NS.manager = new Manager(callback);
		}else{
			NS.life(callback, NS.manager);
		}
	};
	
	
	
};