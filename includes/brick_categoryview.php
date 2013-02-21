<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$v = &$brick->param->var;

$man = BlogModule::$instance->GetManager();
$cats = $man->CategoryList();

if (BlogManager::$isURating){
	Abricos::GetModule('urating')->GetManager();
}


$dir = Abricos::$adress->dir;

$cat = $cats->GetByName($dir[1]);

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'tl' => $cat->title,
	'mbrs' => $cat->memberCount,
	'topics' => $cat->topicCount
));

?>