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
$topics = $man->TopicList(array("limit"=>5));
$count = $topics->Count();
for ($i=0; $i<$count; $i++){
	$topic = $topics->GetByIndex($i);
	$cat = $cats->Get($topic->catid);
	
	$urlusr = "#";
	if (!empty($modUProfile)){
		$urlusr = '/uprofile/#app=uprofile/ws/showws/'.$topic->user->id.'/';
	}

	$lst .= Brick::ReplaceVarByData($brick->param->var['row'], array(
		"urlusr" => $urlusr,
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