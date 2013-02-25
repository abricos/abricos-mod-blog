<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$v = &$brick->param->var;

$pa = BlogModule::$instance->ParserAddress();

$f = explode("/", $pa->topicListFilter);
$isNew = $f[1] == 'new';

$liwr = "";
$mcur = ""; $mcurpub = ""; $mcurpers = "";
switch ($f[0]){
	case "pub":
		$mcurpub = "current";
		break;
	case "pers":
		$mcurpers = "current";
		break;
	default:
		$mcur = "current";
		if (BlogManager::$instance->IsWriteRole()){
			$liwr = $v['submenuindexwr'];
		}
		break;
}

$lst = "";
$topics = $pa->topicList ;

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'submenu' => Brick::ReplaceVarByData($v['submenu'.$f[0]], array(
		"newcnt" => $topics->totalNew>0 ? "+".$topics->totalNew : "",
		"f1sel" => !$isNew ? "sel" : "",
		"f2sel" => !$isNew ? "" : "sel",
		"liwr" => $liwr
	)),
	"curr" => $mcur,
	"currpub" => $mcurpub,
	"currpers" => $mcurpers
));

?>