/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = { };
Component.entryPoint = function(NS){
	
	if (!Brick.mod.bos && Brick.mod.bos.onlineManager){ 
		return; 
	}

	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var buildTemplate = this.buildTemplate;
	
	var OnlineWidget = function(container, rs){
		OnlineWidget.superclass.constructor.call(this, container, rs);
	};
	YAHOO.extend(OnlineWidget, Brick.mod.bos.OnlineWidget, {
		init: function(container, rs){
			var TM = buildTemplate(this, 'widget,item,rss'), lst = "";
			
			var ts = rs['topics'];
			
			for (var i=0,di;i<ts.length;i++){
				di = ts[i];
				lst += TM.replace('item', {
					'id': di['id'],
					'tl': di['tl']
				});
			}
			
			var isRSS = Brick.Permission.check('rss', '10')==1,
				sRSS = !isRSS ? '' : TM.replace('rss');
			
			container.innerHTML = TM.replace('widget', {
				'rss': sRSS,
				'lst': lst
			});
		}
	});
	NS.OnlineWidget = OnlineWidget;
	
	NS.OnlineWidget.isEmptyRecords = function(rs){
		return !(L.isObject(rs) && rs['topics'].length > 0);
	};
	
	Brick.mod.bos.onlineManager.register('{C#MODNAME}', OnlineWidget);
};