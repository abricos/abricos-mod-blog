<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogQuery_Tag
 */
class BlogQuery_Tag {

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


}