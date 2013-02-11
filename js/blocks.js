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
					'urltop': topic.url()
				});
			});
			this.elSetHTML('list', TM.replace('toplist', {
				'rows': lst
			}));
		}
	});
	NS.TopicListBlockWidget = TopicListBlockWidget;	
	
	var TagListBlockWidget = function(container){
		TagListBlockWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'tags,taglist,tagrow' 
		});
	};
	YAHOO.extend(TagListBlockWidget, Brick.mod.widget.Widget, {
		onLoad: function(){
			var __self = this;
			NS.initManager(function(){
				NS.manager.tagListLoad({'limit': 35}, function(list){
					__self.renderList(list);
				});
			});
		},
		renderList: function(list){
			this.elHide('loading');
			if (L.isNull(list)){ return; }

			var arr = [], min = 999999, max = 0;
			list.foreach(function(tag){
				arr[arr.length] = tag;
				min = Math.min(min, tag.topicCount);
				max = Math.max(max, tag.topicCount);
			});
			
			arr = arr.sort(function(t1, t2){
				if (t1.title < t2.title){ return -1; }
				if (t1.title > t2.title){ return 1; }
				return 0;
			});
			
			var fmin = 0, fmax = 10;
			if (min == max){
				max++;
			}
			var g1 = Math.log(min+1),
				g2 = Math.log(max+1);
			
			var lst = "", TM = this._TM;
			for (var i=0;i<arr.length;i++){
				var tag = arr[i], cnt = tag.topicCount;
				
				var n1 = (fmin+Math.log(cnt+1)-g1)*fmax,
					n2 = g2-g1,
					v = Math.ceil(n1/n2);

				lst += TM.replace('tagrow', {
					'tagtl': tag.title,
					'urltag': tag.url(),
					'sz': v
				});
			}
			this.elSetHTML('list', TM.replace('taglist', {
				'rows': lst
			}));
		}
	});
	NS.TagListBlockWidget = TagListBlockWidget;	
	

};