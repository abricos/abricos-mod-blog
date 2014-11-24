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
$cats = $man->CategoryList();

$pa = BlogModule::$instance->ParserAddress();

$lst = "";
$topics = $pa->topicList;
if (empty($topics)){
	$brick->content = "";
	return;
}
$modSocialist = Abricos::GetModule('socialist');
if (!empty($modSocialist)){
	$modSocialist->GetManager();
}

$count = $topics->Count();

if (BlogManager::$isURating){
	Abricos::GetModule('urating')->GetManager();
	$voteBuilder = new URatingBuilder("blog", "topic", "topic.vote.error");
}

for ($i=0; $i<$count; $i++){
	
	$topic = $topics->GetByIndex($i);
	$cat = $topic->Category();
	
	$vote = "";
	if (BlogManager::$isURating){
		$vote = $voteBuilder->BuildVote(array(
			"elid" => $topic->id,
			"vote" => $topic->voteMy,
			"value" =>$topic->rating
		));
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
	
	$lst .= Brick::ReplaceVarByData($v['row'], array(
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
		
		"intro"	=> $topic->intro,
		"readmore" => $topic->bodyLength == 0 ? "" : Brick::ReplaceVarByData($v['readmore'], array(
			"urltop" => $topic->URL()
		)),
		"urlusr" => $topic->user->URL(),
		"uid" => $topic->user->id,
		"unm" => $topic->user->GetUserName(),
		"avatar" => $topic->user->Avatar24()
	));
}

$voteJSMan = "";
if (BlogManager::$isURating){
	$voteJSMan = $voteBuilder->BuildJSMan();
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'rows' => $lst,
	'votejsman' => $voteJSMan
));

Brick::$builder->LoadBrickS('sitemap', 'paginator', $brick, array("p" => array(
	"total" => $topics->total,
	"page" => $pa->page,
	"perpage" => 10,
	"uri" => $pa->uri
)));

if (!empty($pa->pageTitle)){
	$meta_title = $pa->pageTitle." / ".SystemModule::$instance->GetPhrases()->Get('site_name');
	Brick::$builder->SetGlobalVar('meta_title', $meta_title);
}

// отправить сообщения рассылки из очереди (подобие крона)
BlogManager::$instance->SubscribeTopicCheck();

?>