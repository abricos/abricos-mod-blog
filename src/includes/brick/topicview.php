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

$man = BlogModule::$instance->GetManager();
// $cats = $man->CategoryList();

$pa = BlogModule::$instance->ParserAddress();

if (empty($pa->topic)){
    $brick->content = "";
    return;
}

$topic = $pa->topic;
$cat = $topic->Category();

$vote = "";
$voteJSMan = "";

/** @var URatingApp $uratingApp */
$uratingApp = Abricos::GetApp('urating');
if (!empty($uratingApp)){
    $vote = $uratingApp->VotingHTML($topic->voting);
}

$modSocialist = Abricos::GetModule('socialist');
if (!empty($modSocialist)){
    $modSocialist->GetManager();
}
$soclinetpl = "";
if (!empty($modSocialist)){
    $soclinetpl = SocialistManager::$instance->LikeLineHTML(array(
        "uri" => $topic->URL(),
        "title" => $topic->title
    ));
}

$atags = array();
for ($ti = 0; $ti < count($topic->tags); $ti++){
    array_push($atags, Brick::ReplaceVarByData($v['tagrow'], array(
        "tl" => $topic->tags[$ti]->title,
        "url" => $topic->tags[$ti]->URL()
    )));
}


$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "cattl" => $cat->title,
    "urlcat" => $cat->URL(),
    "socialist" => $soclinetpl,

    "toptl" => $topic->title,
    "urltop" => $topic->URL(),
    "urlcmt" => $topic->URL(),
    "cmtcnt" => !empty($topic->commentStatistic) ? $topic->commentStatistic->count : 0,
    "date" => rusDateTime($topic->publicDate),
    "taglist" => implode($v['tagdel'], $atags),

    "voting" => $vote,

    "urlusr" => $topic->user->URL(),
    "uid" => $topic->user->id,
    "unm" => $topic->user->GetUserName(),
    "avatar" => $topic->user->Avatar24(),

    "intro" => $topic->intro,
    "body" => $topic->body,
    'votejsman' => $voteJSMan
));

Brick::$builder->LoadBrickS('comment', 'tree', $brick, array(
    "p" => array(
        "module" => 'blog',
        "type" => 'topic',
        "ownerid" => $topic->id
    )
));


$meta_title = $topic->title." / ".$cat->title." / ".SystemModule::$instance->GetPhrases()->Get('site_name');

Brick::$builder->SetGlobalVar('meta_title', $meta_title);

$man->GetApp()->TopicMetaTagBuild($topic);

Brick::$builder->SetGlobalVar('meta_keys', $topic->metakeys);
Brick::$builder->SetGlobalVar('meta_desc', $topic->metadesc);
