<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogQuery_BlogAction
 */
class BlogQuery_BlogAction {

    /**
     * @param Ab_Database $db
     * @param BlogSave $blogSave
     * @return int
     */
    public static function BlogAppend(Ab_Database $db, $blogSave){
        $args = $blogSave->GetArgs();
        $sql = "
			INSERT INTO ".$db->prefix."blog
			(userid, blogType, title, slug, descript, newTopicUserRep, dateline, upddate) VALUES (
				".bkint(Abricos::$user->id).",
				'".bkstr($args->type)."',
				'".bkstr($args->title)."',
				'".bkstr($args->slug)."',
				'".bkstr($args->descript)."',
				".bkint($args->newTopicUserRep).",
				".TIMENOW.",
				".TIMENOW."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    /**
     * @param Ab_Database $db
     * @param BlogSave $blogSave
     */
    public static function BlogUpdate(Ab_Database $db, $blogSave){
        $args = $blogSave->GetArgs();
        $sql = "
			UPDATE ".$db->prefix."blog
			SET
			    title='".bkstr($args->title)."', 
			    slug='".bkstr($args->slug)."',
			    descript='".bkstr($args->descript)."', 
			    newTopicUserRep=".bkint($args->newTopicUserRep).", 
			    upddate=".intval(TIMENOW)."
			WHERE blogid=".bkint($args->blogid)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    /**
     * @param Ab_Database $db
     * @param BlogUserRole $userRole
     */
    public static function SubscribeUpdate(Ab_Database $db, $userRole){
        $sql = "
            INSERT INTO ".$db->prefix."blog_userRole
            (blogid, userid, isMember, pubKey, dateline, upddate) VALUES (
                ".intval($userRole->blogid).",
                ".intval($userRole->userid).",
                ".intval($userRole->isMember).",
                '".bkstr($userRole->pubKey)."',
                ".intval(TIMENOW).",
                ".intval(TIMENOW)."
            ) ON DUPLICATE KEY UPDATE
                isMember=".intval($userRole->isMember).",
                upddate=".intval(TIMENOW)."
        ";
        $db->query_write($sql);
    }

    public static function MemberCountUpdate(Ab_Database $db, $blogid = 0){
        $sql = "
			UPDATE ".$db->prefix."blog b
			SET b.memberCount = IFNULL((
				SELECT count(*)
				FROM ".$db->prefix."blog_userRole ur
				WHERE b.blogid=ur.blogid AND ur.isMember
				GROUP BY ur.blogid
			), 0)
		";
        if ($blogid > 0){
            $sql .= "
				WHERE b.blogid=".bkint($blogid)."
			";
        }
        $db->query_write($sql);
    }
}