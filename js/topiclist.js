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
	
	
	var TopicListWidget = function(container, catid){
		TopicListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'list,row' 
		}, catid || 0);
	};
	YAHOO.extend(TopicListWidget, Brick.mod.widget.Widget, {
		init: function(catid){
			this.catid = 0;
			this.topics = [];
			
			this.wsList = [];
		},
		onLoad: function(catid){
			var __self = this;
			NS.buildBlogManager(function(){
				__self.loadPage(catid, 0);
			});
			
			NS.initManager(function(){
				NS.manager.topicListLoad(function(list){
					
				});
			});
		},
		destroy: function(){
			this.clear();
		},
		clear: function(){
			var ws = this.wsList;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this.elSetHTML('list', '');
		},
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
				
	});
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
	/**/
};