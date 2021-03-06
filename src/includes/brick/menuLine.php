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

/** @var BlogModule $blogModule */
$blogModule = Abricos::GetModule('blog');
$blogManager = $blogModule->GetManager();

$pa = $blogModule->ParserAddress();

$f = explode("/", $pa->topicListFilter);
$f0 = isset($f[0]) ? $f[0] : "";
$f1 = isset($f[1]) ? $f[1] : "";

$isNew = $f1 == 'new';

$liwr = "";
$mcur = "";
$mcurpub = "";
$mcurpers = "";
switch ($f0){
    case "pub":
        $mcurpub = "active";
        break;
    case "pers":
        $mcurpers = "active";
        break;
    default:
        $mcur = "active";
        if ($blogManager->IsWriteRole()){
            $liwr = $v['submenuindexwr'];
        }
        break;
}

$lst = "";
$topics = $pa->topicList;

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    'submenu' => Brick::ReplaceVarByData($v['submenu'.$f0], array(
        "newcnt" => $topics->totalNew > 0 ? "+".$topics->totalNew : "",
        "f1sel" => !$isNew ? "active" : "",
        "f2sel" => !$isNew ? "" : "active",
        "liwr" => $liwr
    )),
    "curr" => $mcur,
    "currpub" => $mcurpub,
    "currpers" => $mcurpers
));
