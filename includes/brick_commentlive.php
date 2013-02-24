<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$man = BlogModule::$instance->GetManager();
$cats = $man->CategoryList();

$modUProfile = Abricos::GetModule('uprofile');

$lst = "";
$comms = $man->CommentLiveList();
$count = $comms->Count();
for ($i=0; $i<$count; $i++){
	$comm = $comms->GetByIndex($i);
	$topic = $comm->topic;
	$cat = $topic->Category();
	
	$lst .= Brick::ReplaceVarByData($brick->param->var['row'], array(
		"urlusr" => $topic->user->URL(),
		"uid" => $topic->user->id,
		"login" => $topic->user->userName,
		"cattl" => $cat->title,
		"urlcat" => $cat->URL(),
		"toptl" => $topic->title,
		"urltop" => $topic->URL(),
		"urlcmt" => $topic->URL(),
		"cmtcnt" => $topic->commentCount
	));
}

if (empty($lst)){
	$brick->content = "";
	return;
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'rows' => $lst
));

?>