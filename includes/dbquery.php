<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage 
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

/**
 * Запросы для приложения
 */
class BlogQueryApp {
	
	public static function CategoryList(Ab_Database $db){
		$sql = "
			SELECT 
				catid as id, 
				name as nm, 
				phrase as tl
			FROM ".$db->prefix."bg_cat
			WHERE language='".bkstr(Abricos::$LNG)."'
			ORDER BY tl
		";
		return $db->query_read($sql);
	}
	
	public static function TopicWhere($page, $limit, $topicid = -1){
		if ($topicid > 0){
			$limit = 1;
		}
		$from = $limit * (max($page, 1) - 1);
		return "
			WHERE 
				t.deldate=0 
				AND t.status=1 
				".($topicid > 0 ? " AND topicid=".bkint($topicid) : "")." 
				AND language='".bkstr(Abricos::$LNG)."'
			ORDER BY t.datepub DESC
			LIMIT ".$from.",".bkint($limit)."
		";
	}
	
	public static function TopicList(Ab_Database $db, $page, $limit, $topicid){
		$where = BlogQueryApp::TopicWhere($page, $limit, $topicid);
		
		$full = "";
		
		if ($topicid > 0) {
			$full = ", 
				cc.body as bd,
				'1' as isfull
			";			
		}
		
		$sql = "
			SELECT
				t.topicid as id, 
				t.catid as catid,
				cc.contentid as ctid,
				t.title as tl,
				t.intro as intro,
				length(cc.body) as bdlen,
				t.userid as uid,
				(
					SELECT count(cm.contentid) as cnt
					FROM ".$db->prefix."cmt_comment cm
					WHERE t.contentid = cm.contentid
					GROUP by cm.contentid
				) as cmt,
				t.datepub as dl
				".$full."
			FROM ".$db->prefix."bg_topic t
			LEFT JOIN ".$db->prefix."content cc ON t.contentid = cc.contentid
			".$where."
		";
		return $db->query_read($sql);
	}
	
	/**
	 * Таблица тегов для запрашиваемых записей в блоге
	 * 
	 * @param Ab_Database $db
	 * @param integer $page
	 * @param integer $limit
	 */
	public static function TagList(Ab_Database $db, $page, $limit, $topicid){
		$where = BlogQueryApp::TopicWhere($page, $limit, $topicid);
		
		$sql = "
			SELECT 
				DISTINCT
				g.tagid as id, 
				g.phrase as tl
			FROM ".$db->prefix."bg_toptag tg
			INNER JOIN (
				SELECT t.topicid
				FROM ".$db->prefix."bg_topic t
				".$where."
			) t ON tg.topicid = t.topicid
			INNER JOIN ".$db->prefix."bg_tag g ON tg.tagid = g.tagid
			WHERE language='".bkstr(Abricos::$LNG)."'
		";
		return $db->query_read($sql);
	}
	
	public static function TopicTagList(Ab_Database $db, $page, $limit, $topicid){
		$where = BlogQueryApp::TopicWhere($page, $limit, $topicid);
		
		$sql = "
			SELECT 
				tg.toptagid as id, 
				tg.tagid as gid,
				tg.topicid as tid
			FROM ".$db->prefix."bg_toptag tg
			INNER JOIN (
				SELECT t.topicid
				FROM ".$db->prefix."bg_topic t
				".$where."
			) t ON tg.topicid = t.topicid
		";
		return $db->query_read($sql);
	}
	
	public static function TopicUserList(Ab_Database $db, $page, $limit, $topicid){
		$where = BlogQueryApp::TopicWhere($page, $limit, $topicid);
		
		$sql = "
			SELECT
				DISTINCT
				u.userid as id,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm,
				u.avatar as avt
			FROM ".$db->prefix."bg_topic t
			LEFT JOIN ".$db->prefix."user u ON t.userid = u.userid
			".$where."
		";
		return $db->query_read($sql);
	}
}

class BlogQuery {
	
	private static function GetPageWhere(Ab_Database $db, $category, $tagid, $from, $count){
		$where = "";
		$lj = "";
		if (!empty($category)){
			$where = " AND b.name='".bkstr($category)."'";
		}else if(!empty($tagid)){
			$lj = "LEFT JOIN ".$db->prefix."bg_toptag t ON t.topicid = a.topicid ";
			$where = " AND t.tagid='".bkstr($tagid)."'";
		}
		return array(
			$lj,
			"WHERE a.language='".bkstr(Abricos::$LNG)."' AND a.deldate = 0 and a.status = 1 ".$where, 
			"LIMIT ".$from.",".bkint($count)
		);
	}
	
	public static function PageTopicCount(Ab_Database $db, $category, $tagid){
		$w = BlogQuery::GetPageWhere($db, $category, $tagid, 0, 0);
		$sql = "
			SELECT count(a.topicid) as cnt
			FROM ".$db->prefix."bg_topic a
			LEFT JOIN ".$db->prefix."bg_cat b ON a.catid = b.catid
			".$w[0]."
			".$w[1]."
			LIMIT 1
		";
		
		$row = $db->query_first($sql);
		return $row['cnt'];
	}
	
	public static function PageTopicIds(Ab_Database $db, $category, $tagid, $from, $count){
		$w = BlogQuery::GetPageWhere($db, $category, $tagid, $from, $count);
		if (!empty($category)){
			$w[0] = "LEFT JOIN ".$db->prefix."bg_cat b ON a.catid = b.catid";
		}
		$sql = "
			SELECT a.topicid as id
			FROM ".$db->prefix."bg_topic a
			".$w[0]."
			".$w[1]."
			ORDER BY a.datepub DESC
			".$w[2]."
		";
		return $db->query_read($sql);
	}
	
	public static function Page(Ab_Database $db, $category, $tagid, $from, $count){
		$w = BlogQuery::GetPageWhere($db, $category, $tagid, $from, $count);
		
		$modUProfile = Abricos::GetModule('uprofile');
		$uProfile = " '' as avt, '' as fnm, '' as lnm, ";
		if (!empty($modUProfile)){
			$uProfile = "
				c.avatar as avt,
				c.firstname as fnm,
				c.lastname as lnm,
			";
		}
		
		$sql = "
			SELECT
				a.topicid as id, 
				a.name as nm,
				a.title as tl,
				a.contentid as ctid,
				a.intro,
				length(cc.body) as lenbd,
				a.userid as uid,
				a.dateline as dl,
				a.dateedit as de,
				a.datepub as dp,
				a.status as st,
				a.deldate as dd, 
				b.catid as catid,
				b.phrase as catph,
				b.name as catnm,
				c.username as unm,
				".$uProfile."
				(
					SELECT count(cm.contentid) as cnt
					FROM ".$db->prefix."cmt_comment cm
					WHERE a.contentid = cm.contentid
					GROUP by a.contentid
				) as cmt
				
			FROM ".$db->prefix."bg_topic a
			LEFT JOIN ".$db->prefix."bg_cat b ON a.catid = b.catid
			LEFT JOIN ".$db->prefix."content cc ON a.contentid = cc.contentid
			LEFT JOIN ".$db->prefix."user c ON a.userid = c.userid
			".$w[0]."
			".$w[1]."
			ORDER BY a.datepub DESC
			".$w[2]."
		";
		return $db->query_read($sql);
	}
	
	public static function CategoryBlock(Ab_Database $db){
		$sql = "
			SELECT a.catid as id, a.name as nm, a.phrase as ph, count(b.catid) as cnt
			FROM ".$db->prefix."bg_cat a
			LEFT JOIN ".$db->prefix."bg_topic b ON a.catid = b.catid
			WHERE b.deldate = 0 and b.status = 1 AND a.language='".bkstr(Abricos::$LNG)."'
			GROUP BY b.catid
			ORDER BY cnt DESC
		";
		return $db->query_read($sql);
	}
	
	public static function CategoryById(Ab_Database $db, $categoryId, $retArray = true){
		$sql = "
			SELECT 
				a.catid as id,
				a.phrase as ph,
				a.name as nm
			FROM ".$db->prefix."bg_cat a
			WHERE a.catid='".bkstr($categoryId)."' AND a.language='".bkstr(Abricos::$LNG)."'
			LIMIT 1
		";
		if ($retArray){
			return $db->query_first($sql);
		}else{
			return $db->query_read($sql);
		}
	}
	
	public static function CategoryByName(Ab_Database $db, $category, $retArray = true){
		$sql = "
			SELECT *
			FROM ".$db->prefix."bg_cat a
			WHERE a.name='".bkstr($category)."' AND a.language='".bkstr(Abricos::$LNG)."'
			LIMIT 1
		";
		if ($retArray){
			return $db->query_first($sql);
		}else{
			return $db->query_read($sql);
		}
	}
	
	public static function CategoryCheck(Ab_Database $db, $data){
		$sql = "
			SELECT * 
			FROM ".$db->prefix."bg_cat
			WHERE name='".bkstr($data['name'])."' AND language='".bkstr(Abricos::$LNG)."'
		";
		return $db->query_first($sql);
	}
	
	public static function CategoryAppend(Ab_Database $db, $d){
		$sql = "
			INSERT INTO ".$db->prefix."bg_cat 
				(parentcatid, name, phrase, grouplist, language) VALUES
				(
					0,
					'".bkstr($d->nm)."',
					'".bkstr($d->ph)."',
					'".bkstr($d->gps)."',
					'".bkstr(Abricos::$LNG)."'
				)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function CategoryUpdate(Ab_Database $db, $d){
		$sql = "
			UPDATE ".$db->prefix."bg_cat 
			SET 
				name='".bkstr($d->nm)."',
				phrase='".bkstr($d->ph)."',
				grouplist='".bkstr($d->gps)."'
			WHERE catid=".bkint($d->id)."
		";
		$db->query_read($sql);
	}
	
	public static function CategoryRemove(Ab_Database $db, $catid){
		$sql = "
			DELETE FROM ".$db->prefix."bg_cat
			WHERE catid=".bkint($catid)." 
		";
		$db->query_write($sql);
	}

	public static function CategoryListCountTopic(Ab_Database $db){
		$sql = "
			SELECT catid as id, COUNT(*) as cnt
			FROM ".$db->prefix."bg_topic
			WHERE deldate=0 AND language='".bkstr(Abricos::$LNG)."'
			GROUP BY catid
		";
		return $db->query_read($sql);
	}
	
	public static function CategoryList(Ab_Database $db, $isadmin = false){
		$gps = "'' as gps,";
		if ($isadmin){
			$gps = "grouplist as gps,";
		}
		
		$sql = "
			SELECT 
				catid as id, 
				parentcatid as pid,
				".$gps." 
				name as nm, 
				phrase as ph
			FROM ".$db->prefix."bg_cat
			WHERE language='".bkstr(Abricos::$LNG)."'
			ORDER BY ph
		";
		
		return $db->query_read($sql);
	}
	
	public static function CommentLive(Ab_Database $db, $limit=5){
		$sql = "
			SELECT 
				c.contentid, 
				u.userid as uid, 
				u.username as unm, 
				c.body, 
				c.dateline as dl, 
				ct.phrase as catph, 
				ct.name as catnm,
				t.topicid, 
				t.title,
				a.cnt
			FROM (
				SELECT ap.contentid, max( ap.dateline ) AS dl, count(ap.contentid) as cnt
				FROM ".$db->prefix."cmt_comment ap
				INNER JOIN ".$db->prefix."bg_topic tp ON ap.contentid = tp.contentid
				WHERE tp.deldate = 0 and tp.status = 1 AND tp.language='".bkstr(Abricos::$LNG)."'
				GROUP BY contentid
				ORDER BY dl DESC
				LIMIT ".$limit."
			)a
			LEFT JOIN ".$db->prefix."cmt_comment c ON a.contentid = c.contentid AND c.dateline = a.dl
			LEFT JOIN ".$db->prefix."user u ON c.userid = u.userid
			LEFT JOIN ".$db->prefix."bg_topic t ON c.contentid = t.contentid
			LEFT JOIN ".$db->prefix."bg_cat ct ON t.catid = ct.catid
			WHERE t.deldate = 0 and t.status = 1 AND t.language='".bkstr(Abricos::$LNG)."'
			ORDER BY dl DESC  
		";
		return $db->query_read($sql);
	}

	public static function CommentTopicCount(Ab_Database $db, $ids){
		if (empty($ids)){
			return null;
		}
		$where = array();
		foreach ($ids as $id){
			array_push($where, "a.topicid=".bkint($id));
		}
		$sql = "
			SELECT count(a.contentid) as cnt, contentid
			FROM ".$db->prefix."cmt_comment a
			group by a.contentid
		";
		return $db->query_read($sql);
	}
	
	public static function TagAC(Ab_Database $db, $query){
		$sql = "
			SELECT phrase as ph
			FROM ".$db->prefix."bg_tag
			WHERE phrase LIKE '".$query."%' AND language='".bkstr(Abricos::$LNG)."'
			GROUP BY phrase
			ORDER BY phrase
		";
		return $db->query_read($sql);
	}
	
	public static function Tag(Ab_Database $db, $tagname){
		$sql = "
			SELECT *
			FROM ".$db->prefix."bg_tag
			WHERE name='".bkstr($tagname)."' AND language='".bkstr(Abricos::$LNG)."'
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function Tags(Ab_Database $db, $topicid){
		$sql = "
			SELECT b.tagid as id, b.phrase as ph, b.name as nm 
			FROM ".$db->prefix."bg_toptag a
			LEFT JOIN ".$db->prefix."bg_tag b ON a.tagid = b.tagid
			WHERE a.topicid=".bkint($topicid)."
		";
		return $db->query_read($sql);
	}
	
	public static function TagList(Ab_Database $db){
		$sql = "
			SELECT a.tagid as id, sum(a.cnt) as cnt, b.name AS nm, b.phrase AS ph
			FROM (
				SELECT tagid, count( tagid ) AS cnt
				FROM ".$db->prefix."bg_toptag
				GROUP BY tagid
				ORDER BY cnt DESC
			) a
			LEFT JOIN ".$db->prefix."bg_tag b ON b.tagid = a.tagid
			WHERE b.name != '' AND b.language='".bkstr(Abricos::$LNG)."'
			GROUP BY nm
			ORDER BY cnt DESC	
		";	 
		return $db->query_write($sql);
		
	}
	
	public static function TagBlock(Ab_Database $db, $limit = 30){
		$slimit = $limit == 0 ? "" : "LIMIT ".$limit;
		
		$sql = "
			SELECT a.tagid as id, sum(a.cnt) as cnt, b.name AS nm, b.phrase AS ph
			FROM (
				SELECT tagid, count( tagid ) AS cnt
				FROM ".$db->prefix."bg_toptag
				GROUP BY tagid
				ORDER BY cnt DESC
				".$slimit."
			) a
			LEFT JOIN ".$db->prefix."bg_tag b ON b.tagid = a.tagid
			WHERE b.name != '' AND b.language='".bkstr(Abricos::$LNG)."'
			GROUP BY nm
			ORDER BY ph	
		";	 
		return $db->query_write($sql);
	}
	
	public static function TagTopicList(Ab_Database $db, $ids){
		if (empty($ids)){
			return null;
		}
		$where = array();
		foreach ($ids as $id){
			array_push($where, "a.topicid=".bkint($id));
		}
		$sql = "
			SELECT a.topicid, a.tagid, b.name, b.phrase
			FROM ".$db->prefix."bg_toptag a
			LEFT JOIN ".$db->prefix."bg_tag b ON a.tagid = b.tagid
			WHERE (".implode(" OR ", $where).") AND b.name != '' AND b.language='".bkstr(Abricos::$LNG)."'
		";
		return $db->query_read($sql);
	}
	
	public static function TagUpdate(Ab_Database $db, $topicid, &$tags){
		$sql = "
			DELETE FROM ".$db->prefix."bg_toptag
			WHERE topicid=".$topicid."
		";
		$db->query_write($sql);
		
		foreach ($tags as $t => $v){
			$sql = "
				INSERT INTO ".$db->prefix."bg_toptag
				(topicid, tagid) VALUES
				('".bkstr($topicid)."','".bkstr($v['id'])."')
			";
			$db->query_write($sql);
		}
	}
	
	public static function TagSetId(Ab_Database $db, &$tags){
		if (empty($tags)){
			return;
		}
		$where = array();
		foreach ($tags as $t => $v){
			array_push($where, "phrase='".bkstr($v['phrase'])."'");
		}
		$sql = "
			SELECT tagid, phrase
			FROM ".$db->prefix."bg_tag
			WHERE ".implode(' OR ', $where)." AND language='".bkstr(Abricos::$LNG)."'
		";
		$rows = $db->query_read($sql);
		while (($row = $db->fetch_array($rows))){
			$key = $row['phrase'];
			if (!empty($tags[$key])){
				$tags[$row['phrase']]['id'] = $row['tagid'];
			}
		}
		foreach ($tags as $t => &$v){
			if (!empty($v['id'])){
				continue;
			}
			$sql = "
				INSERT INTO ".$db->prefix."bg_tag
				(name, phrase, language) VALUES
				('".bkstr($v['name'])."','".bkstr($v['phrase'])."', '".bkstr(Abricos::$LNG)."')
			";
			$db->query_write($sql);
			$tags[$t]['id'] = $db->insert_id();
		}
	}
	
	
	public static function TopicRecycleClear(Ab_Database $db, $userid){
		$sql = "
			SELECT contentid
			FROM ".$db->prefix."bg_topic
			WHERE userid=".$userid." AND deldate>0
		";
		$rows = $db->query_read($sql);
		$where = array();
		
		while (($row = $db->fetch_array($rows))){
			array_push($where, "contentid=".bkint($row['contentid']));
		}
		if (count($where) == 0){
			return;
		}
		$sql = "
			DELETE FROM ".$db->prefix."content
			WHERE ".implode(" OR ", $where)."
		";
		$db->query_write($sql);
		
		$sql = "
			DELETE FROM ".$db->prefix."bg_topic
			WHERE userid=".bkint($userid)." AND deldate>0
		";
		$db->query_write($sql);
	}
	
	public static function TopicPublish(Ab_Database $db, $topicid){
		$sql = "
			UPDATE ".$db->prefix."bg_topic
			SET datepub=".TIMENOW.", status=1
			WHERE topicid=".bkint($topicid)." AND status=0
		";
		$db->query_write($sql);
	}
	
	public static function TopicRestore(Ab_Database $db, $topicid){
		$info = BlogQuery::TopicInfo($db, $topicid);
		Ab_CoreQuery::ContentRestore($db, $info['contentid']);
		
		$sql = "
			UPDATE ".$db->prefix."bg_topic
			SET deldate=0
			WHERE topicid=".bkint($topicid)."
		";
		$db->query_write($sql);
	}
	
	public static function TopicRemove(Ab_Database $db, $topicid){
		
		$info = BlogQuery::TopicInfo($db, $topicid);
		Ab_CoreQuery::ContentRemove($db, $info['contentid']);
		
		$sql = "
			UPDATE ".$db->prefix."bg_topic
			SET deldate=".TIMENOW."
			WHERE topicid=".bkint($topicid)."
		";
		$db->query_write($sql);
	}
	
	public static function TopicInfo(Ab_Database $db, $topicid, $contentid = 0){
		if ($contentid > 0){
			$where = "WHERE contentid=".bkint($contentid);
		}else{
			$where = "WHERE topicid=".bkint($topicid);
		}
		$sql = "
			SELECT 
				a.topicid, 
				a.contentid, 
				a.userid, 
				a.status, 
				a.datepub, a.catid, a.title, d.phrase as catph, d.name as catnm
			FROM ".$db->prefix."bg_topic a
			LEFT JOIN ".$db->prefix."bg_cat d ON a.catid = d.catid
			".$where."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	
	public static function Topic(Ab_Database $db, $topicid){
		
		$modUProfile = Abricos::GetModule('uprofile');
		$uProfile = " '' as avt, '' as fnm, '' as lnm, ";
		if (!empty($modUProfile)){
			$uProfile = "
				c.avatar as avt,
				c.firstname as fnm,
				c.lastname as lnm,
			";
		}
				
		$sql = "
			SELECT
				a.topicid as id, 
				a.metadesc as mtd,
				a.metakeys as mtk,
				a.name as nm,
				a.title as tl,
				a.catid as catid,
				d.phrase as catph,
				d.name as catnm,
				a.contentid as ctid,
				a.intro,
				b.body,
				a.userid as uid,
				c.username as unm,
				".$uProfile."
				a.dateline as dl,
				a.dateedit as de,
				a.datepub as dp,
				a.status as st,
				a.deldate as dd
			FROM ".$db->prefix."bg_topic a
			LEFT JOIN ".$db->prefix."content b ON a.contentid = b.contentid
			LEFT JOIN ".$db->prefix."user c ON a.userid = c.userid
			LEFT JOIN ".$db->prefix."bg_cat d ON a.catid = d.catid
			WHERE a.topicid=".bkint($topicid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	private static function TopicListWhereByUserId($userid, $showRecycle = false){
		$where = array();
		if (!empty($userid)){
			array_push($where, "a.userid=".bkint($userid));
		}
		if ($showRecycle){
			array_push($where, "a.deldate=0");
		}
		
		$swhere = implode(" AND ", $where);
		if (!empty($swhere)){
			$swhere = "WHERE a.language='".bkstr(Abricos::$LNG)."' AND ".$swhere;
		}
		return $swhere;
	}
	
	public static function TopicCountByUserId(Ab_Database $db, $userid, $showRecycle = false){
		$swhere = BlogQuery::TopicListWhereByUserId($userid, $showRecycle);
		$sql = "
			SELECT count(topicid) as cnt
			FROM ".$db->prefix."bg_topic a
			".$swhere."
		";
		$row = $db->query_first($sql);
		return $row['cnt'];
	}
	
	public static function TopicListByUserId(Ab_Database $db, $userid, $page, $total, $showRecycle = false){
		$swhere = BlogQuery::TopicListWhereByUserId($userid, $showRecycle);
		$from = (($page-1)*10);
		$sql = "
			SELECT 
				a.topicid as id,
				a.title as tl,
				b.phrase as cat,
				b.name as catnm,
				a.userid as uid,
				a.dateline as dl,
				a.dateedit as de,
				a.datepub as dp,
				a.status as st,
				a.deldate as dd,
				u.userid as uid,
				u.username as unm
			FROM ".$db->prefix."bg_topic a
			LEFT JOIN ".$db->prefix."bg_cat b ON a.catid = b.catid
			LEFT JOIN ".$db->prefix."user u ON a.userid = u.userid
			".$swhere."
			ORDER BY dl DESC 
			LIMIT ".$from.",".bkint($total)."
		";
		return $db->query_read($sql);
	}
	
	public static function TopicAppend(Ab_Database $db, $obj){
		$contentid = Ab_CoreQuery::CreateContent($db, $obj->body, 'blog');
		
		$sql = "
			INSERT INTO ".$db->prefix."bg_topic
			(metadesc, metakeys, name, title, catid, intro, contentid, userid, dateline, dateedit, datepub, status, language) VALUES
			(
				'".bkstr($obj->mtd)."',
				'".bkstr($obj->mtk)."',
				'".bkstr($obj->nm)."',
				'".bkstr($obj->tl)."',
				'".bkint($obj->catid)."',
				'".bkstr($obj->intro)."',
				".bkint($contentid).",
				".bkint($obj->uid).",
				".bkint($obj->dl).",
				".bkint($obj->de).",
				".bkint($obj->dp).",
				".bkint($obj->st).",
				'".bkstr(Abricos::$LNG)."'
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}

	public static function TopicUpdate(Ab_Database $db, $obj){
		Ab_CoreQuery::ContentUpdate($db, $obj->cid, $obj->body);
		
		$sql = "
			UPDATE ".$db->prefix."bg_topic SET
				name='".bkstr($obj->nm)."',
				title='".bkstr($obj->tl)."',
				catid='".bkint($obj->catid)."',
				intro='".bkstr($obj->intro)."',
				dateedit=".bkint($obj->de).",
				metadesc='".bkstr($obj->mtd)."',
				metakeys='".bkstr($obj->mtk)."'
			WHERE topicid=".bkint($obj->id)."
		";
		$db->query_write($sql);
	}
	
	public static function SubscribeTopic(Ab_Database $db){
		$sql = "
			SELECT t.*,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm,
				c.name as catname,
				c.phrase as cattitle,
				c.grouplist
			FROM ".$db->prefix."bg_topic t
			INNER JOIN ".$db->prefix."user u ON t.userid = u.userid
			INNER JOIN ".$db->prefix."bg_cat c ON t.catid=c.catid
			WHERE t.scbcomplete=0 AND t.deldate=0 AND t.status=1
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function SubscribeTopicUpdate(Ab_Database $db, $topicid, $lastUserid){
		$sql = "
			UPDATE ".$db->prefix."bg_topic 
			SET scblastuserid=".bkint($lastUserid)."
			WHERE topicid=".bkint($topicid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function SubscribeTopicComplete(Ab_Database $db, $topicid){
		$sql = "
			UPDATE ".$db->prefix."bg_topic
			SET scbcomplete=1
			WHERE topicid=".bkint($topicid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function SubscribeUserList(Ab_Database $db, $catid, $gps, $lastUserId, $limit = 100){
		if (count($gps) == 0){ return; }
		
		$aw = array();
		for ($i=0; $i<count($gps); $i++){
			array_push($aw, "g.groupid=".bkint($gps[$i]));
		}
		$sql = "
			SELECT DISTINCT
				u.userid as id,
				u.username as unm,
				u.lastname as lnm,
				u.firstname as fnm,
				u.email as eml,
				IF ((s.pubkey IS NULL), '', s.pubkey) as pubkey,
				IF ((s.scboff IS NULL), 0, s.scboff) as scboff,
				IF ((s2.userid IS NULL), 0, 1) as scboffall,
				IF ((s.scbcustom IS NULL), 0, s.scbcustom) as scbcustom
				
			FROM ".$db->prefix."usergroup ug
			INNER JOIN ".$db->prefix."group g ON g.groupid = ug.groupid
			INNER JOIN ".$db->prefix."user u ON ug.userid = u.userid
			LEFT JOIN ".$db->prefix."bg_scbblog s ON u.userid=s.userid AND s.catid=".bkint($catid)."
			LEFT JOIN ".$db->prefix."bg_scbunset s2 ON u.userid=s2.userid
			WHERE u.email <> '' AND (".implode(" OR ", $aw).") AND u.userid>".bkint($lastUserId)."
			ORDER BY u.userid
			LIMIT ".bkint($limit)."
		";
		return $db->query_read($sql);
	}
	
	
	public static function SubscribeUserOnBlog(Ab_Database $db, $catid, $userid, $pubkey, $custom = 0){
		$sql = "
			INSERT INTO ".$db->prefix."bg_scbblog
				(catid, userid, pubkey, scbcustom) VALUES (
				".bkint($catid).",
				".bkint($userid).",
				'".bkstr($pubkey)."',
				".bkint($custom)."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function UnSubscribeBlog(Ab_Database $db, $userid, $pubkey){
		$sql = "
			UPDATE ".$db->prefix."bg_scbblog
			SET scboff=1
			WHERE userid=".bkint($userid)." AND pubkey='".bkstr($pubkey)."'
		";
		$db->query_write($sql);
	}
	
	public static function SubscribeBlogInfo(Ab_Database $db, $userid, $pubkey){
		$sql = "
			SELECT *
			FROM ".$db->prefix."bg_scbblog
			WHERE userid=".bkint($userid)." AND pubkey='".bkstr($pubkey)."'
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function UnSunbscribeAllBlog(Ab_Database $db, $userid){
		$sql = "
			INSERT IGNORE INTO ".$db->prefix."bg_scbunset
				(userid, dateline) VALUES (
				".bkint($userid).",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	
}

?>