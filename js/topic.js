/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: 'urating', files: ['vote.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){

	var L = YAHOO.lang;
	var buildTemplate = this.buildTemplate;
	var NSUR = Brick.mod.urating || {};
	var LNG = this.language;
	var R = NS.roles;
	
	var TopicManagerWidget = function(container, topic){
		TopicManagerWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'manbtns' 
		}, topic);
	};
	YAHOO.extend(TopicManagerWidget, Brick.mod.widget.Widget, {
		init: function(topic){
			this.topic = topic;
		},
		buildTData: function(topic){
			return {
				'urledit': NS.navigator.topic.edit(topic.id)
			};
		}
	});
	NS.TopicManagerWidget = TopicManagerWidget;

	var TopicInfoLineWidget = function(container, topic, cfg){
		TopicInfoLineWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'info' 
		}, topic, cfg);
	};
	YAHOO.extend(TopicInfoLineWidget, Brick.mod.widget.Widget, {
		init: function(topic, cfg){
			this.topic = topic;
		},
		buildTData: function(topic, cfg){
			var user = topic.user;
			return {
				'date': L.isNull(topic.date) ? LNG.get('topic.draft') : Brick.dateExt.convert(topic.date),
				'uid': user.id,
				'avatar': user.avatar24(),
				'unm': user.getUserName(),
				'cmt': topic.commentCount
			};
		},
		onLoad: function(topic, cfg){
			if (NSUR.VotingWidget){
				this.voteWidget = new NSUR.VotingWidget(this.gel('topicvote'), {
					'modname': '{C#MODNAME}',
					'elementType': 'topic',
					'elementId': topic.id
				});
				this.elShow('topicvote');
			}
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
			this.manWidget = null;
		},
		buildTData: function(topic){
			return {
				'urlview': topic.url()
			};
		},
		destroy: function(){
			this.tagsWidget.destroy();
			this.infoWidget.destroy();
			TopicRowWidget.superclass.destroy.call(this);
		},
		onLoad: function(topic){
			this.tagsWidget = new NS.TagListWidget(this.gel('taglist'), topic.tagList);
			this.infoWidget = new NS.TopicInfoLineWidget(this.gel('info'), topic);
			
			var cat = topic.category();

			this.elSetHTML({
				'intro': topic.intro,
				'tl': topic.title,
				'cattl': !L.isNull(cat) ? cat.title : ''
			});
			
			if (R.topic.isManager(topic)){
				this.manWidget = new NS.TopicManagerWidget(this.gel('man'), topic);
			}
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
	
	var TopicListWidget = function(container){
		var args = arguments, cfg = {};

		if (L.isObject(args[1])){
			cfg = args[1];
		}else if (args.length > 1){
			var af = [];
			for (var i=1;i<args.length;i++){
				if (L.isString(args[i])){
					af[af.length] = args[i];
				}
			}
			cfg = {
				'filter': af.join("/")
			};
		}
		
		cfg = L.merge({
			'filter': ''
		}, cfg || {});
		
		TopicListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'topiclist' 
		}, cfg);
	};
	YAHOO.extend(TopicListWidget, Brick.mod.widget.Widget, {
		init: function(cfg){
			this.catid = 0;
			this.wsList = [];
			this.wsMenuItem = 'all'; // использует wspace.js
		},
		onLoad: function(cfg){
			var __self = this;
			NS.initManager(function(){
				NS.manager.topicListLoad(function(list){
					__self.renderList(list);
				}, cfg);
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