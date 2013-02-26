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
	
	var L = YAHOO.lang,
		R = NS.roles,
		LNG = this.language,
		buildTemplate = this.buildTemplate;
	
	var WriteWidget = function(container, wType, p1){
		WriteWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, wType || 'topic', p1);
	};
	YAHOO.extend(WriteWidget, Brick.mod.widget.Widget, {
		init: function(wType, p1){
			this.widget = null;
		},
		destroy: function(){
			if (!L.isNull(this.widget)){
				this.widget.destroy();
			}
			WriteWidget.superclass.destroy.call(this);			
		},
		onLoad: function(wType, p1) {
			switch(wType){
			case 'category':
				wType = 'category';
				this.widget = new NS.CategoryEditorWidget(this.gel('widget'), p1);
				break;
			case 'draftlist':
				wType = 'draftlist';
				this.widget = new NS.TopicListWidget(this.gel('widget'), {
					'filter': 'draft'
				});
				break;
			default:
				wType = 'topic';
				this.widget = new NS.TopicEditorWidget(this.gel('widget'), p1);
				break;
			}
			this.wType = wType;
			this.wsMenuItem = wType; // использует wspace.js
		}
	});
	NS.WriteWidget = WriteWidget;
	
	var WriteCategorySelectWidget = function(container, catid){
		WriteCategorySelectWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'catsel,catselrow,catselmyrow' 
		}, catid || 0);
	};
	YAHOO.extend(WriteCategorySelectWidget, Brick.mod.widget.Widget, {
		buildTData: function(catid){
			var TM = this._TM, lst = TM.replace('catselmyrow');
			NS.manager.categoryList.foreach(function(cat){
				if (!R.category.isMember(cat)){ return; }
				lst += TM.replace('catselrow', {
					'id': cat.id,
					'tl': cat.title
				});
			});
			return { 'rows': lst };
		},
		onLoad: function(catid){
			this.setValue(catid);
		},
		getValue: function(){
			return this.gel('id').value;
		},
		setValue: function(value){
			this.gel('id').value = value;
		}
	});
	NS.WriteCategorySelectWidget = WriteCategorySelectWidget;
	
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
			TopicEditorWidget.superclass.destroy.call(this);
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
			
			this.catSelWidget = new NS.WriteCategorySelectWidget(this.gel('catsel'), topic.catid);

			this.tagManager = new TagsAutocomplete(this.gel('tags'), this.gel('tagscont'));

			this.elSetValue({
				'title': topic.title,
				'tags': topic.tagList.toString()
			});

			var Editor = Brick.widget.Editor;
			this.editorWidget = new Editor(this.gel('text'), {
				'toolbar': Editor.TOOLBAR_STANDART,
				// 'mode': Editor.MODE_VISUAL,
				'toolbarExpert': false,
				'separateIntro': true
			});
			
			var text = topic.intro;
			if (topic.isBody){
				text += "<cut>" + topic.body;
			}
			this.editorWidget.setContent(text);
			
			if (R['isAdmin']){
				this.elShow('admindex');
				
				this.gel('isindex').checked = (topic.isIndex && !topic.isAutoIndex) ? 'checked' : '';
			}
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bpreview']: this.showPreview(); return true;
			case tp['bsavedraft']: this.saveDraft(); return true;
			case tp['bcreate']: 
			case tp['bsave']: this.save(); return true;
			case tp['bcancel']: this.cancel(); return true;
			}
			return false;
		},
		getSaveData: function(){
			var stags = this.gel('tags').value;
			var splitText = this.editorWidget.getSplitContent();
			
			return {
				'id': this.topic.id,
				'catid': this.catSelWidget.getValue(),
				'tl': this.gel('title').value,
				'tags': NS.TagList.stringToAJAX(stags),
				'intro': splitText['intro'],
				'body': splitText['body'],
				'idx': this.gel('isindex').checked ? 1 : 0
			};
		},
		showPreview: function(){
			var sd = this.getSaveData();
			new TopicPreviewPanel(new NS.Topic(sd));
		},
		saveDraft: function(){
			this.save(true);
		},
		save: function(isdraft){
			isdraft = isdraft || false; 
			var __self = this;
			var sd = this.getSaveData();

			this.elHide('btnsblock');
			this.elShow('bloading');
			sd['dft'] = isdraft?1:0;
			NS.manager.topicSave(sd, function(topicid, error){
				__self.elShow('btnsblock');
				__self.elHide('bloading');

				if (L.isNull(error) || topicid == 0){
					error = L.isNull(error) ? 'null' : error;
					var sError = LNG.get('write.topic.error.'+error);
					Brick.mod.widget.notice.show(sError);
				}else{
					Brick.Page.reload(NS.navigator.topic.view(topicid));
				}
			});
		},
		cancel: function(){
			if (this.topicid == 0){
				Brick.Page.reload(NS.navigator.home());
			}else{
				Brick.Page.reload(NS.navigator.topic.view(this.topicid));
			}
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

	var CategoryEditorWidget = function(container, catid){
		CategoryEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'blog' 
		}, catid || 0);
	};
	YAHOO.extend(CategoryEditorWidget, Brick.mod.widget.Widget, {
		init: function(catid){
			this.catid = catid;
			this.editorWidget = null;
		},
		buildTData: function(catid){
			return {
				'cledst': catid>0 ? 'edstedit' : 'edstnew'
			};
		},
		destroy: function(){
			if (!L.isNull(this.editorWidget)){
				this.editorWidget.destroy();
			}
			CategoryEditorWidget.superclass.destroy.call(this);
		},
		onLoad: function(catid){
			var __self = this;
			NS.initManager(function(){
				if (catid == 0){
					__self.onLoadManager(new NS.Category());
				}else{
					var cat = NS.manager.categoryList.get(catid);
					__self.onLoadManager(cat);
				}
			});
		},
		onLoadManager: function(cat){
			this.cat = cat;
			this.elHide('loading');
			this.elHide('wrap');
			
			var Editor = Brick.widget.Editor;
			this.editorWidget = new Editor(this.gel('text'), {
				'toolbar': Editor.TOOLBAR_MINIMAL,
				'toolbarExpert': false,
				'separateIntro': false
			});
			this.editorWidget.setContent(cat.descript);

			this.elSetValue({
				'title': cat.title,
				'rep': cat.reputation
			});
			
			if (NS.isURating){
				this.elShow('repblock');
			}
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bcancel']: this.cancel(); return true;
			case tp['bcreate']: 
			case tp['bsave']: this.save(); return true;
			}
			return false;
		},
		getSaveData: function(){
			return {
				'id': this.cat.id,
				'tl': this.gel('title').value,
				'dsc': this.editorWidget.getContent(),
				'rep': this.gel('rep').value
			};
		},
		cancel: function(){
			if (this.cat.id > 0){
				Brick.Page.reload(NS.navigator.category.view(this.cat.id));
			}else{
				Brick.Page.reload(NS.navigator.home());
			}
		},
		save: function(){
			var __self = this;
			this.elHide('btnsblock');
			this.elShow('bloading');
			var sd = this.getSaveData();
			NS.manager.categorySave(sd, function(catid, error){
				__self.elShow('btnsblock');
				__self.elHide('bloading');

				if (L.isNull(error) || catid == 0){
					error = L.isNull(error) ? 'null' : error;
					var sError = LNG.get('write.category.error.'+error);
					Brick.mod.widget.notice.show(sError);
				}else{
					Brick.Page.reload(NS.navigator.category.view(catid));
				}
			});
		}
		
	});
	NS.CategoryEditorWidget = CategoryEditorWidget;	
};