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

$author = $pa->author;

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    'uid' => $author->id,
    'unm' => $author->GetUserName(),
    'urlusr' => $author->URL(),
    'topics' => $author->topicCount,
    'avatar' => $author->Avatar90()
));

if (!empty($pa->pageTitle)){
    $meta_title = $pa->pageTitle." / ".SystemModule::$instance->GetPhrases()->Get('site_name');
    Brick::$builder->SetGlobalVar('meta_title', $meta_title);
}
