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
$options = $module->router->topicListOptions;

/** @var BlogApp $app */
$app = Abricos::GetApp('blog');

$topicList = $app->TopicList($options);

if (AbricosResponse::IsError($topicList)){
    $brick->content = "";
    return;
}
$modSocialist = Abricos::GetModule('socialist');
if (!empty($modSocialist)){
    $modSocialist->GetManager();
}

$count = $topicList->Count();

/** @var URatingApp $uratingApp */
$uratingApp = Abricos::GetApp('urating');

$lst = "";
for ($i = 0; $i < $count; $i++){

    $topic = $topicList->GetByIndex($i);
    $blog = $topic->blog;

    $vote = "";
    if (!empty($uratingApp)){
        $vote = $uratingApp->VotingHTML($topic->voting);
    }
    $socialTpl = "";
    if (!empty($modSocialist)){
        $socialTpl = SocialistManager::$instance->LikeLineHTML(array(
            "uri" => $topic->url,
            "title" => $topic->title
        ));
    }

    $atags = array();
    for ($ti = 0; $ti < count($topic->tags); $ti++){
        array_push($atags, Brick::ReplaceVarByData($v['tagrow'], array(
            "tl" => $topic->tags[$ti]->title,
            "url" => $topic->tags[$ti]->url
        )));
    }

    $commentStat = $topic->commentStatistic;

    $lst .= Brick::ReplaceVarByData($v['row'], array(
        "cattl" => $blog->title,
        "urlcat" => $blog->url,
        "socialist" => $socialTpl,

        "toptl" => $topic->title,
        "urltop" => $topic->url,
        "urlcmt" => $topic->url,
        "cmtcnt" => empty($commentStat) ? 0 : $commentStat->count,
        "date" => rusDateTime($topic->pubdate),
        "taglist" => implode($v['tagdel'], $atags),

        "voting" => $vote,

        "intro" => $topic->intro,
        "readmore" => $topic->bodyLength == 0 ? "" : Brick::ReplaceVarByData($v['readmore'], array(
            "urltop" => $topic->url
        )),
        "userURL" => $topic->user->URL(),
        "uid" => $topic->user->id,
        "userViewName" => $topic->user->GetViewName(),
        "avatar" => $topic->user->GetAvatar24()
    ));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    'rows' => $lst,
));

Brick::$builder->LoadBrickS('sitemap', 'paginator', $brick, array(
    "p" => array(
        "total" => $topicList->total,
        "page" => $options->vars->page,
        "perpage" => 10,
        "uri" => $module->router->topicListURL
    )
));

$phraseKey = $options->vars->type;
if ($options->vars->page > 1){
    $phraseKey .= 'Page';
}
if ($options->vars->onlyNew){
    $phraseKey .= 'New';
}

$i18n = $module->I18n();
$phrase = $i18n->Translate('metaTitle.'.$phraseKey);

$metaTitle = SystemModule::$instance->GetPhrases()->Get('site_name');
if (!empty($phrase)){
    $metaTitle = $phrase." / ".$metaTitle;
}

$metaTitle = Brick::ReplaceVarByData($metaTitle, array(
    'page' => $options->vars->page
));

Brick::$builder->SetGlobalVar('meta_title', $metaTitle);

// отправить сообщения рассылки из очереди (подобие крона)
$app->SubscribeTopicCheck();
