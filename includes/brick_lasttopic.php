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

$lst = "";
$topics = $man->TopicList(array("limit"=>5));
$count = $topics->Count();
for ($i=0; $i<$count; $i++){
	$topic = $topics->GetByIndex($i);
	$cat = $cats->Get($topic->catid);

	$lst .= Brick::ReplaceVarByData($brick->param->var['row'], array(
		"cattl" => $cat->title,
		"urlcat" => $cat->URL(),
		"toptl" => $topic->title,
		"urltop" => $topic->URL()
	));
}

if (empty($lst)){
	$brick->content = "";
	return;
}

$modRSS = Abricos::GetModule('rss');

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'rss' => empty($modRSS) ? "" : $brick->param->var['rss'],
	'rows' => $lst
));

?>