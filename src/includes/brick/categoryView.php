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

$pa = $module->ParserAddress();
$f = explode("/", $pa->topicListFilter);

$isNew = isset($f[2]) && $f[2] == 'new';

/** @var BlogApp $app */
$app = Abricos::GetApp('blog');

$cats = $app->CategoryList();
$cat = $cats->GetByName($dir[1]);

/** @var URatingApp $uratingApp */
$uratingApp = Abricos::GetApp('urating');
$voting = "";
if (!empty($uratingApp) && !empty($cat->voting)){
    $voting = $uratingApp->VotingHTML($cat->voting);
}

$topics = $pa->topicList;

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    'title' => $cat->title,
    "voting" => $voting,
    'mbrs' => $cat->memberCount,
    'topics' => $cat->topicCount,
));
