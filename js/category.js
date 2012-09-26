/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2011 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: 'blog', files: ['group.js', 'roles.js', ]}
	]
};
Component.entryPoint = function(NS){
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;

	var R = NS.roles;

	var buildTemplate = this.buildTemplate;
	
	if (!NS.data){
		NS.data = new Brick.util.data.byid.DataSet('blog');
	}
	var DATA = NS.data;
	
	/**
	 * Панель "Список категорий блога"
	 */
	var CategoryListPanel = function(callback){
		this.callback = callback;
		this.selectedRow = null;
		CategoryListPanel.superclass.constructor.call(this);
	};
	
	YAHOO.extend(CategoryListPanel, Brick.widget.Dialog, {
		el: function(name){ return Dom.get(this._TId['catlistpanel'][name]); },
		initTemplate: function(){
			var TM = buildTemplate(this, 'catlistpanel,catlisttable,catlistrowwait,catlistrow');
			return TM.replace('catlistpanel');
		},
		onLoad: function(){
			var TM = this._TM;
			this.el('table').innerHTML = TM.replace('catlisttable', {
				'rows': TM.replace('catlistrowwait')
			});

			this.tables = {
				'categorylist': DATA.get('categorylist', true), 
				'grouplist': DATA.get('grouplist', true) 
			};
			if (DATA.isFill(this.tables)){ this.renderElements(); }
			
			DATA.onComplete.subscribe(this.dsComplete, this, true);
			if (!R.isAdmin){
				TM.getEl('catlistpanel.bnew').style.display = 'none';
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
			if (L.isFunction(this.callback)){
				this.callback(di);
			}
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
	NS.API.showCategoryListPanel = function(callback){
		R.load(function(){
			new NS.CategoryListPanel(callback);
		});
	};

	var CategoryEditorPanel = function(row, callback){
		this.row = row;
		this.callback = callback;
		CategoryEditorPanel.superclass.constructor.call(this);
	};
	YAHOO.extend(CategoryEditorPanel, Brick.widget.Dialog, {
		el: function(name){ return Dom.get(this._TId['cateditorpanel'][name]); },
		elv: function(name){ return Brick.util.Form.getValue(this.el(name)); },
		setelv: function(name, value){ Brick.util.Form.setValue(this.el(name), value); },
		initTemplate: function(){
			return buildTemplate(this, 'cateditorpanel').replace('cateditorpanel');
		},
		onLoad: function(){
			var TM = this._TM;
			var phrase = this.el('phrase');
			this.validator = new Brick.util.Form.Validator({
				elements: {'phrase':{ obj: phrase, rules: ["empty"], args:{"field":"Название"}}}
			});
			var di = this.row.cell;

			this.setelv('phrase', di['ph']);
			this.setelv('name', di['nm']);
			this.el('bsave').disabled = "";
			
			this.groupWidget = null;
			
			if (R.isAdmin){
				this.groupWidget = new NS.UserGroupWidget(TM.getEl('cateditorpanel.group'));
		 		this.groupWidget.setValue(di['gps']);
		 		this.groupWidget.render();
			}
			this._updateCatName();
			
			var __self = this;
			E.on(this.el('phrase'), 'blur', function(e){
                __self._updateCatName();
			});
			E.on(this.el('name'), 'keyup', function(e){
                __self._updateCatName();
			});
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
			txtname.value = Brick.util.Translite.ruen(txtname.value);
			
			var loc = window.location,
				url = loc.protocol+'//'+loc.hostname+'/blog/'+txtname.value+'/';
			this.el('url').innerHTML = url;
		},
		save: function(){
			this._updateCatName();
			if (this.validator.check() > 0){ return; }
			
			this.row.update({
				'ph': this.elv('phrase'),
				'nm': this.elv('name'),
				'gps': this.groupWidget.getValue()
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
	NS.API.showCategoryEditorPanel = function(row, callback){
		var widget = new NS.CategoryEditorPanel(row, callback);
		DATA.request();
		return widget;
	};

	
};