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
// $cats = $man->CategoryList();

$pa = BlogModule::$instance->ParserAddress();

if (empty($pa->topic)){
	$brick->content = "";
	return;
}

$topic = $pa->topic;
$cat = $topic->Category();

$vote = ""; $voteJSMan = "";
if (BlogManager::$isURating){
	Abricos::GetModule('urating')->GetManager();
	$voteBuilder = new URatingBuilder("blog", "topic", "topic.vote.error");
	$vote = $voteBuilder->BuildVote(array(
		"elid" => $topic->id,
		"vote" => $topic->voteMy,
		"value" =>$topic->rating
	));
	$voteJSMan = $voteBuilder->BuildJSMan();
}

$modSocialist = Abricos::GetModule('socialist');
if (!empty($modSocialist)){
	$modSocialist->GetManager();
}
$soclinetpl = "";
if (!empty($modSocialist)){
	$soclinetpl = SocialistManager::$instance->LikeLineHTML(array(
		"uri" => $topic->URL(),
		"title" => $topic->title
	));
}

$atags = array();
for ($ti=0;$ti<count($topic->tags);$ti++){
	array_push($atags,Brick::ReplaceVarByData($v['tagrow'], array(
		"tl" => $topic->tags[$ti]->title,
		"url" => $topic->tags[$ti]->URL()
	)));
}


$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"submenu" => $submenu,
	"cattl" => $cat->title,
	"urlcat" => $cat->URL(),
	"socialist" => $soclinetpl,
		
	"toptl" => $topic->title,
	"urltop" => $topic->URL(),
	"urlcmt" => $topic->URL(),
	"cmtcnt" => $topic->commentCount,
	"date"	=> rusDateTime($topic->publicDate),
	"taglist" => implode($v['tagdel'], $atags),
		
	"voting" => $vote,
	
	"urlusr" => $topic->user->URL(),
	"uid" => $topic->user->id,
	"unm" => $topic->user->GetUserName(),
	"avatar" => $topic->user->Avatar24(),

	"intro"	=> $topic->intro,
	"body" => $topic->body,
	'votejsman' => $voteJSMan
));

$meta_title = $topic->title." / ".$cat->title." / ".Brick::$builder->phrase->Get('sys', 'site_name');

Brick::$builder->SetGlobalVar('meta_title', $meta_title);

$man->TopicMetaTagBuild($topic);

Brick::$builder->SetGlobalVar('meta_keys', $topic->metakeys);
Brick::$builder->SetGlobalVar('meta_desc', $topic->metadesc);

?>