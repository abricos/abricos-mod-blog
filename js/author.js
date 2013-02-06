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
				__self.renderList();
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
		renderList: function(){
			this.clearList();
			this.elHide('loading');
			this.elShow('view');

			var elList = this.gel('list');
			var ws = this.wsList;

			NS.manager.categoryList.foreach(function(cat){
				var div = document.createElement('div');
				elList.appendChild(div);
				ws[ws.length] = new NS.AuthorRowWidget(div, cat);
			});
		}
	});
	NS.AuthorListWidget = AuthorListWidget;
	
	var AuthorRowWidget = function(container, cat){
		AuthorRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, cat);
	};
	YAHOO.extend(AuthorRowWidget, Brick.mod.widget.Widget, {
		init: function(cat){
			this.cat = cat;
		},
		buildTData: function(cat){
			return {
				'urlview': cat.url(),
				'rtg': cat.rating,
				'mbrs': cat.memberCount,
				'topics': cat.topicCount
			};
		},
		onLoad: function(cat){
			this.elSetHTML({
				'tl': cat.title
			});
		}
	});
	NS.AuthorRowWidget = AuthorRowWidget;

	var AuthorViewWidget = function(container, catid){
		AuthorViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'catview' 
		}, catid);
	};
	YAHOO.extend(AuthorViewWidget, Brick.mod.widget.Widget, {
		init: function(catid){
			this.catid = catid;
			this.voteWidget = null;
			this.topicListWidget = null;
		},
		buildTData: function(catid){
			return {
				'urledit': NS.navigator.category.edit(catid)
			};
		},
		destroy: function(){
			if (!L.isNull(this.voteWidget)){
				this.voteWidget.destroy();
			}
			if (!L.isNull(this.topicListWidget)){
				this.topicListWidget.destroy();
			}
		},
		onLoad: function(catid){
			var __self = this;
			NS.initManager(function(){
				var cat = NS.manager.categoryList.get(catid);
				__self.renderCategory(cat);
			});
		},
		renderCategory: function(cat){
			this.cat = cat;
			this.elHide('loading');
			
			if (L.isNull(cat)){
				this.elShow('nullitem');
				return;
			}
			this.elShow('view');
			this.elSetHTML({
				'tl': cat.title,
				'mbrs': cat.memberCount,
				'topics': cat.topicCount
			});
			
			if (NSUR.VotingWidget && L.isNull(this.voteWidget)){
				this.voteWidget = new NSUR.VotingWidget(this.gel('rating'), {
					'modname': '{C#MODNAME}',
					'elementType': 'cat',
					'elementId': cat.id,
					'value': cat.rating,
					'vote': cat.voteMy,
					'hideButtons': UID == 0,
					'onVotingError': function(error, merror){
						/*
						var s = '', lng = LNG['urating']['error'];
						if (merror > 0){
							s = lng['m'+merror];
						}else if (error == 1){
							s = LNG[error];
						}else{
							return;
						}
						/**/
						Brick.mod.widget.notice.show('ERROR');						
					}
				});
			}
			if (L.isNull(this.topicListWidget)){
				this.topicListWidget = new NS.TopicListWidget(this.gel('toplist'), {
					'filter': 'cat/'+cat.id
				});
			}
			if (UID > 0){
				this.elShow('jbtns');
				if (cat.isMember){
					this.elHide('bjoin');
					this.elShow('bleave');
				}else{
					this.elShow('bjoin');
					this.elHide('bleave');
				}
				if (R['isAdmin']){
					this.elShow('bremove');
				}
				if (R.category.isAdmin(cat)){
					this.elShow('bedit');
				}
			}
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bremove']:
				this.showRemovePanel();
				return true;
			case tp['bjoin']: 
			case tp['bleave']:
				this.memberStatusChange();
				return true;
			}
		},
		memberStatusChange: function(){
			var __self = this;
			this.elHide('jbtnsa');
			this.elShow('jbloading');
			NS.manager.categoryJoin(this.cat.id, function(){
				__self.elShow('jbtnsa');
				__self.elHide('jbloading');
				__self.renderCategory(__self.cat);
			});
		},
		showRemovePanel: function(){
			new CategoryRemovePanel(this.cat, function(){
				NS.navigator.go(NS.navigator.category.list());
			});
		}
	});
	NS.AuthorViewWidget = AuthorViewWidget;		


};