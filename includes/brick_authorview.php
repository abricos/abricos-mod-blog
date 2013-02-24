<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$v = &$brick->param->var;

$man = BlogModule::$instance->GetManager();
$pa = BlogModule::$instance->ParserAddress();


$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'uid' => $pa->author->id,
	'unm' => $pa->author->GetUserName(),
	'topics' => $pa->author->topicCount,
	'avatar' => $pa->author->Avatar90()
));

?>