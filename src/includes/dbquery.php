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
			SELECT *
			FROM ".$db->prefix."blog
			WHERE blogid=".intval($blogid)." AND deldate=0
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function BlogList(Ab_Database $db){
        $sql = "
			SELECT *
			FROM ".$db->prefix."blog
			WHERE deldate=0
			ORDER BY topicCount DESC
		";
        return $db->query_read($sql);
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
}
