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

$mod = Brick::$modules->GetModule('blog');
$topicid = $mod->topicid;
$manager = $mod->GetManager();

$topic = $manager->Topic($topicid);

$title = $topic['tl'] .' - '. $topic['catph'] ;
Brick::$builder->SetGlobalVar("page_title", $topic['mtd']);

$lcat = "/blog/".$topic['catnm']."/";
$ltop = $lcat.$topic['id']."/";

$tdata = array();

$tags = BlogQuery::Tags(Brick::$db, $topicid);
$ttags = array();
while (($tag = Brick::$db->fetch_array($tags))){
	array_push($ttags, Brick::ReplaceVarByData($brick->param->var['tag'], array(
		"link" => $tag['nm'],
		"tag" => $tag['ph']
	)));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	'catlink' => $lcat,
	'cat' => $topic['catph'],
	'subj' => $topic['tl'],
	'subjlink' => $ltop,
	'user' => $topic['unm'],
	
	'date' => rusDateTime(intval($topic['dp'])),
	'date_m3' => rusMonth(intval($topic['dp']), true),
	'date_d' => date("d", intval($topic['dp'])),
	
	'intro' => $topic['intro'],
	'body' => $topic['body'],
	'tags' => implode(", ", $ttags) 
));

?>