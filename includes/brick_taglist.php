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

$tags = $man->TagList(array("limit"=>35));
$tags->SortByTitle();
$count = $tags->Count();

$min = 99999999; $max = 1;
$stags = "";
for ($i=0; $i<$count; $i++){
	$tag = $tags->GetByIndex($i);
	
	$min = min($tag->topicCount, $min);
	$max = max($tag->topicCount, $max);
}

$fmin = 0;
$fmax = 10;

if ($min == $max){ $max++; }
$g1 = log($min+1);
$g2 = log($max+1);

$lst = "";
for ($i=0; $i<$count; $i++){
	$tag = $tags->GetByIndex($i);

	$n1 = ($fmin+log($tag->topicCount+1)-$g1)*$fmax;
	$n2 = $g2-$g1;
	$sz = intval($n1/$n2);
	
	$lst .= Brick::ReplaceVarByData($v['row'], array(
		"tagtl" => $tag->title,
		"urltag" => $tag->URL(),
		"sz" => $sz
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