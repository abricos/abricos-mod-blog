/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var buildTemplate = this.buildTemplate;
	
	var WriteWidget = function(container, wType){
		WriteWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, wType || 'topic');
	};
	YAHOO.extend(WriteWidget, Brick.mod.widget.Widget, {
		onLoad: function(wType){

			switch(wType){
			case 'blog':
				break;
			default:
				wType = 'topic';
				this.widget = new NS.TopicEditorWidget(this.gel('widget'));
				break;
			}
			this.wType = wType;
			this.wsMenuItem = wType; // использует wspace.js
		}
	});
	NS.WriteWidget = WriteWidget;
	
	
	var TopicEditorWidget = function(container, topicid){
		TopicEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'topic' 
		}, topicid || 0);
	};
	YAHOO.extend(TopicEditorWidget, Brick.mod.widget.Widget, {
		buildTData: function(topicid){
			return {
				'cledst': topicid>0 ? 'edstedit' : 'edstnew'
			};
		},
		destroy: function(){
			this.editorIntro.destroy();
			this.editorBody.destroy();
		},
		onLoad: function(topicid){

			var __self = this;
			NS.initManager(function(){
				if (topicid == 0){
					__self.onLoadManager(new NS.Topic());
				}else{
					NS.manager.topicLoad(topicid, function(topic){
						__self.onLoadManager(topic);
					});
				}
			});
		},
		onLoadManager: function(topic){
			this.elHide('loading');
			this.elHide('wrap');
			
			var Editor = Brick.widget.Editor;
			this.editorIntro = new Editor(this.gel('intro'), {
				'mode': Editor.MODE_VISUAL
			});
			this.editorBody = new Editor(this.gel('body'), {
				'mode': Editor.MODE_VISUAL
			});
			
		}
	});
	NS.TopicEditorWidget = TopicEditorWidget;

	
};