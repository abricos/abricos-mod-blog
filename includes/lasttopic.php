<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Blog
 * @copyright Copyright (C) 2008 Abricos All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$brick = Brick::$builder->brick;

$module = BlogModule::$instance;
$manager = $module->GetManager();

$baseUrl = "/".$module->takelink."/";

$lst = "";
$rows = $manager->TopicLastList(5);
while (($row = Brick::$db->fetch_array($rows))){
	
	$lst .= Brick::ReplaceVarByData($brick->param->var['row'], array(
		"cattl" => $row['catph'],
		"topnm" => $row['tl'], 
		"catlnk" => $baseUrl.$row['catnm']."/",
		"toplnk" => $baseUrl.$row['catnm']."/".$row['id']."/"
	));
}
$brick->param->var['lst'] = $lst;

?>