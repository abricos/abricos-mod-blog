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
$limit = 30;

require_once 'dbquery.php';

$rows = BlogQuery::CommentOnlineList(Brick::$db, 5);
$mod = Brick::$modules->GetModule('blog');
$baseUrl = "/".$mod->takelink."/";

$p_do = Brick::$input->clean_gpc('g', 'do', TYPE_STR);
$upd = $p_do == 'updcmtonl';

$lst = "";
// $cids = array();
while (($row = Brick::$db->fetch_array($rows))){
	
	/* fixed bug */
	if (empty($row['catph']) || empty($row['title'])){
		continue;
	}
	$lst .= Brick::ReplaceVarByData($brick->param->var['row'], array(
		"unm" => $row['unm'],
		"catnm" => $row['catph'],
		"topnm" => $row['title'], 
		"catlnk" => $baseUrl.$row['catnm']."/",
		"toplnk" => $baseUrl.$row['catnm']."/".$row['topicid']."/",
		"cid" => $row['contentid'],
		"count" => $row['cnt']
	));
	/*
	
	
	array_push($cids, $row['contentid']);
	$t = str_replace('#cnt#', , $brick->param->var['tcmt']);
	$t = str_replace('#cid#', , $t);
	$tt .= $t;

	$tt =  str_replace('#c#', $tt, $brick->param->var['ti']);
	
	$lst .= $tt;
	/**/
}
$brick->param->var['lst'] = $lst;

/*
$brick->param->var['s'] = str_replace('#ids#', implode(',', $cids), 
	$brick->param->var[($upd ? 's2' : 's')]
);

$lst = str_replace('#c#', $lst, $brick->param->var['t']);

if ($upd){
	$brick->content = $lst.$brick->param->var['s'];
}else{
	$brick->param->var['lst'] = $lst;
}

unset($brick->param->var['t']);
/**/
?>