/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2008 Abricos All rights reserved.
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
Component.entryPoint = function(){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var NS = this.namespace, 
		TMG = this.template,
		API = NS.API,
		R = NS.roles;

	var initCSS = false,
		buildTemplate = function(w, ts){
		if (!initCSS){
			Brick.util.CSS.update(Brick.util.CSS['blog']['topiclist']);
			delete Brick.util.CSS['blog']['topiclist'];
			initCSS = true;
		}
		w._TM = TMG.build(ts); w._T = w._TM.data; w._TId = w._TM.idManager;
	};
	
	var TopicWidget = function(container, topic, cfg){
		cfg = L.merge({
			'fullview': false
		}, cfg || {});
		this.init(container, topic, cfg);
	};
	TopicWidget.prototype = {
		init: function(container, topic, cfg){
			this.topic = topic;
			
			var cat = NS.blogManager.categoryList.get(topic.catid);
			buildTemplate(this, 'topic');
			var TM = this._TM,
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
			
			container.innerHTML = TM.replace('topic', r);
			
			this.tagsWidget = new NS.TopicTagListWidget(TM.getEl('topic.tags'), topic);
			
			if (cfg.fullview){
				// Инициализировать менеджер комментариев
				Brick.ff('comment', 'comment', function(){
					Brick.mod.comment.API.buildCommentTree({
						'container': TM.getEl('topic.comments'),
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
		},
		destroy: function(){
			this.tagsWidget.destroy();
			var el = this._TM.getEl('topic.id');
			el.parentNode.removeChild(el);
		}
	};
	NS.TopicWidget = TopicWidget;

	var TopicListWidget = function(container, catid){
		this.init(container, catid || 0);
	};
	TopicListWidget.prototype = {
		init: function(container, catid){
			
			this.catid = 0;
			this.topics = [];
			
			buildTemplate(this, 'list,row');
			
			container.innerHTML = this._TM.replace('list');

			this.loadPage(catid, 0);
		},
		destroy: function(){
			this.clear();
		},
		clear: function(){
			for (var i=0;i<this.topics.length;i++){
				this.topics[i].destroy();
			}
			this._TM.getEl('list.list').innerHTML = '';
		},
		loadPage: function(catid, inc){
			this.catid = catid = catid || 0; inc = inc || 0;
			
			var bm = NS.blogManager;

			// отобразить прогруженные
			this.render();
			
			var __self = this,
				TM = this._TM,
				elLoadMore = TM.getEl('list.loadmore');
			
			Dom.setStyle(elLoadMore, 'display', '');
			bm.loadPage(catid, inc, function(){
				Dom.setStyle(elLoadMore, 'display', 'none');
				__self.render();
			});
		},
		render: function(){
			
			this.clear();
			
			var TM = this._TM, TId = this._TId,
				lst = "";
			
			NS.blogManager.foreach(function(top){
				lst += TM.replace('row', {'id': top.id});
			}, this.catid);
			TM.getEl('list.list').innerHTML = lst;

			var topics = [];
			NS.blogManager.foreach(function(top){
				var el = Dom.get(TId['row']['id']+'-'+top.id);
				topics[topics.length] = new NS.TopicWidget(el, top);
			});
			this.topics = topics;
		}
		
	};
	NS.TopicListWidget = TopicListWidget;
	
	var TopicViewPanel = function(topicid, anchor){
		this.topicid = topicid || 0;
		this.anchor = anchor;
		
		TopicViewPanel.superclass.constructor.call(this, {
			fixedcenter: true, width: '790px', height: '400px',
			overflow: false, 
			controlbox: 1
		});
	};
	YAHOO.extend(TopicViewPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'view');
			return this._TM.replace('view');
		},
		onLoad: function(){
			var TM = this._TM, __self = this, topicid = this.topicid;

			this.topicWidget = null;
			this.globalMenu = new NS.GlobalMenuWidget(TM.getEl('view.gmenu'), 'view');
			
			NS.buildBlogManager(function(){
				NS.blogManager.topicLoad(topicid, function(topic){
					__self.onBuildManager(topic);
				});
			});
			
		},
		setTopicConfig: function(topicid, anchor){
			this.topicid = topicid;
			if (L.isNull(this.topicWidget)){ return; }
			this.topicWidget.destroy();
			var __self = this;
			NS.blogManager.topicLoad(topicid, function(topic){
				__self.onBuildManager(topic);
			});
		},
		onBuildManager: function(topic){
			var TM = this._TM;
			this.topicWidget = new TopicWidget(TM.getEl('view.widget'), topic, {
				'fullview': true
			});
		},
		destroy: function(){
			if (!L.isNull(this.topicWidget)){
				this.topicWidget.destroy();
			}
			TopicViewPanel.superclass.destroy.call(this);
		}
	});
	NS.TopicViewPanel = TopicViewPanel;
	
	var activePanel = null;
	NS.API.showTopicViewPanel = function(topicid, anchor){
		if (L.isNull(activePanel) || activePanel.isDestroy()){
			activePanel = new TopicViewPanel(topicid, anchor);
		}else{
			activePanel.setTopicConfig(topicid, anchor);
		}
		return activePanel;
	};
	
};