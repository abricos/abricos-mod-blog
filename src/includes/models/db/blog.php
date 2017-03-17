<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogQuery_Blog
 */
class BlogQuery_Blog {

    public static function Blog(Ab_Database $db, $blogid){
        $sql = "
			SELECT *, 0 as isEasyData
			FROM ".$db->prefix."blog
			WHERE blogid=".intval($blogid)." AND deldate=0
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function BlogList(Ab_Database $db){
        $sql = "
			SELECT 
			    blogid,
			    userid,
			    blogType,
			    title,
			    slug,
			    topicCount,
			    memberCount,
			    1 as isEasyData
			FROM ".$db->prefix."blog
			WHERE deldate=0
			ORDER BY topicCount DESC
		";
        return $db->query_read($sql);
    }

    /**
     * @param Ab_Database $db
     * @param BlogSave $blogSave
     * @return int
     */
    public static function BlogAppend(Ab_Database $db, $blogSave){
        $vars = $blogSave->vars;
        $sql = "
			INSERT INTO ".$db->prefix."blog
			(userid, blogType, title, slug, descript, newTopicUserRep, dateline, upddate) VALUES (
				".bkint(Abricos::$user->id).",
				'".bkstr($vars->type)."',
				'".bkstr($vars->title)."',
				'".bkstr($vars->slug)."',
				'".bkstr($vars->descript)."',
				".bkint($vars->newTopicUserRep).",
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
        $vars = $blogSave->vars;
        $sql = "
			UPDATE ".$db->prefix."blog
			SET
			    title='".bkstr($vars->title)."', 
			    slug='".bkstr($vars->slug)."',
			    descript='".bkstr($vars->descript)."', 
			    newTopicUserRep=".bkint($vars->newTopicUserRep).", 
			    upddate=".intval(TIMENOW)."
			WHERE blogid=".bkint($vars->blogid)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function BlogUserRoleList(Ab_Database $db, $blogids, $userid = 0){
        if ($userid === 0){
            $userid = Abricos::$user->id;
        }
        $count = count($blogids);
        if ($userid === 0 || $count === 0){
            return null;
        }

        $wha = array();
        for ($i = 0; $i < $count; $i++){
            $wha[] = "blogid=".intval($blogids[$i]);
        }

        $sql = "
            SELECT *
            FROM ".$db->prefix."blog_userRole
            WHERE userid=".intval($userid)." 
                AND (".implode(" OR ", $wha).") 
        ";
        return $db->query_write($sql);
    }

}