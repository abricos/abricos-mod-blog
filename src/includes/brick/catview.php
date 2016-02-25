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
$pa = BlogModule::$instance->ParserAddress();
$f = explode("/", $pa->topicListFilter);

$isNew = isset($f[2]) && $f[2] == 'new';

$cats = $man->CategoryList();

if (BlogManager::$isURating){
    Abricos::GetModule('urating')->GetManager();
}

$dir = Abricos::$adress->dir;

$cat = $cats->GetByName($dir[1]);

$vote = "";
$voteJSMan = "";
if (BlogManager::$isURating){
    Abricos::GetModule('urating')->GetManager();
    $voteBuilder = new URatingBuilder("blog", "cat", "cat.vote.error");
    $vote = $voteBuilder->BuildVote(array(
        "elid" => $cat->id,
        "vote" => $cat->voteMy,
        "value" => $cat->rating
    ));
    $voteJSMan = $voteBuilder->BuildJSMan();
}

$topics = $pa->topicList;

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    'tl' => $cat->title,
    "catname" => $cat->name,
    "voting" => $vote,
    'votejsman' => $voteJSMan,
    'mbrs' => $cat->memberCount,
    'topics' => $cat->topicCount,
    "newcnt" => $topics->totalNew > 0 ? "+".$topics->totalNew : "",
    "f1sel" => !$isNew ? "sel" : "",
    "f2sel" => !$isNew ? "" : "sel"
));

?>