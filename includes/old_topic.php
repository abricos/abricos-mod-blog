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

$mod = Abricos::GetModule('blog');
$topicid = $mod->topicid;
$mod->GetManager();

$modUProfile = Abricos::GetModule('uprofile');
$isUProfileExist = !empty($modUProfile);

$modSocialist = Abricos::GetModule('socialist');
if (!empty($modSocialist)){
	$modSocialist->GetManager();
}

$topic = BlogManager::$instance->Topic($topicid);

$title = $topic['tl'] .' - '. $topic['catph'] ;
Brick::$builder->SetGlobalVar("page_title", $topic['mtd']);

$lcat = "/blog/".$topic['catnm']."/";
$ltop = $lcat.$topic['id']."/";

$tdata = array();

$tags = BlogQuery::Tags(Abricos::$db, $topicid);
$ttags = array();
while (($tag = Abricos::$db->fetch_array($tags))){
	array_push($ttags, Brick::ReplaceVarByData($brick->param->var['tag'], array(
		"link" => $tag['nm'],
		"tag" => $tag['ph']
	)));
}

$usertpl = Brick::ReplaceVarByData($brick->param->var['user'], array(
	"avtsrc" => (empty($topic['avt']) ?
			'/modules/uprofile/images/nofoto24.gif' :
			'/filemanager/i/'.$topic['avt'].'/w_24-h_24/avatar.gif'),
	"usrsrc" => ($isUProfileExist ? '/uprofile/#app=uprofile/ws/showws/{v#userid}/' : '#'),
	"userid" => $topic['uid'],
	"unm" => (!empty($topic['fnm']) && !empty($topic['lnm']) ? $topic['fnm']." ".$topic['lnm'] : $topic['unm'])
));

$soclinetpl = "";
if (!empty($modSocialist)){
	$soclinetpl = SocialistManager::$instance->LikeLineHTML(array(
		"uri" => $ltop,
		"title" => $topic['tl']
	));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"user" => $usertpl,
	"userid" => $row['uid'],
	"socialist" => $soclinetpl,
		
	'catlink' => $lcat,
	'cat' => $topic['catph'],
	'subj' => $topic['tl'],
	'subjlink' => $ltop,
			
	'date' => rusDateTime(intval($topic['dp'])),
	'date_m3' => rusMonth(intval($topic['dp']), true),
	'date_d' => date("d", intval($topic['dp'])),
	
	'intro' => $topic['intro'],
	'body' => $topic['body'],
	'tags' => implode(", ", $ttags) 
));

// отправить сообщения рассылки из очереди (подобие крона)
BlogManager::$instance->SubscribeTopicCheck();

$meta_title = $topic['tl']." / ".Brick::$builder->phrase->Get('sys', 'site_name');

Brick::$builder->SetGlobalVar('meta_title', $meta_title);

?>