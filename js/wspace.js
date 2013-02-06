/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: '{C#MODNAME}', files: ['blocks.js']}
	]		
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang,
		R = NS.roles;

	var buildTemplate = this.buildTemplate;

	var AccessDeniedWidget = function(container){
		this.init(container);
	};
	AccessDeniedWidget.prototype = {
		init: function(container){
			buildTemplate(this, 'accessdenied');
			container.innerHTML = this._TM.replace('accessdenied');
		},
		destroy: function(){
			var el = this._TM.getEl('accessdenied.id');
			el.parentNode.removeChild(el);
		}
	};
	NS.AccessDeniedWidget = AccessDeniedWidget;

	var GMID = {
		'TopicListWidget': 'topics',
		'TopicViewWidget': 'topics',
		'CategoryListWidget': 'cats',
		'CategoryViewWidget': 'cats',
		'WriteWidget': 'write',
		'AboutWidget': 'about'
	};
	GMIDI = {
		'topics': ['all', 'pub', 'person'],
		'write': ['topic', 'category', 'draftlist']
	};
	var DEFPAGE = {
		'component': 'topic',
		'wname': 'TopicListWidget',
		'p1': '', 'p2': '', 'p3': '', 'p4': ''
	};
	
	var WSWidget = function(container, pgInfo){
		WSWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, pgInfo || []);
	};
	YAHOO.extend(WSWidget, Brick.mod.widget.Widget, {
		init: function(pgInfo){
			this.pgInfo = pgInfo;
			this.widget = null;
		},
		buildTData: function(pgInfo){
			var NG = NS.navigator;
			return {
				'urlhome': NG.home(),
				'urltopics': NG.topic.list(),
				'urlcats': NG.category.list(),
				'urlauthors': NG.author.list(),
				'urlwrite': NG.write.topic(),
				'urlwritetopic': NG.write.topic(),
				'urlwritecategory': NG.write.category(),
				'urlwritedraftlist': NG.write.draftlist()
			};
		},
		onLoad: function(pgInfo){
			var __self = this;
			NS.initManager(function(){
				__self.onLoadManager(pgInfo);
			});
		},
		onLoadManager: function(pgInfo){
			this.cmtLiveWidget = new NS.CommentLiveBlockWidget(this.gel('cmtlive'));
			this.topicListWidget = new NS.TopicListBlockWidget(this.gel('toplist'));
			this.showPage(pgInfo);
		},
		showPage: function(p){
			p = L.merge(DEFPAGE, p || {});

			this.elHide('board');
			this.elShow('loading');

			var __self = this;
			Brick.ff('{C#MODNAME}', p['component'], function(){
				__self._showPageMethod(p);
			});
		},
		_showPageMethod: function(p){
			
			var wName = p['wname'];
			if (!NS[wName]){ return; }
			
			if (!L.isNull(this.widget)){
				this.widget.destroy();
				this.widget = null;
			}
			this.elSetHTML('board', "");
			
			this.widget = new NS[wName](this.gel('board'), p['p1'], p['p2'], p['p3'], p['p4']);
			
			var isUpdate = {};
			for (var n in GMID){
				
				var pfx = GMID[n], 
					miEl = this.gel('m'+pfx),
					mtEl = this.gel('mt'+pfx);

				if (wName == n){
					isUpdate[pfx] = true;
					
					Dom.addClass(miEl, 'sel');
					Dom.setStyle(mtEl, 'display', '');

					var mia = GMIDI[pfx];
					if (L.isArray(mia)){
						for (var i=0; i<mia.length; i++){
							var mtiEl = this.gel('i'+pfx+mia[i]);
							if (mia[i] == this.widget.wsMenuItem){
								Dom.addClass(mtiEl, 'current');
							}else{
								Dom.removeClass(mtiEl, 'current');
							}
						}
					}
					
				}else{
					if (isUpdate[pfx]){ continue; }
					
					Dom.removeClass(miEl, 'sel');
					Dom.setStyle(mtEl, 'display', 'none');
				}
			}
			this.elShow('board');
			this.elHide('loading');
		}		
	});
	NS.WSWidget = WSWidget;

	
	var WSPanel = function(pgInfo){
		this.pgInfo = pgInfo || [];
		
		WSPanel.superclass.constructor.call(this, {
			fixedcenter: true, width: '790px', height: '400px'
		});
	};
	YAHOO.extend(WSPanel, Brick.widget.Panel, {
		initTemplate: function(){
			return buildTemplate(this, 'panel').replace('panel');
		},
		onLoad: function(){
			this.widget = new NS.WSWidget(this._TM.getEl('panel.widget'), this.pgInfo);
		},
		showPage: function(p){
			this.widget.showPage(p);
		}
	});
	NS.WSPanel = WSPanel;
	
	var activeWSPanel = null;
	NS.API.ws = function(){
		var args = arguments;
		var pgInfo = {
			'component': args[0] || 'topic',
			'wname': args[1] || 'TopicListWidget',
			'p1': args[2], 'p2': args[3], 'p3': args[4], 'p4': args[5]
		};
		if (L.isNull(activeWSPanel) || activeWSPanel.isDestroy()){
			activeWSPanel = new WSPanel(pgInfo);
		}else{
			activeWSPanel.showPage(pgInfo);
		}
		return activeWSPanel;
	};
	
};