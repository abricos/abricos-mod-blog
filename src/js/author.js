/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: 'urating', files: ['vote.js']},
        {name: '{C#MODNAME}', files: ['topic.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang,
		NSUR = Brick.mod.urating || {},
		UID = Brick.env.user.id,
		R = NS.roles,
		buildTemplate = this.buildTemplate;
	
	var AuthorListWidget = function(container){
		AuthorListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		});
	};
	YAHOO.extend(AuthorListWidget, Brick.mod.widget.Widget, {
		init: function(){
			this.wsList = [];
			this.wsMenuItem = 'all'; // использует wspace.js
		},
		onLoad: function(catid){
			var __self = this;
			NS.initManager(function(){
				NS.manager.authorListLoad(function(list){
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

			if (L.isNull(list)){
				this.elShow('nullitem');
				return;
			}

			this.elShow('view');
			
			var elList = this.gel('list'), ws = this.wsList;
			
			list.foreach(function(author){
				var div = document.createElement('div');
				elList.appendChild(div);
				ws[ws.length] = new NS.AuthorRowWidget(div, author);
			});
		}
	});
	NS.AuthorListWidget = AuthorListWidget;
	
	var AuthorRowWidget = function(container, author){
		AuthorRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, author);
	};
	YAHOO.extend(AuthorRowWidget, Brick.mod.widget.Widget, {
		buildTData: function(author){
			var user = author.user;
			return {
				'uid': user.id,
				'avatar': user.avatar90(),
				'unm':  user.getUserName(),
				'rep': author.reputation,
				'topics': author.topicCount,
				'urlview': NS.navigator.author.view(author.id)
			};
		}
	});
	NS.AuthorRowWidget = AuthorRowWidget;

	var AuthorViewWidget = function(container, authorid){
		AuthorViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'view' 
		}, authorid);
	};
	YAHOO.extend(AuthorViewWidget, Brick.mod.widget.Widget, {
		init: function(authorid){
			this.viewWidget = null;
			this.topicListWidget = null;
		},
		destroy: function(){
			if (!L.isNull(this.viewWidget)){
				this.viewWidget.destroy();
			}
			if (!L.isNull(this.topicListWidget)){
				this.topicListWidget.destroy();
			}
		},
		onLoad: function(authorid){
			var __self = this;
			NS.initManager(function(){
				NS.manager.authorLoad(authorid, function(author){
					__self.renderAuthor(author);
				});
			});
		},
		renderAuthor: function(author){
			this.author = author;
			this.elHide('loading');
			
			if (L.isNull(author)){
				this.elShow('nullitem');
				return;
			}
			this.elShow('view');
			
			if (L.isNull(this.viewWidget)){
				this.viewWidget = new NS.AuthorRowWidget(this.gel('author'), author);
			}

			if (L.isNull(this.topicListWidget)){
				this.topicListWidget = new NS.TopicListWidget(this.gel('toplist'), {
					'filter': 'author/'+author.id
				});
			}
		}
	});
	NS.AuthorViewWidget = AuthorViewWidget;		
};