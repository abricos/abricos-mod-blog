/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2011 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: 'blog', files: ['roles.js', ]}
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
	
	var UserGroupWidget = function(container, userid){
		this.init(container, userid);
	};
	UserGroupWidget.prototype = {
		init: function(container, userid){
			this.userid = userid;
			var TM = buildTemplate(this, 'ugwidget,ugstable,ugsrow,ugsrowwait,ugtable,ugrowwait,ugrow');
			
			container.innerHTML = TM.replace('ugwidget');

			TM.getEl('ugwidget.selgroups').innerHTML = TM.replace('ugstable', {
				'rows': TM.replace('ugsrowwait')
			});

			TM.getEl('ugwidget.table').innerHTML = TM.replace('ugtable', {
				'rows': TM.replace('ugrowwait')
			});

			var __self = this;
			E.on(TM.getEl('ugwidget.id'), 'click', function(e){
                if (__self.onClick(E.getTarget(e))){ E.preventDefault(e); }
			});
		},
		destroy: function(){},
		render: function(){
			var lst = "", TM = this._TM, T = this._T;
			DATA.get('grouplist').getRows().foreach(function(row){
				var di = row.cell;
				lst += TM.replace('ugsrow', {
					'nm': di['nm'],
					'id': di['id']
				});
			});
			TM.getEl('ugwidget.selgroups').innerHTML = TM.replace('ugstable', {
				'rows': lst
			});
		},
		renderGroupTable: function(){
			var arr = this.groups, TM = this._TM,
				groupRows = DATA.get('grouplist').getRows(),
				lst = "";
			for (var i=0;i<arr.length;i++){
				var group = groupRows.getById(arr[i]);
				if (!L.isNull(group)){
					lst += TM.replace('ugrow', {
						'nm': group.cell['nm'],
						'id': group.cell['id']
					});
				}
			}
			TM.getEl('ugwidget.table').innerHTML = TM.replace('ugtable', {
				'rows': lst
			});
		},
		setValue: function(groups){
			this.groups = groups.split(',');
			this.renderGroupTable();
		},
		addGroup: function(groupid){
			var find = false, arr = this.groups;
			for (var i=0;i<arr.length;i++){
				if (arr[i]*1 == groupid*1){
					find = true;
				}
			}
			if (find){ return; }
			this.groups[this.groups.length] = groupid;
			this.renderGroupTable();
		},
		removeGroup: function(groupid){
			var find = false, arr = this.groups, newarr = [];
			
			for (var i=0;i<arr.length;i++){
				if (arr[i]*1 != groupid*1){
					newarr[newarr.length] = arr[i];
				}
			}
			this.groups = newarr;
			this.renderGroupTable();
		},
		getValue: function(){
			return this.groups.join(',');
		}, 
		onClick: function(el){
			var TId = this._TId, TM = this._TM;
			
			if (el.id == TId['ugstable']['badd']){
				var groupid = TM.getEl('ugstable.id').value;
				this.addGroup(groupid);
				return true;
			}
			
			var prefix = el.id.replace(/([0-9]+$)/, '');
			var numid = el.id.replace(prefix, "");

			switch(prefix){
			case (TId['ugrow']['remove']+'-'):
				this.removeGroup(numid);
				return true;
			}
			
			return false;
		}
	};
	NS.UserGroupWidget = UserGroupWidget;
	
};