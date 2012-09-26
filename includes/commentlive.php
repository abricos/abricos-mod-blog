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

$modUProfile = Abricos::GetModule('uprofile');
$isUProfileExist = !empty($modUProfile);

$baseUrl = "/".$module->takelink."/";

$showCount = 0;
$lst = "";
$rows = $manager->CommentLive(5);
while (($row = Abricos::$db->fetch_array($rows))){
	
	/* fixed bug */
	if (empty($row['catph']) || empty($row['title'])){
		continue;
	}
	$showCount++;
	$lst .= Brick::ReplaceVarByData($brick->param->var['row'], array(
		"usrsrc" => ($isUProfileExist ? '/uprofile/#app=uprofile/ws/showws/'.$row['uid'].'/' : '#'),
		"uid" => $row['uid'],
		"unm" => $row['unm'],
		"catnm" => $row['catph'],
		"topnm" => $row['title'], 
		"catlnk" => $baseUrl.$row['catnm']."/",
		"toplnk" => $baseUrl.$row['catnm']."/".$row['topicid']."/",
		"cid" => $row['contentid'],
		"count" => $row['cnt']
	));
}
$brick->param->var['lst'] = $lst;
if ($showCount == 0 && !$brick->param->param['showempty']){
	$brick->content = "";
}

?>