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
$contentId = Abricos::CleanGPC('g', 'contentid', TYPE_INT);
$brick->param->var['cid'] = $contentId;

require_once 'dbquery.php';

$info = BlogQuery::TopicInfo(Abricos::$db, 0, $contentId);

if ($info['status'] != 1){
	return;
}

$topic = BlogQuery::Topic(Abricos::$db, $info['userid'], $info['topicid']);
if (empty($topic)){
	return;
}

$brick->param->var['tl'] = $topic['catph']." -> ".$topic['tl'];

?>