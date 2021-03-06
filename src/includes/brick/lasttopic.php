<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;

/** @var BlogApp $app */
$app = Abricos::GetApp('blog');

$lst = "";
$topics = $app->TopicList(array("limit" => 5));

if (empty($topics)){
    $brick->content = "";
    return;
}

$count = $topics->Count();
for ($i = 0; $i < $count; $i++){
    $topic = $topics->GetByIndex($i);
    $cat = $topic->Category();

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
