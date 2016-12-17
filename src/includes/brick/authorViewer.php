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
$pa = $module->ParserAddress();

$author = $pa->author;

/** @var UProfileApp $uprofileApp */
$uprofileApp = Abricos::GetApp('uprofile');
$user = $uprofileApp->User($author->id);
if (AbricosResponse::IsError($user)){
    $brick->content = '';
    return;
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    'uid' => $user->id,
    'viewName' => $user->GetViewName(),
    'profileURL'=>$user->URL(),
    'topics' => $author->topicCount,
    'avatar' => $user->GetAvatar90()
));

if (!empty($pa->pageTitle)){
    $meta_title = $pa->pageTitle." / ".SystemModule::$instance->GetPhrases()->Get('site_name');
    Brick::$builder->SetGlobalVar('meta_title', $meta_title);
}
