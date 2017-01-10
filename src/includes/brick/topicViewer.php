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

/** @var BlogModule $module */
$module = Abricos::GetModule('blog');


$struct = $module->GetStructure('Blog');
print_r($struct->ToJSON());

$struct = $module->GetStructure('Topic');
print_r($struct->ToJSON());


exit;


$options = $module->router->options;

if (!isset($options['topicid'])){
    $brick->content = "";
    return;
}

/** @var BlogApp $app */
$app = Abricos::GetApp('blog');

$topic = $app->Topic($options['topicid']);

if (AbricosResponse::IsError($topic)){
    $brick->content = "";
    return;
}

$votingHTML = "";
if (!empty($topic->voting)){
    /** @var URatingApp $uratingApp */
    $uratingApp = Abricos::GetApp('urating');
    $votingHTML = $uratingApp->VotingHTML($topic->voting);
}

$socialistHTML = "";

/** @var SocialistModule $socialistModule */
$socialistModule = Abricos::GetModule('socialist');
if (!empty($socialistModule)){
    $socialistHTML = $socialistModule->GetManager()->LikeLineHTML(array(
        "uri" => $topic->url,
        "title" => $topic->title
    ));
}

$metaKeys = array();
$tags = array();

$count = $topic->tagList->Count();
for ($ti = 0; $ti < $count; $ti++){
    $tag = $topic->tagList->GetByIndex($ti);
    $tags[] = Brick::ReplaceVarByData($v['tagrow'], array(
        "tl" => $tag->title,
        "url" => $tag->url
    ));
    $metaKeys[] = $tag->title;
}

$blog = $topic->blog;
$user = $topic->user;

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "cattl" => $blog->title,
    "urlcat" => $blog->url,
    "socialist" => $socialistHTML,

    "toptl" => $topic->title,
    "urltop" => $topic->url,
    "urlcmt" => $topic->url,
    "cmtcnt" => !empty($topic->commentStatistic) ? $topic->commentStatistic->count : 0,
    "date" => rusDateTime($topic->pubdate),
    "taglist" => implode($v['tagdel'], $tags),

    "voting" => $votingHTML,

    "userURL" => $user->URL(),
    "uid" => $user->id,
    "userViewName" => $user->GetViewName(),
    "avatar" => $user->GetAvatar24(),

    "intro" => $topic->intro,
    "body" => $topic->body
));

Brick::$builder->LoadBrickS('comment', 'tree', $brick, array(
    "p" => array(
        "module" => 'blog',
        "type" => 'topic',
        "ownerid" => $topic->id
    )
));

$meta_title = $topic->title." / ".$blog->title." / ".SystemModule::$instance->GetPhrases()->Get('site_name');

Brick::$builder->SetGlobalVar('meta_title', $meta_title);
Brick::$builder->SetGlobalVar('meta_keys', implode(", ", $metaKeys));
Brick::$builder->SetGlobalVar('meta_desc', $topic->metaDesc);
