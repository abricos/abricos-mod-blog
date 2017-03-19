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

$mcur = "";
$mcurpub = "";
$mcurpers = "";
switch ($options->vars->type){
    case BlogApp::BLOG_TYPE_PUBLIC:
        $mcurpub = "active";
        break;
    case BlogApp::BLOG_TYPE_PERSONAL:
        $mcurpers = "active";
        break;
    default:
        $mcur = "active";
        break;
}

$lst = "";

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    'submenu' => Brick::ReplaceVarByData($v[$options->vars->type."Item"], array(
        "newcnt" => $topicList->totalNew > 0 ? "+".$topicList->totalNew : "",
        "f1sel" => !$options->vars->onlyNew ? "active" : "",
        "f2sel" => !$options->vars->onlyNew ? "" : "active",
        "liwr" => $app->IsWriteRole() ? $v['managerItem'] : ''
    )),
    "curr" => $mcur,
    "currpub" => $mcurpub,
    "currpers" => $mcurpers
));
