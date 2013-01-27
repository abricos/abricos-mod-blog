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
	
	var CategoryRowWidget = function(container, category){
		CategoryRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, category);
	};
	YAHOO.extend(CategoryRowWidget, Brick.mod.widget.Widget, {
		init: function(category){
			this.category = category;
		},
		buildTData: function(category){
			return {
				'urlview': category.url()
			};
		},
		destroy: function(){
			// this.infoWidget.destroy();
		},
		onLoad: function(category){
			this.elSetHTML({
				'tl': category.title
			});
		}
	});
	NS.CategoryRowWidget = CategoryRowWidget;
	
	var CategoryViewWidget = function(container, catid){
		CategoryViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'categoryview' 
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
				var category = NS.manager.categoryList.get(catid);
				__self.renderCategory(category);
			});
		},
		renderCategory: function(category){
			this.elHide('loading');
			
			if (L.isNull(category)){
				this.elShow('nullitem');
				return;
			}

			this.viewWidget = new NS.CategoryRowWidget(this.gel('view'), category);
		}
	});
	NS.CategoryViewWidget = CategoryViewWidget;		
	
	var CategoryListWidget = function(container){
		CategoryListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'categorylist' 
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

			var elList = this.gel('list');
			var ws = this.wsList;

			NS.manager.categoryList.foreach(function(category){
				var div = document.createElement('div');
				elList.appendChild(div);
				ws[ws.length] = new NS.CategoryRowWidget(div, category);
			});
		}
	});
	NS.CategoryListWidget = CategoryListWidget;

};