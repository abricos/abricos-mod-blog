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
		'toplic': {
			'list': function(){
				return WS;
			},
			'view': function(topicid){
				return WS+'/topiclist/TopicViewWidget/'+topicid+'/';
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
	
	var Topic = function(d){
		d = L.merge({
			'catid': 0,
			'tl': '', 
			'dl': 0, 
			'uid': Brick.env.user.id,
			'cmt': 0,
			'bdlen': 0,
			'intro': ''
		}, d || {});
		
		Topic.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Topic, SysNS.Item, {
		init: function(d){
			
			this.body = null;
			this.user = null;
			
			this.tagList = new TagList();
			Topic.superclass.init.call(this, d);
		},
		update: function(d){
			this.catid = d['catid']*1;				// идентификатор раздела
			this.title = d['tl'];				// заголовок
			this.userid = d['uid'];				// идентификатор автора
			
			// дата публикации
			this.date = d['dl']==0 ? null : new Date(d['dl']*1000);
			this.intro = d['intro'];
			this.isBody = d['bdlen']>0;
			this.commentCount = d['cmt']*1;
			this.contentid = d['ctid']*1;
			
			if (d['isfull']*1 == 1){
				this.body = d['bd'];
			}
		}
	});	
	
	var TopicList = function(d){
		TopicList.superclass.constructor.call(this, d, Topic);
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
			this.topicList = new TopicList();
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
	
	
	var GlobalMenuWidget = function(container, page){
		this.init(container, page);
	};
	GlobalMenuWidget.prototype = {
		init: function(container, page){
			buildTemplate(this, 'gbmenu');
			
			container.innerHTML = this._TM.replace('gbmenu', {
				'topiclist': page == 'topiclist' ? 'current' : '',
				'comments': page == 'comments' ? 'current' : ''
			});
		}
	};
	NS.GlobalMenuWidget = GlobalMenuWidget;
	
};