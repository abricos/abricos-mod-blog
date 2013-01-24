/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['container.js']},
        {name: 'uprofile', files: ['viewer.js']},
        {name: 'blog', files: ['taglist.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var buildTemplate = this.buildTemplate;
	
	
	var TopicWidget = function(container, topic, cfg){
		cfg = L.merge({
			'fullview': false
		}, cfg || {});
		TopicWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'topic' 
		}, topic, cfg);
	};
	YAHOO.extend(TopicWidget, Brick.mod.widget.Widget, {
		init: function(topic, cfg){
			this.topic = topic;
		},
		buildTData: function(topic, cfg){
			var cat = NS.blogManager.categoryList.get(topic.catid),
				user = topic.user;
			
			var r = {
				'id': topic.id,
				'tl': topic.title,
				'catname': cat.name,
				'cattl': cat.title,
				'dispfull': topic.isBody ? '' : 'none', 
				'date': Brick.dateExt.convert(topic.date),
				'uid': user.id,
				'unm': user.getUserName(),
				'cmt': topic.commentCount,
				'intro': topic.intro,
				'body': ''
			};
			
			if (cfg.fullview){
				r['dispfull'] = 'none';
				r['body'] = topic.body;
			}
			
			return r;
		},
		destroy: function(){
			this.tagsWidget.destroy();
		},
		onLoad: function(topic, cfg){
			
			var __self = this;
			
			this.tagsWidget = new NS.TopicTagListWidget(this.gel('tags'), topic);
			
			if (cfg.fullview){
				// Инициализировать менеджер комментариев
				Brick.ff('comment', 'comment', function(){
					Brick.mod.comment.API.buildCommentTree({
						'container': __self.gel('comments'),
						'dbContentId': topic.contentid,
						'config': {
							'onLoadComments': function(){
								// aTargetBlank(TM.getEl('panel.drawbody'));
								// aTargetBlank(TM.getEl('panel.comments'));
							}
							// ,
							// 'readOnly': project.w*1 == 0,
							// 'manBlock': L.isFunction(config['buildManBlock']) ? config.buildManBlock() : null
						},
						'instanceCallback': function(b){ }
					});
				});
			}
		}
	});
	NS.TopicWidget = TopicWidget;

	var TopicInfoLineWidget = function(container, topic, cfg){
		
		TopicInfoLineWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'info' 
		}, topic);
	};
	YAHOO.extend(TopicInfoLineWidget, Brick.mod.widget.Widget, {
		init: function(topic){
			this.topic = topic;
		},
		buildTData: function(topic){
			var user = NS.manager.users.get(topic.userid);
			return {
				'date': Brick.dateExt.convert(topic.date),
				'uid': topic.userid,
				'avatar': user.avatar24(),
				'unm': user.getUserName(),
				'cmt': topic.commentCount
			};
		}		
	});
	NS.TopicInfoLineWidget = TopicInfoLineWidget;

	
	var TagListWidget = function(container, list){
		TagListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'taglist,tagrow,tagrowcm' 
		}, list);
	};
	YAHOO.extend(TagListWidget, Brick.mod.widget.Widget, {
		init: function(list){
			this.list = list;
		},
		onLoad: function(list){
			var TM = this._TM, alst = [];
			list.foreach(function(tag){
				alst[alst.length] = TM.replace('tagrow', {
					'tl': tag.title
				});
			});
			
			this.elSetHTML('list', alst.join(TM.replace('tagrowcm')));
		}
	});
	NS.TagListWidget = TagListWidget;

	
	var TopicRowWidget = function(container, topic){
		TopicRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, topic);
	};
	YAHOO.extend(TopicRowWidget, Brick.mod.widget.Widget, {
		init: function(topic){
			this.topic = topic;
		},
		destroy: function(){
			this.infoWidget.destroy();
		},
		onLoad: function(topic){
			this.tagsWidget = new NS.TagListWidget(this.gel('taglist'), topic.tagList);
			this.infoWidget = new NS.TopicInfoLineWidget(this.gel('info'), topic);
			
			var cat = NS.manager.categoryList.get(topic.catid);

			this.elSetHTML({
				'intro': topic.intro,
				'tl': topic.title,
				'cattl': !L.isNull(cat) ? cat.title : ''
			});
		}
	});
	NS.TopicRowWidget = TopicRowWidget;
	
	
	var TopicListWidget = function(container, catid){
		TopicListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, catid || 0);
	};
	YAHOO.extend(TopicListWidget, Brick.mod.widget.Widget, {
		init: function(catid){
			this.catid = 0;
			this.wsList = [];
		},
		onLoad: function(catid){
			var __self = this;
			NS.initManager(function(){
				NS.manager.topicListLoad(function(list){
					__self.renderList(list);
				});
			});
		},
		destroy: function(){
			this.clearList();
		},
		clearList: function(){
			var ws = this.wsList;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this.elSetHTML('list', '');
		},
		
		renderList: function(list){
			this.clearList();
			this.elHide('loading');
			
			var elList = this.gel('list');
			var ws = this.wsList;

			list.foreach(function(topic){
				var div = document.createElement('div');
				elList.appendChild(div);
				ws[ws.length] = new NS.TopicRowWidget(div, topic);
			});
		}
		/*
		loadPage: function(catid, inc){
			this.catid = catid = catid || 0; 
			inc = inc || 0;
			
			var __self = this;
			
			this.elShow('loading');
			NS.blogManager.loadPage(catid, inc, function(){
				__self.renderList();
			});
		},
		renderList: function(){
			this.clear();
			this.elHide('loading');
			
			var TM = this._TM, TId = this._TId,
				lst = "";
			
			NS.blogManager.foreach(function(top){
				lst += TM.replace('row', {'id': top.id});
			}, this.catid);
			this.elSetHTML('list', lst);

			var topics = [];
			NS.blogManager.foreach(function(top){
				var el = Dom.get(TId['row']['id']+'-'+top.id);
				topics[topics.length] = new NS.TopicWidget(el, top);
			});
			this.topics = topics;
		}
		/**/
	});
	NS.TopicListWidget = TopicListWidget;

};