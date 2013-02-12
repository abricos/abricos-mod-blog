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
$count = min($cats->Count(), 10);

$lst = "";
for ($i=0; $i<$count; $i++){
	$cat = $cats->GetByIndex($i);

	$lst .= Brick::ReplaceVarByData($v['row'], array(
		"cattl" => $cat->title,
		"urlcat" => $cat->URL(),
		"rtg" => $cat->rating
	));
}


if (empty($lst)){
	$brick->content = "";
	return;
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"rows" => $lst
));

?>