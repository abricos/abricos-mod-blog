/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: 'uprofile', files: ['users.js']},
        {name: 'widget', files: ['lib.js']},
        {name: '{C#MODNAME}', files: ['roles.js']}
	]		
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang,
		R = NS.roles;
	var SysNS = Brick.mod.sys;
	var UP = Brick.mod.uprofile;
	var LNG = this.language;

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
		'category': {
			'list': function(){
				return WS+'category/CategoryListWidget/';
			},
			'view': function(catid){
				return WS+'category/CategoryViewWidget/'+catid+'/';
			}
		},
		'write': {
			'view': function(){
				return WS+'write/WriteWidget/';
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
		}, 
		toAJAX: function(){
			return {
				'tl': this.title
			};
		}
	});	
	NS.Tag = Tag;
	
	var TagList = function(d){
		TagList.superclass.constructor.call(this, d, Tag);
	};
	YAHOO.extend(TagList, SysNS.ItemList, {});
	TagList.stringToAJAX = function(s){
		var ret = [];
		if (!L.isString(s)){
			return ret;
		}
		var a = s.replace(/\  /g, ' ').split(',');
		for (var i=0;i<a.length;i++){
			ret[ret.length] = {
				'tl': a[i]
			};
		}
		return ret;
	};
	NS.TagList = TagList;
	
	// Информация о топике
	var TopicInfo = function(d){
		d = L.merge({
			'catid': 0,
			'tl': '',
			'dl': 0, 
			'uid': Brick.env.user.id,
			'user': null,
			'cmt': 0,
			'bdlen': 0,
			'intro': '',
			'tags': []
		}, d || {});
		
		TopicInfo.superclass.constructor.call(this, d);
	};
	YAHOO.extend(TopicInfo, SysNS.Item, {
		init: function(d){
			this.type = 'info';
			
			this.tagList = new TagList();
			this.user = null;
			this.category = null;
			
			TopicInfo.superclass.init.call(this, d);
		},
		update: function(d){
			this.title = d['tl'];				// заголовок
			var userid = d['uid'];				// идентификатор автора

			if (!L.isNull(d['user'])){
				UP.viewer.users.update([d['user']]);
			}
			
			// дата публикации
			this.date = d['dl']==0 ? null : new Date(d['dl']*1000);
			this.intro = d['intro'];
			this.commentCount = d['cmt']*1;
			this.contentid = d['ctid']*1;
			
			this.tagList.update(d['tags']);

			this.isBody = d['bdlen']>0;
			
			this.user = UP.viewer.users.get(userid);
			
			var cat = null, catid = d['catid']*1;
			if (catid == 0){ // персональный блог
				cat = new CategoryPerson(this.user.id);
				
			}else{
				cat = NS.manager.categoryList.get(catid);
			}
			this.category = cat;
		},
		url: function(){
			return NS.navigator.topic.view(this.id);
		}
	});
	NS.TopicInfo = TopicInfo;
	
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
		update: function(d){
			this.title = d['tl'];
			this.name = d['nm'];
		},
		url: function(){
			return NS.navigator.category.view(this.id);
		}
	});		
	NS.Category = Category;
	
	var CategoryPerson = function(userid){ // персональный блог
		var user = UP.viewer.users.get(userid);
		var d = L.merge({
			'id': 0,
			'tl': LNG['category']['my'].replace('{v#unm}', user.userName),
			'nm': user.userName
		}, d || {});
		CategoryPerson.superclass.constructor.call(this, d);
	};
	YAHOO.extend(CategoryPerson, Category, {
		update: function(d){
			this.title = d['tl'];
			this.name = d['nm'];
		},
		url: function(){
			return NS.navigator.category.view(this.id);
		}
	});		
	NS.CategoryPerson = CategoryPerson;
	
	
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
	
	var CommentLive = function(d){
		d = L.merge({
			'dl': '',
			'bd': ''
		}, d || {});
		CommentLive.superclass.constructor.call(this, d);
	};
	YAHOO.extend(CommentLive, SysNS.Item, {
		init: function(d){
			this.topic = null;
			this.user = null;
			
			CommentLive.superclass.init.call(this, d);
		},		
		update: function(d){
			this.date = d['dl']==0 ? null : new Date(d['dl']*1000);
			this.body = d['bd'];
			
			if (L.isNull(this.topic)){
				this.topic = new TopicInfo(d['topic']);
			}else{
				this.topic.update(d['topic']);
			}
			UP.viewer.users.update([d['user']]);
			this.user = UP.viewer.users.get(d['user'].id);
		}
	});		
	NS.CommentLive = CommentLive;
	
	var CommentLiveList = function(d){
		CommentLiveList.superclass.constructor.call(this, d, CommentLive);
	};
	YAHOO.extend(CommentLiveList, SysNS.ItemList, { });
	NS.CommentLiveList = CommentLiveList;	
	
	
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
				'page': 1,
				'limit': 15
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
				var list = null;
				
				if (!L.isNull(d) && !L.isNull(d['comments'])){
					list = new NS.CommentLiveList(d['comments']);
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