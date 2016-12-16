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

$topicList = $app->CommentLiveList(array('limit' => 5));

if (AbricosResponse::IsError($topicList)){
    $brick->content = "";
    return;
}

$lst = "";
$count = $topicList->Count();
for ($i = 0; $i < $count; $i++){
    $topic = $topicList->GetByIndex($i);
    $blog = $topic->blog;
    $commentStat  = $topic->commentStatistic;
    $user  = $commentStat->lastUser;

    $lst .= Brick::ReplaceVarByData($brick->param->var['row'], array(
        "urlusr" => $user->URL(),
        "username" => $user->username,
        "userViewName" => $user->GetViewName(),
        "cattl" => $blog->title,
        // "urlcat" => $cat->URL(),
        "toptl" => $topic->title,
        // "urltop" => $topic->URL(),
        // "urlcmt" => $topic->URL(),
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
