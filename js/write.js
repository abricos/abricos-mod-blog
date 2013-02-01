/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/
var Component = new Brick.Component();
Component.requires = {
    yahoo: ['autocomplete','dragdrop'],
	mod:[
		{name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['topic.js']}
	]
};
Component.entryPoint = function(NS){
	
	var L = YAHOO.lang;
	
	var buildTemplate = this.buildTemplate;
	
	var WriteCategorySelectWidget = function(container){
		WriteCategorySelectWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'catsel,catselrow,catselmyrow' 
		});
	};
	YAHOO.extend(WriteCategorySelectWidget, Brick.mod.widget.Widget, {
		buildTData: function(){
			var TM = this._TM, lst = TM.replace('catselmyrow');
			NS.manager.categoryList.foreach(function(cat){
				lst += TM.replace('catselrow', {
					'id': cat.id,
					'tl': cat.title
				});
			});
			return { 'rows': lst };
		},
		getValue: function(){
			return this.gel('id').value;
		}
	});
	NS.WriteCategorySelectWidget = WriteCategorySelectWidget;
	
	
	var WriteWidget = function(container, wType){
		WriteWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, wType || 'topic');
	};
	YAHOO.extend(WriteWidget, Brick.mod.widget.Widget, {
		init: function(wType){
			this.widget = null;
		},
		onLoad: function(wType) {
			switch(wType){
			case 'category':
				wType = 'category';
				this.widget = new NS.CategoryEditorWidget(this.gel('widget'));
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
		init: function(topicid){
			this.topicid = topicid;
			this.catSelWidget = null;
			this.editorWidget = null;
		},
		buildTData: function(topicid){
			return {
				'cledst': topicid>0 ? 'edstedit' : 'edstnew'
			};
		},
		destroy: function(){
			if (!L.isNull(this.editorWidget)){
				this.editorWidget.destroy();
				this.catSelWidget.destroy();
			}
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
			this.topic = topic;
			this.elHide('loading');
			this.elHide('wrap');
			
			this.catSelWidget = new NS.WriteCategorySelectWidget(this.gel('catsel'));

			this.tagManager = new TagsAutocomplete(this.gel('tags'), this.gel('tagscont'));

			var Editor = Brick.widget.Editor;
			this.editorWidget = new Editor(this.gel('text'), {
				'toolbar': Editor.TOOLBAR_STANDART,
				// 'mode': Editor.MODE_VISUAL,
				'toolbarExpert': false,
				'separateIntro': true
			});
			
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bpreview']: this.showPreview(); return true;
			}
			return false;
		},
		getSaveData: function(){
			var stags = this.gel('tags').value;
			var splitText = this.editorWidget.getSplitContent();
			
			return {
				'catid': this.catSelWidget.getValue(),
				'tl': this.gel('title').value,
				'tags': NS.TagList.stringToAJAX(stags),
				'intro': splitText['intro'],
				'bd': splitText['body']
			};
		},
		showPreview: function(){
			var sd = this.getSaveData();
			Brick.console(sd);
			new TopicPreviewPanel(new NS.Topic(sd));
		}
	});
	NS.TopicEditorWidget = TopicEditorWidget;

	var TopicPreviewPanel = function(topic){
		this.topic = topic;
		TopicPreviewPanel.superclass.constructor.call(this);
	};
	YAHOO.extend(TopicPreviewPanel, Brick.widget.Dialog, {
		initTemplate: function(){
			return buildTemplate(this, 'topicpreview').replace('topicpreview');
		},
		onLoad: function() {
			var topic = this.topic;
			var widget = this.viewWidget = 
				new NS.TopicRowWidget(this._TM.getEl('topicpreview.widget'), topic);
			widget.elSetHTML({
				'body': topic.body
			});
			
			widget.elHide('readmore');
		}
	});
	NS.TopicPreviewPanel = TopicPreviewPanel;	
	
	var TagsAutocomplete = function(input, container){
	    var ds = new YAHOO.util.XHRDataSource('/ajax/blog/js_tags/');
	    ds.connMethodPost = true;  
	    ds.responseSchema = {recordDelim:"\n", fieldDelim: "\t"};
	    ds.responseType = YAHOO.util.XHRDataSource.TYPE_TEXT;
	    ds.maxCacheEntries = 60;

		var oAC = new YAHOO.widget.AutoComplete(input, container, ds);
		oAC.delimChar = [",",";"]; // Enable comma and semi-colon delimiters
	};

	var CategoryEditorWidget = function(container, categoryid){
		CategoryEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'blog' 
		}, categoryid || 0);
	};
	YAHOO.extend(CategoryEditorWidget, Brick.mod.widget.Widget, {
		init: function(categoryid){
			this.categoryid = categoryid;
			this.editorWidget = null;
		},
		buildTData: function(categoryid){
			return {
				'cledst': categoryid>0 ? 'edstedit' : 'edstnew'
			};
		},
		destroy: function(){
			if (!L.isNull(this.editorWidget)){
				this.editorWidget.destroy();
			}
		},
		onLoad: function(categoryid){
			var __self = this;
			NS.initManager(function(){
				if (categoryid == 0){
					__self.onLoadManager(new NS.Category());
				}else{
					var cat = NS.manager.categoryList.get(categoryid);
					__self.onLoadManager(cat);
				}
			});
		},
		onLoadManager: function(category){
			this.category = category;
			this.elHide('loading');
			this.elHide('wrap');
			
			var Editor = Brick.widget.Editor;
			this.editorWidget = new Editor(this.gel('text'), {
				'toolbar': Editor.TOOLBAR_MINIMAL,
				'toolbarExpert': false,
				'separateIntro': false
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			// case tp['bpreview']: this.showPreview(); return true;
			}
			return false;
		},
		getSaveData: function(){
			/*
			var stags = this.gel('tags').value;
			var splitText = this.editorWidget.getSplitContent();
			
			return {
				'catid': this.catSelWidget.getValue(),
				'tl': this.gel('title').value,
				'tags': NS.TagList.stringToAJAX(stags),
				'intro': splitText['intro'],
				'bd': splitText['body']
			};
			/**/
		}
	});
	NS.CategoryEditorWidget = CategoryEditorWidget;	
};