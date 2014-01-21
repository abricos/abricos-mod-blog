/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: '{C#MODNAME}', files: ['topic.js']}
	]
};
Component.entryPoint = function(NS){
	
	var L = YAHOO.lang,
		E = YAHOO.util.Event,
		buildTemplate = this.buildTemplate;
	
	var TagViewWidget = function(container, tag){
		TagViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, tag);
	};
	YAHOO.extend(TagViewWidget, Brick.mod.widget.Widget, {
		init: function(tag){
			this.tag = tag;
			this.topicListWidget = null;
		},
		destroy: function(){
			if (!L.isNull(this.topicListWidget)){
				this.topicListWidget.destroy();
			}
		},
		onLoad: function(tag){
			var __self = this;
			NS.initManager(function(){
				__self.onLoadManager(tag);
			});
		},
		onLoadManager: function(tag){
			this.tag = tag;
			this.elHide('loading');
			this.elShow('view');
			
			this.elSetValue({
				'tag': tag
			});
			
			if (L.isNull(this.topicListWidget)){
				this.topicListWidget = new NS.TopicListWidget(this.gel('list'), {
					'filter': 'tag/'+tag
				});
			}
			
			var __self = this;
			E.on(this.gel('tag'), 'keypress', function(e){
				if (e.keyCode != 13){ return false; }
				__self.tagView();
				return true;
			});
		},
		tagView: function(){
			var tag = L.trim(this.gel('tag').value);
			if (tag.length == 0){ return; }
			NS.navigator.go(NS.navigator.tag.view(tag));
		}
	});
	NS.TagViewWidget = TagViewWidget;		

};