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
