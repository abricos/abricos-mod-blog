/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var L = YAHOO.lang;
	
	var buildTemplate = this.buildTemplate;
	
	var CommentLiveBlockWidget = function(container){
		CommentLiveBlockWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'comments,cmtlist,cmtrow' 
		});
	};
	YAHOO.extend(CommentLiveBlockWidget, Brick.mod.widget.Widget, {
		onLoad: function(){
			var __self = this;
			NS.initManager(function(){
				NS.manager.commentLiveListLoad(function(list){
					__self.renderList(list);
				});
			});
		},
		renderList: function(list){
			this.elHide('loading');
			if (L.isNull(list)){ return; }
			var lst = "", TM = this._TM;
			list.foreach(function(cmt){
				var cat = cmt.topic.category();
				lst += TM.replace('cmtrow', {
					'uid': cmt.user.id,
					'login': cmt.user.userName,
					'unm': cmt.user.getUserName(),
					'cattl': cat.title,
					'urlcat': cat.url(),
					'toptl': cmt.topic.title,
					'urlcmt': cmt.topic.url(),
					'cmtcnt': cmt.topic.commentCount
				});
			});
			this.elSetHTML('list', TM.replace('cmtlist', {
				'rows': lst
			}));
		}
	});
	NS.CommentLiveBlockWidget = CommentLiveBlockWidget;

	var TopicListBlockWidget = function(container){
		TopicListBlockWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'topics,toplist,toprow' 
		});
	};
	YAHOO.extend(TopicListBlockWidget, Brick.mod.widget.Widget, {
		onLoad: function(){
			var __self = this;
			NS.initManager(function(){
				NS.manager.topicListLoad({'limit': 5}, function(list){
					__self.renderList(list);
				});
			});
		},
		renderList: function(list){
			this.elHide('loading');
			if (L.isNull(list)){ return; }
			var lst = "", TM = this._TM;
			list.foreach(function(topic){
				var cat = topic.category();
				lst += TM.replace('toprow', {
					'cattl': cat.title,
					'urlcat': cat.url(),
					'toptl': topic.title,
					'urlcmt': topic.url()
				});
			});
			this.elSetHTML('list', TM.replace('toplist', {
				'rows': lst
			}));
		}
	});
	NS.TopicListBlockWidget = TopicListBlockWidget;	
};