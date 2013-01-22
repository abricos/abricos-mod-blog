/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: 'blog', files: ['topiclist.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var TMG = this.template,
		initCSS = false,
		buildTemplate = function(w, ts){
		if (!initCSS){
			Brick.util.CSS.update(Brick.util.CSS['{C#MODNAME}']['{C#COMNAME}']);
			delete Brick.util.CSS['{C#MODNAME}']['{C#COMNAME}'];
			initCSS = true;
		}
		w._TM = TMG.build(ts); w._T = w._TM.data; w._TId = w._TM.idManager;
	};
	
	var BoardPanel = function(){
		BoardPanel.superclass.constructor.call(this, {
			fixedcenter: true, width: '790px', height: '400px',
			overflow: false, 
			controlbox: 1
		});
	};
	YAHOO.extend(BoardPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'panel');
			return this._TM.replace('panel');
		},
		onLoad: function(){
			var TM = this._TM, __self = this;
			
			this.topicList = null;
			this.globalMenu = new NS.GlobalMenuWidget(TM.getEl('panel.gmenu'), 'topiclist');
			
			NS.buildBlogManager(function(){
				__self.onBuildManager();
			});
		},
		onBuildManager: function(){
			this.topicList = new NS.TopicListWidget(this._TM.getEl('panel.list'));
		},
		destroy: function(){
			if (!L.isNull(this.topicList)){
				this.topicList.destroy();
			}
			BoardPanel.superclass.destroy.call(this);
		}
	});
	NS.BoardPanel = BoardPanel;
	
	var activeBoardPanel = null;
	NS.API.showBoardPanel = function(){
		if (L.isNull(activeBoardPanel) || activeBoardPanel.isDestroy()){
			activeBoardPanel = new BoardPanel();
		}
		return activeBoardPanel;
	};
};