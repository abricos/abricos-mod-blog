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
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
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
				lst += TM.replace('cmtrow', {
					'uid': cmt.user.id,
					'login': cmt.user.userName,
					'unm': cmt.user.getUserName(),
					'cattl': cmt.topic.category.title,
					'urlcat': cmt.topic.category.url(),
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
				NS.manager.topicListLoad(function(list){
					__self.renderList(list);
				}, {
					'limit': 5
				});
			});
		},
		renderList: function(list){
			this.elHide('loading');
			if (L.isNull(list)){ return; }
			var lst = "", TM = this._TM;
			list.foreach(function(topic){
				lst += TM.replace('toprow', {
					'cattl': topic.category.title,
					'urlcat': topic.category.url(),
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