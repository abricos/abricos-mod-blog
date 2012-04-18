<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Blog
 * @copyright Copyright (C) 2008 Abricos All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$brick = Brick::$builder->brick;
$in = Brick::$input;

$adress = Abricos::$adress;
$category = "";
$mod = Abricos::GetModule('blog');


$page = $mod->page;
$category = $mod->category;
$tag = $mod->tag;
$tagid = 0;
$baseUrl = "/".$mod->takelink."/";
require_once 'dbquery.php';

$lst = "";
$title = "";
if (!empty($category)){
	$baseUrl .= $category."/";
	$catInfo = BlogQuery::CategoryByName(Abricos::$db, $category); 
	$title = $catInfo['phrase'];
}else if (!empty($tag)){
	$baseUrl .= $tag."/";
	$taginfo = BlogQuery::Tag(Abricos::$db, $tag);
	$title = $taginfo['phrase'];
	$tagid = $taginfo['tagid'];
	
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

$rows = BlogQuery::Page(Abricos::$db, $category, $tagid, $from, $count);
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
	
	$t = Brick::ReplaceVarByData($brick->param->var['th'], array(
		"subj" => $row['tl'],
		"catlink" => $lcat,
		"subjlink" => $ltop,
		"cat" => $row['catph'],
		"intro" => $row['intro'],
		"tags" => $taglist,
		"date" => rusDateTime(intval($row['dp'])),
		"user" => $row['unm'],
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

?>