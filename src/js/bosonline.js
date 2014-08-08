/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
	     {name: 'widget', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	if (!Brick.mod.bos && Brick.mod.bos.onlineManager){ 
		return; 
	}

	var buildTemplate = this.buildTemplate;
	
	var OnlineWidget = function(container, rs){
		OnlineWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget,rss' 
		}, rs);
	};
	YAHOO.extend(OnlineWidget, Brick.mod.widget.Widget, {
		buildTData: function(rs){
			var isRSS = Brick.Permission.check('rss', '10')==1,
				sRSS = !isRSS ? '' : this._TM.replace('rss');
			
			return {'rss': sRSS };
		},
		onLoad: function(){
			var __self = this;
			Brick.f('{C#MODNAME}', 'boxes', function(){
				__self._onLoadWidgets();
			});
		},
		_onLoadWidgets: function(){
			new NS.TopicListBoxWidget(this.gel('box'));
		}
	});
	NS.OnlineWidget = OnlineWidget;
	NS.OnlineWidget.isEmptyRecords = function(rs){
		
		return rs*1 == 0;
	};
	
	Brick.mod.bos.onlineManager.register('{C#MODNAME}', OnlineWidget);
};