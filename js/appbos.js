/*
@version $Id: appbos.js 258 2011-05-05 14:15:45Z roosit $
@copyright Copyright (C) 2008 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.entryPoint = function(){
	
	if (Brick.Permission.check('blog', '10') != 1){ return; }
	
	var os = Brick.mod.bos;
	
	var app = new os.Application(this.moduleName);
	app.icon = '/modules/blog/images/app_icon.gif';
	app.entryComponent = 'board';
	app.entryPoint = 'showBoardPanel';
	
	os.ApplicationManager.register(app);
	
};
