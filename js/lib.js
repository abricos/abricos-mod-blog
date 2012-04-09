/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2008 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: 'uprofile', files: ['users.js']},
	    {name: 'social', files: ['lib.js']},
        {name: 'blog', files: ['roles.js']}
	]		
};
Component.entryPoint = function(){

	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		TMG = this.template,
		NS = this.namespace,
		R = NS.roles,
		SC = Brick.mod.social;

	Brick.util.CSS.update(Brick.util.CSS['blog']['lib']);
	delete Brick.util.CSS['blog']['lib'];

	var buildTemplate = function(w, ts){w._TM = TMG.build(ts); w._T = w._TM.data; w._TId = w._TM.idManager;};
	
	var Tag = function(d){
		d = L.merge({
			'id': 0,
			'tl': '',
			'nm': ''
		}, d || {});
		this.init(d);
	};
	Tag.prototype = {
		init: function(d){
			this.id = d['id']*1;
			this.title = d['tl'];
		}
	};
	NS.Tag = Tag;
	
	var TagList = function(){ TagList.superclass.constructor.call(this); };
	YAHOO.extend(TagList, SC.SocialItemList, {});
	NS.TagList = TagList;
	
	var Topic = function(d){
		d = L.merge({
			'id': 0,
			'catid': 0,
			'tl': '', 
			'dl': 0, 
			'uid': Brick.env.user.id,
			'cmt': 0,
			'bdlen': 0,
			'intro': ''
		}, d || {});
		
		this.init(d);
	};
	Topic.prototype = {
		init: function(d){
			
			this.body = null;
			this.user = null;
			
			this.update(d);
			
			this.tagList = new TagList();
		},
		update: function(d){
			this.id = d['id']*1;				// идентификатор темы
			this.catid = d['catid']*1;				// идентификатор раздела
			this.title = d['tl'];				// заголовок
			this.userid = d['uid'];				// идентификатор автора
			this.date = SC.dateToClient(d['dl']); // дата публикации
			this.intro = d['intro'];
			this.isBody = d['bdlen']>0;
			this.commentCount = d['cmt']*1;
			this.contentid = d['ctid']*1;
			
			if (d['isfull']*1 == 1){
				this.body = d['bd'];
			}
		}
	};
	NS.Topic = Topic;
	
	var TopicList = function(){
		TopicList.superclass.constructor.call(this);
	};
	YAHOO.extend(TopicList, SC.SocialItemList, {});
	NS.TopicList = TopicList;

	var Category = function(d){
		d = L.merge({
			'id': 0,
			'tl': '',
			'nm': ''
		}, d || {});
		this.init(d);
	};
	Category.prototype = {
		init: function(d){
			this.id = d['id']*1;
			this.title = d['tl'];
			this.name = d['nm'];
			
			this.topicList = new TopicList();
		}
	};
	NS.Category = Category;
	
	var CategoryList = function(d){
		d = d || [];
		CategoryList.superclass.constructor.call(this, d);
	};
	YAHOO.extend(CategoryList, SC.SocialItemList, {
		init: function(d){
			SC.SocialItemList.superclass.init.call(this, d);
			
			for (var i=0;i<d.length;i++){
				var cat = new Category(d[i]);
				this.add(cat);
			}
		},
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
			this.users = new SC.UserList();
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