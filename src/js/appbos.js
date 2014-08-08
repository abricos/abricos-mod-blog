/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.entryPoint = function(){
	
	if (Brick.Permission.check('blog', '10') != 1){ return; }
	
	var os = Brick.mod.bos;
	
	var app = new os.Application(this.moduleName);
	app.icon = '/modules/blog/images/app_icon.gif';
	app.entryComponent = 'wspace';
	app.entryPoint = 'ws';
	
	os.ApplicationManager.register(app);
	
};
