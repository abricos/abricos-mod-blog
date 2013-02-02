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
	
	var L = YAHOO.lang,
		NSUR = Brick.mod.urating || {},
		UID = Brick.env.user.id,
		buildTemplate = this.buildTemplate;
	
	var CategoryRowWidget = function(container, cat){
		CategoryRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, cat);
	};
	YAHOO.extend(CategoryRowWidget, Brick.mod.widget.Widget, {
		init: function(cat){
			this.cat = cat;
		},
		buildTData: function(cat){
			return {
				'urlview': cat.url(),
				'rtg': 0,
				'mbrs': 0,
				'topics': cat.topicCount
			};
		},
		destroy: function(){
			// this.infoWidget.destroy();
		},
		onLoad: function(cat){
			this.elSetHTML({
				'tl': cat.title
			});
		}
	});
	NS.CategoryRowWidget = CategoryRowWidget;
	
	var CategoryListWidget = function(container){
		CategoryListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'catlist' 
		});
	};
	YAHOO.extend(CategoryListWidget, Brick.mod.widget.Widget, {
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
				ws[ws.length] = new NS.CategoryRowWidget(div, cat);
			});
		}
	});
	NS.CategoryListWidget = CategoryListWidget;

	var CategoryViewWidget = function(container, catid){
		CategoryViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'catview' 
		}, catid);
	};
	YAHOO.extend(CategoryViewWidget, Brick.mod.widget.Widget, {
		init: function(catid){
			this.catid = catid;
			this.viewWidget = null;
		},
		destroy: function(){
			if (!L.isNull(this.viewWidget)){
				this.viewWidget.destroy();
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
			this.elHide('loading');
			
			if (L.isNull(cat)){
				this.elShow('nullitem');
				return;
			}
			
			this.elSetHTML({
				'tl': cat.title
			});
			
			if (NSUR.VotingWidget){
				this.voteWidget = new NSUR.VotingWidget(this.gel('rating'), {
					'modname': 'blog',
					'elementType': 'cat',
					'elementId': cat.id,
					'value': 0,
					// 'vote': user.repMyVote,
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

		}
	});
	NS.CategoryViewWidget = CategoryViewWidget;		

};