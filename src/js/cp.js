/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
	     {name: 'user', files: ['cpanel.js']}
	]
};
Component.entryPoint = function(){
	/*
	if (Brick.Permission.check('blog', '20') < 1){ return; }
	
	var cp = Brick.mod.user.cp;
	
	var menuItem = new cp.MenuItem(this.moduleName);
	menuItem.icon = '/modules/blog/images/cp_icon.gif';
	menuItem.entryComponent = 'manager';
	menuItem.entryPoint = 'Brick.mod.blog.API.showManagerWidget';
	
	cp.MenuManager.add(menuItem);
	/**/
};