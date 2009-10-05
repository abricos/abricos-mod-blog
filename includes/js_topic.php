<?php
/**
 * @version $Id: js_topic.php 784 2009-05-05 10:31:57Z AKuzmin $
 * @package CMSBrick
 * @copyright Copyright (C) 2008 CMSBrick. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

if (!Brick::$session->IsAdminMode()){ return; }

$brick = Brick::$builder->brick;

$json = Brick::$input->clean_gpc('p', 'json', TYPE_STR);
$obj = json_decode($json);

$info = CMSQBlog::TopicInfo(Brick::$db, $obj->id);
$user = Brick::$session->userinfo;

if (!Brick::$session->IsAdminMode() && $info['userid'] != $user['userid']){
	$brick->content = "alert('Access denied!');";
	return;
}
$obj->uid = $info['userid'];
$topic = CMSQBlog::Topic(Brick::$db, $obj);

$rows = CMSQBlog::Tags(Brick::$db, $obj->id);
$tags = array();
while (($row = Brick::$db->fetch_array($rows))){
	array_push($tags, $row);
}
$topic['tags'] = $tags;


$brick->param->var['obj'] = json_encode_ext($topic);


?>