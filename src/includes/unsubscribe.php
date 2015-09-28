<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;

$mod = Abricos::GetModule('blog');
$man = BlogManager::$instance;

$pa = BlogModule::$instance->ParserAddress();

$userid = $pa->usbUserId;
$pubkey = $pa->usbKey;

$info = BlogTopicQuery::CategoryUserRoleByPubKey(Abricos::$db, $userid, $pubkey);
if (empty($info)){
    sleep(1);
    return;
}

$v = &$brick->param->var;

if (empty($info)){
    $v['result'] = $v['errid'];
} else {

    if ($pa->usbCatId == 'all'){
        $v['result'] = $v['unsetall'];
        BlogTopicQuery::UnSunbscribeAllBlog(Abricos::$db, $userid);
    } else {

        $cat = $man->Category($info['catid']);

        $v['result'] = Brick::ReplaceVarByData($v['unsetblog'], array(
            'blog' => $cat->title
        ));
        BlogTopicQuery::UnSubscribeCategory(Abricos::$db, $userid, $pubkey);
    }
}


?>