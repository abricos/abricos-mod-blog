/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        // {name: '{C#MODNAME}', files: ['topic.js']}
	]
};
Component.entryPoint = function(NS){
	
	var L = YAHOO.lang,
		E = YAHOO.util.Event,
		buildTemplate = this.buildTemplate;

	var NextWidget = function(container, cfg){
		cfg = L.merge({
			'page': 1,
			'loaded': 0,
			'total': 0,
			'limit': 10,
			'nextCallback': null
		}, cfg || {});
		
		NextWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'next' 
		}, cfg);
	};
	YAHOO.extend(NextWidget, Brick.mod.widget.Widget, {
		init: function(cfg){
			this.cfg = cfg;
			this._isProcess = false;
		},
		onClick: function(el){
			this.nextLoad();
			return true;
		},
		render: function(){
			var cfg = this.cfg,
				loaded = cfg['loaded']*1,
				total = cfg['total']*1,
				limit = cfg['limit']*1;
			
			limit = Math.max(Math.min(limit, total-loaded), 0);
			
			this.elSetVisible('id', loaded < total);
			
			this.elSetHTML({
				'limit': limit,
				'total': total-loaded
			});
		},
		nextLoad: function(){
			var __self = this, cfg = this.cfg;
			
			if (this._isProcess){ return; }
			if (!L.isFunction(cfg['nextCallback'])){ return; }

			this._isProcess = true;
			
			this.elHideShow('info', 'loading');
			var page = cfg['page'] = cfg['page']+1;

			cfg['nextCallback'](page, function(nCfg){
				__self.onLoaded(nCfg);
			});
		},
		onLoaded: function(nCfg){
			this._isProcess = false;
			this.cfg = L.merge(this.cfg, nCfg || {});
			
			this.render();
			this.elShowHide('info', 'loading');
		}
	});
	NS.NextWidget = NextWidget;		

};