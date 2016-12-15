<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'old_dbquery.php';

/**
 * Class BlogQuery
 */
class BlogQuery {

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

    public static function BlogUserRole(Ab_Database $db, $blogid, $userid = 0){
        if ($userid === 0){
            $userid = Abricos::$user->id;
        }
        if ($userid > 0){
            $sql = "
                SELECT *
                FROM ".$db->prefix."blog_userRole
                WHERE blogid=".intval($blogid)." 
                    AND userid=".intval($userid)."
                LIMIT 1
            ";
            $d = $db->query_first($sql);
        }

        if (empty($d)){
            return array(
                'blogid' => $blogid,
                'userid' => $userid
            );
        }

        return $d;
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

    /**
     * @param Ab_Database $db
     * @param BlogUserRole $userRole
     */
    public static function BlogJoinLeaveUpdate(Ab_Database $db, $userRole){
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

    public static function BlogMemberCountUpdate(Ab_Database $db, $blogid = 0){
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

    /*********************************************************/
    /*                         Topic                         */
    /*********************************************************/

    public static function Topic(Ab_Database $db, $topicid){
        $sql = "
			SELECT t.*
			FROM ".$db->prefix."blog_topic t
			INNER JOIN ".$db->prefix."blog b ON b.blogid=t.blogid AND b.deldate=0
			WHERE t.topicid=".intval($topicid)." AND t.deldate=0
                AND (
                    t.isDraft=0
                    OR (t.isDraft=1 AND t.userid=".intval(Abricos::$user->id).")
                )
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function TopicList(Ab_Database $db, BlogTopicListOptions $options, $isCount = false){
        $vars = $options->vars;
        $newPeriod = TIMENOW - 60 * 60 * 24;

        $innerTable = "";
        $where = '';
        switch ($vars->type){
            case 'index':
                break;
            case 'public':
                $where = " AND b.blogType='public'";
                break;
            case 'personal':
                $where = " AND b.blogType='personal'";
                break;
        }
        if ($vars->blogid > 0){
            $where .= " AND t.blogid=".intval($vars->blogid);
        }
        if ($vars->userid > 0){
            $where .= " AND t.userid=".intval($vars->userid);
        }
        if (!empty($vars->tag)){
            $innerTable .= "
                INNER JOIN ".$db->prefix."blog_tagInTopic tt ON t.topicid=tt.topicid 
                INNER JOIN ".$db->prefix."blog_tag tg ON tg.tagid=tt.tagid
            ";
            $where = " AND tg.title='".bkstr($vars->tag)."'";
        }
        if ($vars->onlyNew){
            $where .= " AND t.pubdate>".$newPeriod;
        }

        $from = $vars->limit * ($vars->page - 1);
        $limit = "LIMIT ".$from.",".bkint($vars->limit)."";

        $fields = "t.*";
        if ($isCount){
            $fields = "count(t.topicid) as cnt";
            $limit = "LIMIT 1";
        }

        $sql = "
			SELECT ".$fields."
			FROM ".$db->prefix."blog_topic t
			INNER JOIN ".$db->prefix."blog b ON b.blogid=t.blogid AND b.deldate=0
			".$innerTable."
			WHERE t.deldate=0 AND t.isDraft=0
			    ".$where."
			ORDER BY t.pubdate DESC
			".$limit."
		";

        if ($isCount){
            $row = $db->query_first($sql);
            return intval($row['topicCount']);
        }

        return $db->query_read($sql);
    }

    public static function TagInTopicList(Ab_Database $db, $topicids){
        $count = count($topicids);
        if ($count === 0){
            return null;
        }
        $wha = array();
        for ($i = 0; $i < $count; $i++){
            $wha[] = "topicid=".bkint($topicids[$i]);
        }

        $sql = "
			SELECT *
			FROM ".$db->prefix."blog_tagInTopic
			WHERE ".implode(" OR ", $wha)."
			ORDER BY topicid
		";
        return $db->query_read($sql);
    }

    public static function TagList(Ab_Database $db, $topicids){
        if (is_integer($topicids)){
            $topicids = array($topicids);
        }
        $count = count($topicids);
        if ($count === 0){
            return null;
        }
        $wha = array();
        for ($i = 0; $i < $count; $i++){
            $wha[] = "ti.topicid=".bkint($topicids[$i]);
        }

        $sql = "
			SELECT DISTINCT t.*
			FROM ".$db->prefix."blog_tagInTopic ti
			INNER JOIN ".$db->prefix."blog_tag t ON t.tagid=ti.tagid
			WHERE ".implode(" OR ", $wha)."
			ORDER BY topicid
		";
        return $db->query_read($sql);
    }


}
