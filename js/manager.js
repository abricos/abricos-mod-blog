/*
@version $Id$
@copyright Copyright (C) 2008 Abricos. All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/**
 * @module Blog
 * @namespace Brick.mod.blog
 */
 
var Component = new Brick.Component();
Component.requires = {
    yahoo: ['autocomplete','dragdrop'],
	mod:[
	     {name: 'sys', files: ['form.js', 'editor.js', 'data.js', 'container.js', 'widgets.js', 'wait.js']},
	     {name: 'blog', files: ['roles.js']}
	]
};
Component.entryPoint = function(){
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		W = YAHOO.widget;
	
	var TMG = this.template, 
		NS = this.namespace,
		API = this.namespace.API,
		R = NS.roles;
	
	if (!NS.data){
		NS.data = new Brick.util.data.byid.DataSet('blog');
	}
	var DATA = NS.data;

	var LW = Brick.widget.LayWait;
	
	var buildTemplate = function(w, templates){
		var TM = TMG.build(templates), T = TM.data, TId = TM.idManager;
		w._TM = TM; w._T = T; w._TId = TId;
	};

	// Шаблон для модальных панелей
	var TM = TMG.build('topiclistpanel,editor,btnsave,btnpub,btndraft'),
		T = TM.data,
		TId = TM.idManager;
	
	
//////////////////////////////////////////////////////////////
//                        TopicListPanel                    //
//////////////////////////////////////////////////////////////
	
	/**
	 * Панель "Список записей в блоге".
	 * 
	 * @class TopicListPanel
	 */
	var TopicListPanel = function(){
		TopicListPanel.superclass.constructor.call(this, {
			modal: true, fixedcenter: true
		});
	};
	YAHOO.extend(TopicListPanel, Brick.widget.Panel, {
		el: function(name){ return Dom.get(TId['topiclistpanel'][name]); },
		initTemplate: function(){
			return T['topiclistpanel'];
		},
		onLoad: function(){
			this.topicListWidget = new NS.TopicListWidget(TId['topiclistpanel']['container']);
			
			var firstRender = true, __self = this;
			this.topicListWidget.parentRender = this.topicListWidget.renderElements;
			this.topicListWidget.renderElements = function(){
				this.parentRender();
				if (firstRender){
					__self.center();
				}
				firstRender = false;
			};
		},
		destroy: function(){
			this.topicListWidget.destroy();
			TopicListPanel.superclass.destroy.call(this);
		},
		onClick: function(el){
			switch(el.id){
			case TId['topiclistpanel']['bclose']: this.close(); return true;
			}
			return false;
		}
	});
	
	NS.TopicListPanel = TopicListPanel;
	
	/**
	 * Показать панель "Список моих записей в блоге"
	 * 
	 * @method showTopicListPanel
	 * @class API
	 * @static
	 */
	API.showTopicListPanel = function(){
		var widget = new NS.TopicListPanel();
		API.addWidget('TopicListPanel', widget);
		DATA.request();
		return widget;
	};
	
//////////////////////////////////////////////////////////////
//                        TopicListWidget                   //
//////////////////////////////////////////////////////////////
	
	/**
	 * Виджет "Список записей в блоге".
	 * 
	 * @class TopicListWidget
	 */
	var TopicListWidget = function(container){
		container = L.isString(container) ? Dom.get(container) : container;
		var TM = TMG.build('panel,table,row,rowwait,rowdel,titledel,bipub'),
			T = TM.data,
			TId = TM.idManager;
		
		this._TM = TM;
		this._T = T;
		this._TId = TId;
		
		var config = {
			rowlimit: 10,
			tables: {
				'list': 'topiclist',
				'count': 'topiclistcount'
			},
			tm: TM,
			paginators: ['panel.pagtop', 'panel.pagbot'],
			DATA: DATA
		};
		TopicListWidget.superclass.constructor.call(this, container, config);    
	};
	
    YAHOO.extend(TopicListWidget, Brick.widget.TablePage, {
    	initTemplate: function(){
    		return this._T['panel'];
    	},
    	renderTableAwait: function(){
    		this._TM.getEl("panel.table").innerHTML = this._TM.replace('table', {'rows': this._T['rowwait']});
    	},
    	renderTable: function(lst){
    		this._TM.getEl("panel.table").innerHTML = this._TM.replace('table', {'rows': lst}); 
    	}, 
		renderRow: function(di){
			return this._TM.replace(di['dd']>0 ? 'rowdel' : 'row', {
				'unm': di['unm'],
				'dl': Brick.dateExt.convert(di['dl']),
				'de': Brick.dateExt.convert(di['de']),
				'dp': di['dp']>0 ? Brick.dateExt.convert(di['dp']) : this._T['bipub'],
				'cat': di['cat'],
				'lnk': '/blog/'+di['catnm']+'/'+di['id'],
				'tl': di['tl'],
				'id': di['id']
			});
    	},
    	onClick: function(el){
			var tp = this._TId['panel']; 
			switch(el.id){
			case tp['btnadd']: 
				API.showTopicEditorPanel(0); 
				return true;
			case tp['refresh']: this.refresh(); return true;
			case tp['rcclear']: this.recycleClear(); return true;
			}
			
			var prefix = el.id.replace(/([0-9]+$)/, '');
			var numid = el.id.replace(prefix, "");
			
			switch(prefix){
			case (this._TId['row']['edit']+'-'):
				API.showTopicEditorPanel(numid);
				return true;
			case (this._TId['row']['remove']+'-'): this.remove(numid); return true;
			case (this._TId['rowdel']['restore']+'-'): this.restore(numid); return true;
			case (this._TId['bipub']['id']+'-'): this.publish(numid); return true;
			}
			return false;
    	},
		_createWait: function(){
			return new LW(this._TM.getEl("panel.table"), true);
		},
    	_ajax: function(data){
			var lw = this._createWait(), __self = this;
			Brick.ajax('blog',{
				'data': data,
				'event': function(request){
					lw.hide();
					__self.refresh();
				}
			});
    	},
		remove: function(topicid){
			this._ajax({'type': 'topic', 'do': 'remove', 'id': topicid});
		},
		restore: function(topicid){
			this._ajax({'type': 'topic', 'do': 'restore', 'id': topicid});
		},
		recycleClear: function(){
			this._ajax({'type': 'topic', 'do': 'rclear'});
		},
		publish: function(topicid){
			this._ajax({'type': 'topic', 'do': 'publish', 'id': topicid});
		}
    	
    });
	
	NS.TopicListWidget = TopicListWidget;
	
	/**
	 * Показать виджет "Список моих записей в блоге"
	 * 
	 * @method showTopicListWidget
	 * @class API
	 * @static
	 * @param {Object} container Идентификатор HTML элемента или 
	 * HTML элемент, контейнер  в котором будет показан виджет.
	 */
	API.showManagerWidget = function(container){
		R.load(function(){
			new NS.TopicListWidget(container);
			DATA.request();
		});
	};

//////////////////////////////////////////////////////////////
//                      TopicEditorPanel                    //
//////////////////////////////////////////////////////////////
	
	/**
	 * Панель "Редактор записи в блоге"
	 * 
	 * @class TopicEditorPanel
	 * @constructor
	 * @param {Integer} Идентификатор записи.
	 */
	var TopicEditorPanel = function(topicId){
		
		/**
		 * Идентификатор записи.
		 * @property topicId
		 * @type Integer
		 */
		this.topicId = topicId;
		
		TopicEditorPanel.superclass.constructor.call(this, {
			modal: true, fixedcenter: true
		});
	};
	YAHOO.extend(TopicEditorPanel, Brick.widget.Panel, {
		
		/**
		 * Рубрика блога.
		 * 
		 * @property category
		 * @type Object
		 */
		category: {},
		
		/**
		 * Редактор поля "Анонс"
		 * 
		 * @property editorIntro
		 * @type Brick.widget.Editor
		 */
		editorIntro: null,
		
		/**
		 * Редактор поля "Основная запись"
		 * 
		 * @property editorBody
		 */
		editorBody: null,
		
		el: function(name){ return Dom.get(TId['editor'][name]); },
		elv: function(name){ return Brick.util.Form.getValue(this.el(name)); },
		setelv: function(name, value){ Brick.util.Form.setValue(this.el(name), value); },
		
		initTemplate: function(){
			return TM.replace('editor', {
				'buttons': this.topicId>0 ? T['btnsave'] : T['btnpub']+T['btndraft']
			});
		},
		onLoad: function(){
			this.tagManager = new TagsAutocomplete(TId['editor']['tags'], TId['editor']['tagscont']);
			
			this.validator = new Brick.util.Form.Validator({
				elements: {
					'category':{obj: this.el('category'), rules: ["empty"], args:{"field":"Рубрика"}},
					'title':{obj: this.el('title'), rules: ["empty"], args:{"field":"Заголовок"}},
					'tags': {obj: this.el('tags'), rules: ["empty"], args:{"field":"Метки"}}
				}
			});
			
			var Editor = Brick.widget.Editor;

			this.editorIntro = new Editor(TId['editor']['bodyint'], {
				width: '750px', height: '150px', 'mode': Editor.MODE_VISUAL
			});

			this.editorBody = new Editor(TId['editor']['bodyman'], {
				width: '750px', height: '250px', 'mode': Editor.MODE_VISUAL
			});
			
			if (this.topicId > 0){
				var __self = this;
				Brick.ajax('blog', {
					'data': { 'type': 'topic', 'id': this.topicId },
					'event': function(request){
						__self.setData(request.data);
					}
				});
			}else{
				this.setData();
			}
		},
		onClick: function(el){
			var __self = this;
			switch(el.id){
			case TId['editor']['bcategory']:
				API.showCategoryListPanel(function(data){ 
					__self.setCategory(data); 
				});
				return true;
			case TId['btnpub']['id']: this.save('pub'); return true;
			case TId['btndraft']['id']: this.save('draft'); return true;
			case TId['btnsave']['id']: this.save('save'); return true;
			case TId['editor']['bcancel']: this.close(); return true;
			}
			return false;
		},
		setCategory: function(d){
			this.category = d;
			this.setelv('category', this.category['ph'] || ''); 
		},
		setData: function(d){
			this.data = d = L.merge({
				"id":"0",
				"mtd":"","mtk":"",
				"nm":"",	
				"tl":"","catid":"","catph":"",
				"catnm":"","ctid":"",
				"intro":"",
				"body":"",
				"uid":"","unm":"","dl":"","de":"","dp":"",
				"st":"0","dd":"","tags":""
			}, d || {});
			
			var disBtn = function(a){
				for(var i=0;i<a.length;i++){
					var el = Dom.get(TId[a[i]]['id']); 
					if (!L.isNull(el)){ el.disabled = ""; }
				}
			};

			this.el('bcategory').disabled = "";
			this.el('title').disabled = "";
			this.el('tags').disabled = "";
			disBtn(['btnpub', 'btndraft', 'btnsave']);
			
			if (this.topicId > 0){
				this.editorIntro.setContent(d['intro']);
				this.editorBody.setContent(d['body']);
				this.setCategory({
					'id': d['catid'],
					'ph': d['catph'],
					'nm': d['catnm']
				});
				this.setelv('title', d['tl']);
				this.setelv('tags', d['tags']);
			}
		},
		destroy: function(){
			this.editorIntro.destroy();
			this.editorBody.destroy();
			TopicEditorPanel.superclass.destroy.call(this);
		},
		save: function(status){
			var errors = this.validator.check();
			if (errors.length > 0){ return; }
			
			var oTitle = this.el('title');
			var oTags = this.el('tags');

			var sIn = this.editorIntro.getContent();
			var sBd = this.editorBody.getContent();
			
			var s = sIn + ' ' + sBd;
			
			var cat = this.category;
			var data = {
				'id': this.data['id'],
				'nm': Brick.util.Translite.ruen(oTitle.value),
				'tl': oTitle.value,
				'intro': sIn,
				'body': sBd,
				'tags': oTags.value,
				'catid': cat.id,
				'catph': cat.ph,
				'catnm': cat.nm
			};

			if (status == 'pub'){
				data['st'] = 1;
			}else if (status == 'draft'){
				data['st'] = 0;
			}
			
			var __self = this,
				lw = new LW(this.body, true);
			Brick.ajax('blog', {
				'data': {'type': 'topic', 'do': 'save', 'data': data},
				'event': function(request){
					
					lw.hide();
					
					var tbl1 = DATA.get('topiclist');
					var tbl2 = DATA.get('topiclistcount');
					if (!L.isNull(tbl1)){ tbl1.clear(); }
					if (!L.isNull(tbl2)){ tbl2.clear(); }
					DATA.request();

					__self.close();
				}
			});
		}
	});
	
	NS.TopicEditorPanel = TopicEditorPanel;
	
	var TagsAutocomplete = function(input, container){
	    var ds = new YAHOO.util.XHRDataSource('/ajax/blog/js_tags/');
	    ds.connMethodPost = true;  
	    ds.responseSchema = {recordDelim:"\n", fieldDelim: "\t"};
	    ds.responseType = YAHOO.util.XHRDataSource.TYPE_TEXT;
	    ds.maxCacheEntries = 60;

		var oAC = new YAHOO.widget.AutoComplete(input, container, ds);
		oAC.delimChar = [",",";"]; // Enable comma and semi-colon delimiters
	};
	
	/**
	 * Отобразить панель "Редактор записи в блоге"
	 * 
	 * @method showTopicEditorPanel
	 * @class API
	 * @static
	 * @param {Integer} topicid Идентификатор записи в блоге, 
	 * если 0, создать новый. 
	 */
	API.showTopicEditorPanel = function(topicid){
		return new NS.TopicEditorPanel(topicid);
	};


//////////////////////////////////////////////////////////////
//                     CategoryListPanel                    //
//////////////////////////////////////////////////////////////

	/**
	 * Панель "Список категорий блога"
	 */
	var CategoryListPanel = function(callback){
		this.callback = callback;
		this.selectedRow = null;
		CategoryListPanel.superclass.constructor.call(this, {
			modal: true, fixedcenter: true
		});
	};
	
	YAHOO.extend(CategoryListPanel, Brick.widget.Panel, {
		el: function(name){ return Dom.get(this._TId['catlistpanel'][name]); },
		initTemplate: function(){
			buildTemplate(this, 'catlistpanel,catlisttable,catlistrowwait,catlistrow');
			return this._T['catlistpanel'];
		},
		onLoad: function(){
			this.el('table').innerHTML = this._TM.replace('catlisttable', {
				'rows': this._T['catlistrowwait']
			});

			this.tables = {'categorylist': DATA.get('categorylist', true) };
			if (DATA.isFill(this.tables)){ this.renderElements(); }
			
			DATA.onComplete.subscribe(this.dsComplete, this, true);
			if (!R.isAdmin){
				this._TM.getEl('catlistpanel.bnew').style.display = 'none';
			}
			DATA.request();
		},
		dsComplete: function(type, args){
			if (args[0].checkWithParam('categorylist')){ 
				this.renderElements(); 
			}
		},
		destroy: function(){
			CategoryListPanel.superclass.destroy.call(this);
			DATA.onComplete.unsubscribe(this.dsComplete, this); 
		},
		renderElements: function(){
			var TM = this._TM;
			var rows = this.tables['categorylist'].getRows();
			var lst = "";
			rows.foreach(function(row){
				var di = row.cell;
				lst += TM.replace('catlistrow', {
					'ph': di['ph'],
					'cnt': di['cnt'],
					'id': di['id'],
					'viewedit': R.isAdmin ? '' : 'none',
					'viewremove': R.isAdmin ? '' : 'none'
				});
			});
			this.el('table').innerHTML = TM.replace('catlisttable', {'rows': lst}); 
		},
		onClick: function(el){
			var TId = this._TId;
			var tp = TId['catlistpanel']; 
			switch(el.id){
			case tp['bselect']: this.select();	return true;
			case tp['bcancel']: this.close(); return true;
			case tp['bnew']: this.edit(); return true;
			}
			var prefix = el.id.replace(/([0-9]+$)/, '');
			var numid = el.id.replace(prefix, "");
			
			var tp = TId['catlistrow']; 
			switch(prefix){
			case tp['td1']+'-':
			case tp['td2']+'-':
			case tp['td3']+'-':
				this.selectRow(numid);
				return true;
			case tp['edit']+'-': this.edit(numid); return true;
			case tp['del']+'-':
				// this.remove(row); 
				return true;
			}
			return false;
		},
		edit: function(categoryid){
			categoryid = categoryid || 0;
			var table = DATA.get('categorylist'), 
				rows = table.getRows();
			
			var row = categoryid == 0 ? table.newRow() : rows.getById(categoryid);
			
			new NS.CategoryEditorPanel(row, function(){
				if (row.isNew()){ rows.add(row); }
				table.applyChanges();
				DATA.request();
			});
		},
		selectRow: function(catid){
			var row = DATA.get('categorylist').getRows().getById(catid);
			if (L.isNull(row)){ return; }
			
			var di = row.cell;
			this.el('category').value = di['ph'];
			this.el('bselect').disabled = "";

			this.close();
			this.callback(di);
		}
	});
	
	NS.CategoryListPanel = CategoryListPanel;
	
	/**
	 * Показать панель "Список категорий блога"
	 *
	 * @method showCategoryListPanel
	 * @static
	 * @param {Function} callback
	 */
	API.showCategoryListPanel = function(callback){
		R.load(function(){
			new NS.CategoryListPanel(callback);
		});
	};

//////////////////////////////////////////////////////////////
//                     CategoryEditorPanel                  //
//////////////////////////////////////////////////////////////


	var CategoryEditorPanel = function(row, callback){
		this.row = row;
		this.callback = callback;
		CategoryEditorPanel.superclass.constructor.call(this, {
			modal: true, fixedcenter: true
		});
	};
	YAHOO.extend(CategoryEditorPanel, Brick.widget.Panel, {
		el: function(name){ return Dom.get(this._TId['cateditorpanel'][name]); },
		elv: function(name){ return Brick.util.Form.getValue(this.el(name)); },
		setelv: function(name, value){ Brick.util.Form.setValue(this.el(name), value); },
		initTemplate: function(){
			buildTemplate(this, 'cateditorpanel');
			return this._T['cateditorpanel']; 
		},
		onLoad: function(){
			var phrase = this.el('phrase');
			this.validator = new Brick.util.Form.Validator({
				elements: {'phrase':{ obj: phrase, rules: ["empty"], args:{"field":"Название"}}}
			});
			var di = this.row.cell;

			this.setelv('phrase', di['ph']);
			this.setelv('name', di['nm']);
			this.el('bsave').disabled = "";
		},
		onClick: function(el){
			var tp = this._TId['cateditorpanel'];
			switch(el.id){
			case tp['bsave']: this.save(); return true;
			case tp['bcancel']: this.close(); return true;
			case tp['name']: this._updateCatName(); return false;
			}
			return false;
		},
		_updateCatName: function(){
			var txtname = this.el('name');
			if (txtname.value.length == 0){
				var txtphrase = this.el('phrase');
				txtname.value = Brick.util.Translite.ruen(txtphrase.value);
			}
		},
		save: function(){
			this._updateCatName();
			if (this.validator.check() > 0){ return; }
			
			this.row.update({
				'ph': this.elv('phrase'),
				'nm': this.elv('name')
			});
			this.callback();
			this.close();
		}
	});
	
	NS.CategoryEditorPanel = CategoryEditorPanel;
	
	/**
	 * Показать панель "Редактор категории блога"
	 *
	 * @method showCategoryEditorPanel
	 * @static
	 * @param {Integer} categoryId Идентификатор категории, если 0, 
	 * то создание новой категории
	 */
	API.showCategoryEditorPanel = function(row, callback){
		var widget = new NS.CategoryEditorPanel(row, callback);
		DATA.request();
		return widget;
	};

(function(){
	var cleanSpace = function(s){
		s = s.replace(/^\s*|\s*$/g, '');
		var n=-1;
		do{
			n = s.length;
			s = s.replace(/\s\s/g, ' ');
		}while(n != s.length);
		
		return s;
	};
	
	var keywords = function(){};
	keywords.prototype = {
		create: function(s){

			s = s.replace(/[^a-zA-Z0-9\-\а-\я\А-\Я]/g, " ");
			s = cleanSpace(s);
			s = s.toLowerCase();
			
			var a = s.split(' '), i, w, words = [], find, j;
			for (i=0;i<a.length;i++){
				w = a[i];
				if (w.length > 3){
					find = false;
					for (j=0;j<words.length;j++){
						if (words[j].word == w){
							words[j].count++;
							find = true;
							break;
						}
					}
					if (!find){
						words[words.length] = {word: w, count: 1};
					}
				}
			}
			var ret=[];
			for (i=0;i<words.length;i++){
				ret[ret.length] = words[i].word;
			}
			
			return ret;
		},
		createByBlogTags: function(s){
			var a = this.create(s), i, ret = '', w, len;
			for (i=0;i<a.length;i++){
				len = a[i].length;
				if (len >= 7){
					w = a[i].substring(len-2,0);
				}else{
					w = a[i];
				}
				ret += w+' ';
			}
			return ret;
		}
	};
	NS.Keywords = new keywords();
	
	var search = function(s, f){
		var r = new RegExp(f, 'gi');
		return r.test(s);
	};
	
	var descript = function(){};
	descript.prototype = {
		create: function(s, title, tags){

			// ключевые слова
			var keya = NS.Keywords.createByBlogTags(title+' '+tags).split(' ');

			// предложения страницы 
			s = s.replace(/[^a-zA-Z0-9\-\а-\я\А-\Я\.\,\:]/g, " ");
			s = cleanSpace(s);
			var ws = s.split('.');
			
			// определение значимости предложения
			var wso = [], o, i, j, level, wset='';
			for (i=0;i<ws.length;i++){
				o = { s: ws[i], level: 0 };
				for (j=0;j<keya.length;j++){
					if (search(o.s, keya[j])){
						o.level++;
					}
				}
				wso[wso.length] = o;
			}
			
			// сортировка
			var change = false, sa, sb;
			do{
				change = false;
				for (i=0;i<wso.length-1;i++){
					sa = wso[i];
					sb = wso[i+1];
					if (sa.level < sb.level){
						wso[i] = sb;
						wso[i+1]=sa;
						change = true;
					}
				}
			}while(change);
			
			var maxLength = 200; 
			
			if (wso.length == 0 || wso[0].s.length < maxLength){
				return wso[0].s;
			}else{
				var a = wso[0].s.split(' '), i, ret = '';
				for (i=0;i<a.length;i++){
					if (ret.length + a[i].length > maxLength){
						break;
					}
					ret += a[i] + ' ';
				}
				return ret; 
			}
		}
	};
	
	NS.Description = new descript();
	
})();
	
};


