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

/** @var BlogApp $app */
$app = Abricos::GetApp('blog');
$options = $app->router->topicListOptions;

/** @var UProfileApp $uprofileApp */
$uprofileApp = Abricos::GetApp('uprofile');

$user = $uprofileApp->User($options->vars->userid);
if (AbricosResponse::IsError($user)){
    $brick->content = '';
    return;
}

$topicList = $app->TopicList($options);

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    'uid' => $user->id,
    'viewName' => $user->GetViewName(),
    'profileURL' => $user->URL(),
    'topics' => $topicList->total,
    'avatar' => $user->GetAvatar90()
));

$meta_title = $module->I18n()->Translate('metaTitle.author');
$meta_title = Brick::ReplaceVarByData($meta_title, array(
    'username' => $user->username,
    'viewName' => $user->GetViewName()
));
$meta_title .= " / ".SystemModule::$instance->GetPhrases()->Get('site_name');

Brick::$builder->SetGlobalVar('meta_title', $meta_title);
