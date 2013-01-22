/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['container.js']},
        {name: 'blog', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var API = NS.API,
		R = NS.roles;

	var buildTemplate = this.buildTemplate;
	
	var TopicTagListWidget = function(container, topic){
		this.init(container, topic);
	};
	TopicTagListWidget.prototype = {
		init: function(container, topic){
			this.topic = topic;
			this.tagList = topic.tagList;
			buildTemplate(this, 'widget,table,row');
			
			container.innerHTML = this._TM.replace('widget');
			this.render();
		},
		destroy: function(){},
		render: function(){
			var TM = this._TM,
				tlst = [];
			
			this.tagList.foreach(function(tag){
				tlst[tlst.length] = TM.replace('row', {
					'id': tag.id,
					'tl': tag.title
				});
			});
			
			TM.getEl('widget.id').innerHTML = TM.replace('table', {
				'rows': tlst.join(', ')
			});
		}
	};
	NS.TopicTagListWidget = TopicTagListWidget;
	
};