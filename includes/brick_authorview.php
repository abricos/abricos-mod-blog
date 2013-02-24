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

$author = $pa->author;

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'uid' => $author->id,
	'unm' => $author->GetUserName(),
	'urlusr' => $author->URL(),
	'topics' => $author->topicCount,
	'avatar' => $author->Avatar90()
));

?>