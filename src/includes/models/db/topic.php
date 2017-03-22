<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogQuery_Topic
 */
class BlogQuery_Topic {

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

    /**
     * @param Ab_Database $db
     * @param BlogTopicList $list
     * @param bool $isTotal
     * @return array|int|null
     */
    public static function TopicList(Ab_Database $db, $list, $isTotal = false){
        $args = $list->GetArgs();
        $newPeriod = TIMENOW - 60 * 60 * 24;

        $innerTable = "";
        $where = '';
        switch ($args->type){
            case 'index':
                break;
            case 'public':
                $where = " AND b.blogType='public'";
                break;
            case 'personal':
                $where = " AND b.blogType='personal'";
                break;
        }
        if ($args->blogid > 0){
            $where .= " AND t.blogid=".intval($args->blogid);
        }
        if ($args->blogSlug !== ''){
            $where .= " AND b.slug='".bkstr($args->blogSlug)."'";
        }
        if ($args->userid > 0){
            $where .= " AND t.userid=".intval($args->userid);
        }
        if ($args->tag !== ''){
            $innerTable .= "
                INNER JOIN ".$db->prefix."blog_tagInTopic tt ON t.topicid=tt.topicid 
                INNER JOIN ".$db->prefix."blog_tag tg ON tg.tagid=tt.tagid
            ";
            $where = " AND tg.title='".bkstr($args->tag)."'";
        }
        if ($args->onlyNew){
            $where .= " AND t.pubdate>".$newPeriod;
        }

        $from = $args->limit * ($args->page - 1);
        $limit = "LIMIT ".$from.",".bkint($args->limit)."";

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

        if ($args->idsUse){
            $ids = explode(',', $args->ids);
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
}