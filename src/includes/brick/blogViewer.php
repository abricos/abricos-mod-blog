<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$dir = Abricos::$adress->dir;

$brick = Brick::$builder->brick;
$v = &$brick->param->var;

/** @var BlogModule $module */
$module = Abricos::GetModule('blog');

$options = $module->router->topicListOptions;

/** @var BlogApp $app */
$app = Abricos::GetApp('blog');

$blog = $app->BlogBySlug($options->vars->blogSlug);

if (AbricosResponse::IsError($blog)){
    $brick->content = "";
    return;
}

$votingHTML = "";
if (!empty($blog->voting)){
    /** @var URatingApp $uratingApp */
    $uratingApp = Abricos::GetApp('urating');
    $votingHTML = $uratingApp->VotingHTML($blog->voting);
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    'title' => $blog->title,
    "voting" => $votingHTML,
    'mbrs' => $blog->memberCount,
    'topics' => $blog->topicCount,
));
