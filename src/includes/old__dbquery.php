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



    /*********************************************************/
    /*                         Topic                         */
    /*********************************************************/

    public static function Topic(Ab_Database $db, $topicid){
        $sql = "
			SELECT 
			    t.*,
			    IF(LENGTH(t.body)>10, 1, 0) as isBody
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

    public static function TopicList(Ab_Database $db, BlogTopicListOptions $options, $isTotal = false){
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
        if (!empty($vars->blogSlug)){
            $where .= " AND b.slug='".bkstr($vars->blogSlug)."'";
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

        $fields = "
            t.*,
            IF(LENGTH(t.body)>10, 1, 0) as isBody
        ";
        if ($isTotal){
            $fields = "
                SUM(1) as total,
                SUM(IF(t.pubdate>".$newPeriod.",1,0)) as totalNew
            ";
            $limit = "LIMIT 1";
        }

        if ($vars->idsUse){
            $ids = explode(',', $vars->ids);
            $count = min(count($ids), 100);
            if ($count === 0){
                return $isTotal ? 0 : null;
            }
            $idsSQL = array();
            for ($i = 0; $i < $count; $i++){
                $idsSQL[] = "t.topicid=".intval($ids[$i]);
            }
            $where .= " AND (".implode(' OR ', $idsSQL).")";
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

        if ($isTotal){
            return $db->query_first($sql);
        }

        return $db->query_read($sql);
    }

    public static function TagList(Ab_Database $db, BlogTagListOptions $options){
        $sql = "
            SELECT ttag.*
            FROM (
                SELECT tag.*
                FROM ".$db->prefix."blog_tag tag
                WHERE tag.topicCount>0
                ORDER BY topicCount DESC
                LIMIT ".intval($options->vars->limit)."
            ) ttag
            ORDER BY title
		";
        return $db->query_read($sql);
    }

    public static function TagInTopicList(Ab_Database $db, $topicids){
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
			SELECT DISTINCT tag.*
			FROM ".$db->prefix."blog_tagInTopic ti
			INNER JOIN ".$db->prefix."blog_tag tag ON tag.tagid=ti.tagid
			WHERE ".implode(" OR ", $wha)."
			ORDER BY title
		";
        return $db->query_read($sql);
    }

    public static function CommentLiveList(Ab_Database $db, BlogTopicListOptions $options){
        $sql = "
            SELECT t.topicid, oo.lastCommentDate
			FROM (
                SELECT o.ownerid, o.lastCommentDate
                FROM ".$db->prefix."comment_ownerstat o
                WHERE o.ownerModule='blog' AND o.ownerType='topic'
			    ORDER BY o.lastCommentDate DESC
			) oo
            INNER JOIN ".$db->prefix."blog_topic t ON oo.ownerid=t.topicid 
			INNER JOIN ".$db->prefix."blog b ON b.blogid=t.blogid 
			WHERE t.deldate=0 AND t.isDraft=0 AND b.deldate=0
            ORDER BY lastCommentDate DESC
			LIMIT ".bkint($options->vars->limit)."
		";
        return $db->query_read($sql);
    }
}
