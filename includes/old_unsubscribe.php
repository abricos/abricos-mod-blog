<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$brick = Brick::$builder->brick;

$mod = Abricos::GetModule('blog');
$manager = BlogManager::$instance;

$adress = Abricos::$adress;

$userid = $adress->dir[2];
$pubkey = $adress->dir[3];

$info = BlogQuery::SubscribeBlogInfo(Abricos::$db, $userid, $pubkey);
$v = &$brick->param->var;

if (empty($info)){
	$v['result'] = $v['errid'];
}else{

	if ($adress->dir[4] == 'all'){
		$v['result'] = $v['unsetall'];
		BlogQuery::UnSunbscribeAllBlog(Abricos::$db, $userid);
	}else{
		$cat = BlogQuery::CategoryById(Abricos::$db, $info['catid'], true);
		$v['result'] = Brick::ReplaceVarByData($v['unsetblog'], array(
			'blog'=> $cat['ph']
		));
		BlogQuery::UnSubscribeBlog(Abricos::$db, $userid, $pubkey);
	}
}

 


?>