<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$v = &$brick->param->var;

/** @var BlogApp $app */
$app = Abricos::GetApp('blog');

$blogList = $app->BlogList();

if (empty($cats)){
    $brick->content = "";
    return;
}

$count = min($blogList->Count(), 10);

$lst = "";
for ($i = 0; $i < $count; $i++){
    $blog = $blogList->GetByIndex($i);

    $lst .= Brick::ReplaceVarByData($v['row'], array(
        "cattl" => $blog->title,
        "urlcat" => $blog->url,
        "topicCount" => $blog->topicCount
    ));
}


if (empty($lst)){
    $brick->content = "";
    return;
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "rows" => $lst
));
