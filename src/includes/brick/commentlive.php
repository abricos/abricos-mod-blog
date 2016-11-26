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

$comms = $app->CommentLiveList();

if (empty($comms)){
    $brick->content = "";
    return;
}

$lst = "";
$count = $comms->Count();
for ($i = 0; $i < $count; $i++){
    /** @var BlogCommentLive $comm */
    $comm = $comms->GetByIndex($i);

    $topic = $comm->topic;
    $cat = $topic->Category();

    $lst .= Brick::ReplaceVarByData($brick->param->var['row'], array(
        "urlusr" => $topic->user->URL(),
        "uid" => $topic->user->id,
        "username" => $topic->user->username,
        "cattl" => $cat->title,
        "urlcat" => $cat->URL(),
        "toptl" => $topic->title,
        "urltop" => $topic->URL(),
        "urlcmt" => $topic->URL(),
        "cmtcnt" => $topic->commentStatistic->count
    ));
}

if (empty($lst)){
    $brick->content = "";
    return;
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    'rows' => $lst
));
