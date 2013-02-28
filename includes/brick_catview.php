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
$pa = BlogModule::$instance->ParserAddress();
$f = explode("/", $pa->topicListFilter);
$isNew = $f[2] == 'new';

$cats = $man->CategoryList();

if (BlogManager::$isURating){
	Abricos::GetModule('urating')->GetManager();
}

$dir = Abricos::$adress->dir;

$cat = $cats->GetByName($dir[1]);

$topics = $pa->topicList ;

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'tl' => $cat->title,
	"catname" => $cat->name,
	'mbrs' => $cat->memberCount,
	'topics' => $cat->topicCount,
	"newcnt" => $topics->totalNew>0 ? "+".$topics->totalNew : "",
	"f1sel" => !$isNew ? "sel" : "",
	"f2sel" => !$isNew ? "" : "sel"
));

?>