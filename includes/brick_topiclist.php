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

$dir = Abricos::$adress->dir;

$mcur = ""; $mcurpub = ""; $mcurpers = "";
$f1 = ""; $f2 = "";
switch ($dir[1]){
	case "new":
		$mcur = "current";
		$f2 = "new";
		break;
	case "pub":
		$mcurpub = "current";
		$f1 = $dir[1];
		break;
	case "pers":
		$mcurpers = "current";
		$f1 = $dir[1];
		break;
	default:
		$mcur = "current";
		break;
}
$filter = $f1;
if ($dir[2] == "new"){
	$filter .= "/new";
}

$lst = "";
$topics = $man->TopicList(array(
	"limit" => 10,
	"filter" => $filter
));

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'submenu' => Brick::ReplaceVarByData($v['submenu'.$f1], array(
		"newcnt" => $topics->totalNew>0 ? "+".$topics->totalNew : "",
		"f1sel" => empty($f2) ? "sel" : "",
		"f2sel" => empty($f2) ? "" : "sel"
	)),
	"curr" => $mcur,
	"currpub" => $mcurpub,
	"currpers" => $mcurpers
));

$count = $topics->Count();
for ($i=0; $i<$count; $i++){
	
	$topic = $topics->GetByIndex($i);
	$cat = $cats->Get($topic->catid);

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
			
		"toptl" => $topic->title,
		"urltop" => $topic->URL(),
		"urlcmt" => $topic->URL(),
		"cmtcnt" => $topic->commentCount,
		"date"	=> rusDateTime($topic->publicDate),
		"taglist" => implode($v['tagdel'], $atags),
		
		"intro"	=> $topic->intro,
		"readmore" => $topic->bodyLength == 0 ? "" : Brick::ReplaceVarByData($v['readmore'], array(
			"urlview" => $topic->URL()
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

Brick::$builder->LoadBrickS('sitemap', 'paginator', $brick, array("p" => array(
	"total" => $topicCount,
	"page" => $page,
	"perpage" => $count,
	"uri" => $baseUrl
)));

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