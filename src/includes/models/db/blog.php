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