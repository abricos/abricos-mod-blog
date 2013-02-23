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

if (BlogManager::$isURating){
	Abricos::GetModule('urating')->GetManager();
}


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
for ($i=0; $i<$count; $i++){
	
	$topic = $topics->GetByIndex($i);
	$cat = $topic->Category();
	
	$vote = "";
	if (BlogManager::$isURating){
		$vote = URatingManager::$instance->VoteBrick(array(
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

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'rows' => $lst
));

Brick::$builder->LoadBrickS('sitemap', 'paginator', $brick, array("p" => array(
	"total" => $topics->total,
	"page" => $pa->page,
	"perpage" => 10,
	"uri" => $pa->uri
)));


/*

$adress = Abricos::$adress;
$category = "";
$mod = Abricos::GetModule('blog');
$mod->GetManager();

$modUProfile = Abricos::GetModule('uprofile');
$isUProfileExist = !empty($modUProfile);

$modSocialist = Abricos::GetModule('socialist');
if (!empty($modSocialist)){
	$modSocialist->GetManager();
}

$page = $mod->page;
$category = $mod->category;
$tag = $mod->tag;
$tagid = 0;
$baseUrl = "/".$mod->takelink."/";
require_once 'dbquery.php';

$site_name = Brick::$builder->phrase->Get('sys', 'site_name');

$lst = "";
$title = "";
if (!empty($category)){
	$baseUrl .= $category."/";
	$catInfo = BlogQuery::CategoryByName(Abricos::$db, $category); 
	$title = $catInfo['phrase'];
	
	Brick::$builder->SetGlobalVar('meta_title', $title." / ".$site_name);
	$lst = Brick::ReplaceVar($brick->param->var['h1'], "c", $taginfo['phrase']);
	
}else if (!empty($tag)){
	$baseUrl .= $tag."/";
	$taginfo = BlogQuery::Tag(Abricos::$db, $tag);
	$title = $taginfo['phrase'];
	$tagid = $taginfo['tagid'];
	
	Brick::$builder->SetGlobalVar('meta_title', $title." / ".$site_name);
	$lst = Brick::ReplaceVar($brick->param->var['h1'], "c", $taginfo['phrase']);
}

Brick::$builder->SetGlobalVar("page_title", $title);

$topicCount = BlogQuery::PageTopicCount(Abricos::$db, $category, $tagid);

$count = 8;
$from = ($page-1)*$count;
$ids = array();
$rows = BlogQuery::PageTopicIds(Abricos::$db, $category, $tagid, $from, $count);
while (($row = Abricos::$db->fetch_array($rows))){
	array_push($ids, $row['id']);
}
$rows = BlogQuery::TagTopicList(Abricos::$db, $ids);
$tags = array();
while (($row = Abricos::$db->fetch_array($rows))){
	array_push($tags, $row);
}

$rows = BlogManager::$instance->Page($category, $tagid, $from, $count);

$ctids = array();
while (($row = Abricos::$db->fetch_array($rows))){
	array_push($ctids, $row['ctid']);
	$lcat = "/blog/".$row['catnm']."/";
	$ltop = $lcat.$row['id']."/";

	$ttags = array();
	foreach ($tags as $tag){
		if ($tag['topicid'] == $row['id']){
			array_push($ttags, Brick::ReplaceVarByData($brick->param->var['tag'], array(
				"link" => $tag['name'],
				"tag" => $tag['phrase']
			)));
		}
	}
	$taglist = implode(", ", $ttags);
	
	$more = "";
	if ($row['lenbd']>20){
		$more = Brick::ReplaceVarByData($brick->param->var['more'], array(
			"id" => $row['id'],
			"ltop" => $ltop
		));
	}
	$usertpl = Brick::ReplaceVarByData($brick->param->var['user'], array(
		"avtsrc" => (empty($row['avt']) ? 
				'/modules/uprofile/images/nofoto24.gif' : 
				'/filemanager/i/'.$row['avt'].'/w_24-h_24/avatar.gif'),
		"usrsrc" => ($isUProfileExist ? '/uprofile/#app=uprofile/ws/showws/{v#userid}/' : '#'),
		"userid" => $row['uid'],
		"unm" => (!empty($row['fnm']) && !empty($row['lnm']) ? $row['fnm']." ".$row['lnm'] : $row['unm'])
	));
	$soclinetpl = "";
	if (!empty($modSocialist)){
		$soclinetpl = SocialistManager::$instance->LikeLineHTML(array(
			"uri" => $ltop,
			"title" => $row['tl']
		));
	}
	
	$t = Brick::ReplaceVarByData($brick->param->var['th'], array(
		"user" => $usertpl,
		"userid" => $row['uid'],
		"socialist" => $soclinetpl,
		
		"subj" => $row['tl'],
		"catlink" => $lcat,
		"subjlink" => $ltop,
		"cat" => $row['catph'],
		"intro" => $row['intro'],
		"tags" => $taglist,
		"date" => rusDateTime(intval($row['dp'])),
		"ctid" => $row['ctid'],
		"cmt" => intval($row['cmt']),
		"body" => $more
	));
	
	$lst .=  $brick->param->var['tb'].$t.$brick->param->var['te'];
}

$scrpt = str_replace('#clst#', implode(',', $ctids), $brick->param->var['ts']);
$scrpt = str_replace('#tlst#', implode(',', $ids), $scrpt);

$brick->content = Brick::ReplaceVar($brick->content, "result", 
	$brick->param->var['bt'].
	$lst . $scrpt.
	$brick->param->var['et']
);

$brick->param->var = array();

// отправить сообщения рассылки из очереди (подобие крона)
BlogManager::$instance->SubscribeTopicCheck();

/**/
?>