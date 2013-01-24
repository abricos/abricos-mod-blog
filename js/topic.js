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
		buildTData: function(topic){
			return {
				'urlview': NS.navigator.topic.view(topic.id)
			};
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
	
	var TopicViewWidget = function(container, topicid){
		TopicViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'topicview' 
		}, topicid);
	};
	YAHOO.extend(TopicViewWidget, Brick.mod.widget.Widget, {
		init: function(topicid){
			this.topicid = topicid;
			this.topic = null;
			this.viewWidget = null;
		},
		destroy: function(){
			if (!L.isNull(this.viewWidget)){
				this.viewWidget.destroy();
			}
		},
		onLoad: function(topicid){
			var __self = this;
			NS.initManager(function(){
				NS.manager.topicLoad(topicid, function(topic){
					__self.renderTopic(topic);
				});
			});
		},
		renderTopic: function(topic){
			this.elHide('loading');
			
			if (L.isNull(topic)){
				this.elShow('nullitem');
				return;
			}

			var widget = this.viewWidget = new NS.TopicRowWidget(this.gel('view'), topic);
			widget.elSetHTML({
				'body': topic.body
			});
			widget.elHide('readmore');
			
			// Инициализировать менеджер комментариев
			Brick.ff('comment', 'comment', function(){
				Brick.mod.comment.API.buildCommentTree({
					'container': widget.gel('comments'),
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
	});
	NS.TopicViewWidget = TopicViewWidget;	
	
	var TopicListWidget = function(container, catid){
		TopicListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'topiclist' 
		}, catid || 0);
	};
	YAHOO.extend(TopicListWidget, Brick.mod.widget.Widget, {
		init: function(catid){
			this.catid = 0;
			this.wsList = [];
			this.wsMenuItem = 'all'; // использует wspace.js
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
	});
	NS.TopicListWidget = TopicListWidget;

};