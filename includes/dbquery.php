<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class BlogTopicQuery {
	
	private static function TopicFields(Ab_Database $db){
		return "
			t.topicid as id,
			t.catid as catid,
			cc.contentid as ctid,
			t.title as tl,
			t.intro as intro,
			length(cc.body) as bdlen,
			
			t.rating as rtg,
			t.votecount as vcnt,
			
			t.userid as uid,
			u.username as unm,
			u.avatar as avt,
			u.firstname as fnm,
			u.lastname as lnm,
			(
				SELECT count(cm.contentid) as cnt
				FROM ".$db->prefix."cmt_comment cm
				WHERE t.contentid = cm.contentid
				GROUP by cm.contentid
			) as cmt,
			t.pubdate as dl
		";
	}
	
	private static function TopicRatingSQLExt(Ab_Database $db){
		$ret = new stdClass();
		$ret->fld = "";
		$ret->tbl = "";
		$userid = Abricos::$user->id;
		if (BlogManager::$isURating && $userid>0){
			$ret->fld .= "
				,IF(ISNULL(vt.userid), null, IF(vt.voteup>0, 1, IF(vt.votedown>0, -1, 0))) as vmy
			";
			$ret->tbl .= "
				LEFT JOIN ".$db->prefix."urating_vote vt
					ON vt.module='blog' AND vt.elementtype='topic'
					AND vt.elementid=t.topicid
					AND vt.userid=".bkint($userid)."
			";
		}
		return $ret;
	}
	
	/**
	 * Количество черновиков в профиле пользователя
	 *
	 * @param Ab_Database $db
	 * @param integer $userid
	 */
	public static function TopicDraftCountByUser(Ab_Database $db, $userid){
		$sql = "
			SELECT count(*) as cnt
			FROM ".$db->prefix."bg_topic
			WHERE userid=".bkint($userid)." AND isdraft=1
		";
		return $db->query_first($sql);
	}
	
	/**
	 * Количество опубликованных записей за текущие сутки
	 * @param Ab_Database $db
	 * @param integer $userid
	 */
	public static function TopicPublicCountByUser(Ab_Database $db, $userid){
		$day = 60*60*24;
		$t1 = intval(round(TIMENOW/$day)*$day);
		$sql = "
			SELECT count(*) as cnt
			FROM ".$db->prefix."bg_topic
			WHERE userid=".bkint($userid)." AND pubdate>".$t1."
		";
		return $db->query_first($sql);
	}	

	/**
	 * Запросить топик по идентификатору
	 * 
	 * Если указан параметр $contentid, то запрос происходит по нему
	 * 
	 * @param Ab_Database $db
	 * @param integer $topicid
	 * @param integer $contentid
	 */
	public static function Topic(Ab_Database $db, $topicid, $contentid = 0){
		$urt = BlogTopicQuery::TopicRatingSQLExt($db);
		
		$where = "t.topicid = ".bkint($topicid)."";
		if ($contentid > 0){
			$where = "t.contentid = ".bkint($contentid)."";
		}
		
		$userid = Abricos::$user->id;
		$sql = "
			SELECT
				".BlogTopicQuery::TopicFields($db).",
				cc.body as bd
				".$urt->fld."
			FROM ".$db->prefix."bg_topic t
			LEFT JOIN ".$db->prefix."bg_cat cat ON t.catid = cat.catid
			INNER JOIN ".$db->prefix."content cc ON t.contentid = cc.contentid
			INNER JOIN ".$db->prefix."user u ON t.userid = u.userid
			".$urt->tbl."
			WHERE ".$where." AND t.deldate=0 AND (cat.deldate=0 OR t.catid=0)
				AND (t.isdraft=0 OR (t.isdraft=1 AND t.userid=".bkint($userid)."))
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	

	public static function TopicList(Ab_Database $db, $page=1, $limit=10, $fType='index', $fPrm='', $isCount = false){
		$from = $limit * (max($page, 1) - 1);
		$urt = BlogTopicQuery::TopicRatingSQLExt($db);
		
		$newPeriod = TIMENOW-60*60*24;

		$filterRating = "";
		if (BlogManager::$isURating){
			$filterRating = " AND (t.rating >= 5 OR t.isindex=1)";
		}
		
		// print_r(array($fType, $fPrm, $isCount));
		$filter = '';
		if ($fType == "index"){ // главная
			if ($fPrm == "new"){
				$filter = " AND t.pubdate>".$newPeriod;
				$filterRating = "";
			}
		}else if ($fType == 'pub'){		// коллективные
			$filter = " AND t.catid>0";
			if ($fPrm == 'new'){
				$filter .= " AND t.pubdate>".$newPeriod;
				$filterRating = "";
			}
		}else if ($fType == 'pers'){		// персональные
			$filter = " AND t.catid=0";
			if ($fPrm == 'new'){
				$filter .= " AND t.pubdate>".$newPeriod;
				$filterRating = "";
			}
		}else if ($fType == 'cat'){
			$fa = explode("/", $fPrm);
			$filter = " AND t.catid=".bkint($fa[0]);
			
			if ($fa[1] == 'new'){
				$filter .= " AND t.pubdate>".$newPeriod;
				$filterRating = "";
			}

		}else if ($fType == 'tag'){
			$urt->tbl .= "
				INNER JOIN ".$db->prefix."bg_toptag tt ON t.topicid=tt.topicid 
				INNER JOIN ".$db->prefix."bg_tag tg ON tg.tagid=tt.tagid
			";
			$filter = " AND tg.title='".bkstr($fPrm)."'";
		}
		$filter .= $filterRating;
		
		$fld = "
			".BlogTopicQuery::TopicFields($db)."
			".$urt->fld."
		";
		$limit = "LIMIT ".$from.",".bkint($limit)."";
		
		if ($isCount){
			$fld = "count(t.topicid) as cnt";
			$limit = "LIMIT 1";
		}
				
		$sql = "
			SELECT ".$fld."
			FROM ".$db->prefix."bg_topic t
			LEFT JOIN ".$db->prefix."bg_cat cat ON t.catid = cat.catid
			INNER JOIN ".$db->prefix."content cc ON t.contentid = cc.contentid
			INNER JOIN ".$db->prefix."user u ON t.userid = u.userid
			".$urt->tbl."
			WHERE t.deldate=0 AND (cat.deldate=0 OR t.catid=0)
				AND t.isdraft=0 AND t.language='".bkstr(Abricos::$LNG)."'
				".$filter."
			ORDER BY t.pubdate DESC
			".$limit."
		";

		if ($isCount){
			$row = $db->query_first($sql);
			return intval($row['cnt']);
		}
		return $db->query_read($sql);
	}
	
	/**
	 * Список черновиков пользователя
	 * 
	 * @param Ab_Database $db
	 * @param integer $userid
	 * @param integer $page
	 * @param integer $limit
	 */
	public static function TopicDraftList(Ab_Database $db, $userid, $page=1, $limit=10){
		$from = $limit * (max($page, 1) - 1);
		$urt = BlogTopicQuery::TopicRatingSQLExt($db);

		$sql = "
			SELECT
				".BlogTopicQuery::TopicFields($db)."
				".$urt->fld."
			FROM ".$db->prefix."bg_topic t
			LEFT JOIN ".$db->prefix."bg_cat cat ON t.catid = cat.catid
			INNER JOIN ".$db->prefix."content cc ON t.contentid = cc.contentid
			INNER JOIN ".$db->prefix."user u ON t.userid = u.userid
			".$urt->tbl."
			WHERE t.userid=".bkint($userid)." AND t.isdraft=1 
				AND t.deldate=0  AND (cat.deldate=0 OR t.catid=0) 
				AND t.language='".bkstr(Abricos::$LNG)."'
			ORDER BY t.dateline DESC
			LIMIT ".$from.",".bkint($limit)."
		";
		return $db->query_read($sql);
	}
	
	/**
	 * Персональный блог имени <<пользователя>>
	 * 
	 * @param Ab_Database $db
	 * @param integer $userid
	 * @param integer $page
	 * @param integer $limit
	 */
	public static function TopicListByAuthor(Ab_Database $db, $userid, $page=1, $limit=10){
		$from = $limit * (max($page, 1) - 1);
		$urt = BlogTopicQuery::TopicRatingSQLExt($db);
	
		$sql = "
			SELECT
				".BlogTopicQuery::TopicFields($db)."
				".$urt->fld."
			FROM ".$db->prefix."bg_topic t
			LEFT JOIN ".$db->prefix."bg_cat cat ON t.catid = cat.catid
			INNER JOIN ".$db->prefix."content cc ON t.contentid = cc.contentid
			INNER JOIN ".$db->prefix."user u ON t.userid = u.userid
			".$urt->tbl."
			WHERE t.userid=".bkint($userid)." AND t.isdraft=0
				AND t.deldate=0  AND (cat.deldate=0 OR t.catid=0)
				AND t.language='".bkstr(Abricos::$LNG)."'
			ORDER BY t.dateline DESC
			LIMIT ".$from.",".bkint($limit)."
		";
		return $db->query_read($sql);
	}
	

	public static function TopicListByIds(Ab_Database $db, $ids){
		$awh = array();
		for ($i=0;$i<count($ids);$i++){
			array_push($awh, "t.topicid=".bkint($ids[$i]));
		}
		if (count($ids) == 0){
			return null;
		}
		$urt = BlogTopicQuery::TopicRatingSQLExt($db);
		
		$sql = "
			SELECT
				".BlogTopicQuery::TopicFields($db)."
				".$urt->fld."
			FROM ".$db->prefix."bg_topic t
			INNER JOIN ".$db->prefix."content cc ON t.contentid = cc.contentid
			INNER JOIN ".$db->prefix."user u ON t.userid = u.userid
			".$urt->tbl."
			WHERE ".implode(" OR ", $ids)."
		";
		return $db->query_read($sql);
	}
	
	public static function TopicAppend(Ab_Database $db, $userid, $d){
		$contentid = Ab_CoreQuery::CreateContent($db, $d->body, 'blog');
	
		$sql = "
			INSERT INTO ".$db->prefix."bg_topic
			(catid, userid, language, title, name, intro, contentid, isdraft, pubdate, dateline, upddate) VALUES (
				".bkint($d->catid).",
				".bkint($userid).",
				'".bkstr(Abricos::$LNG)."',
				'".bkstr($d->tl)."',
				'".bkstr($d->nm)."',
				'".bkstr($d->intro)."',
				'".bkstr($contentid)."',
				".($d->dft>0?1:0).",
				".bkint($d->pdt).",
				".TIMENOW.",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function TopicUpdate(Ab_Database $db, $topicid, $contentid, $d){
		Ab_CoreQuery::ContentUpdate($db, $contentid, $d->body);
	
		$sql = "
			UPDATE ".$db->prefix."bg_topic
			SET
				catid=".bkint($d->catid).",
				title='".bkstr($d->tl)."',
				name='".bkstr($d->nm)."',
				intro='".bkstr($d->intro)."',
				isdraft=".($d->dft>0?1:0).",
				pubdate=".bkint($d->pdt).",
				upddate=".TIMENOW."
			WHERE topicid=".bkint($topicid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
		
	public static function TopicRatingUpdate(Ab_Database $db, $topicid, $votecount, $voteup, $votedown){
		$sql = "
		UPDATE ".$db->prefix."bg_topic
			SET
				rating=".bkint($voteup-$votedown).",
				voteup=".bkint($voteup).",
				votedown=".bkint($votedown).",
				votecount=".bkint($votecount).",
				votedate=".TIMENOW."
			WHERE topicid=".bkint($topicid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function TagListByTopicIds(Ab_Database $db, $tids){
		if (!is_array($tids)){
			$tids = array(intval($tids));
		}
		if (count($tids) == 0){
			return null;
		}
		
		$awh = array();
		for ($i=0; $i<count($tids); $i++){
			array_push($awh, "tg.topicid=".bkint($tids[$i]));
		}
	
		$sql = "
			SELECT
				DISTINCT
				g.tagid as id,
				g.title as tl,
				g.name as nm
			FROM ".$db->prefix."bg_toptag tg
			INNER JOIN ".$db->prefix."bg_tag g ON tg.tagid = g.tagid
			WHERE ".implode(" OR ", $awh)."
		";
		return $db->query_read($sql);
	}
	
	public static function TagListByLikeQuery(Ab_Database $db, $query){
		$sql = "
			SELECT title as tl
			FROM ".$db->prefix."bg_tag
			WHERE title LIKE '".bkstr($query)."%' AND language='".bkstr(Abricos::$LNG)."'
			GROUP BY title
			ORDER BY title
			LIMIT 10
		";
		return $db->query_read($sql);
	}

	public static function TopicTagList(Ab_Database $db, $tids){
		if (!is_array($tids)){
			$tids = array(intval($tids));
		}
		if (count($tids) == 0){
			return null;
		}
		$awh = array();
		for ($i=0; $i<count($tids); $i++){
			array_push($awh, "tg.topicid=".bkint($tids[$i]));
		}
	
		$sql = "
			SELECT
				tg.topicid as tid,
				tg.tagid as tgid
			FROM ".$db->prefix."bg_toptag tg
			WHERE ".implode(" OR ", $awh)."
		";
		return $db->query_read($sql);
	}
	
	public static function UserList(Ab_Database $db, $uids){
		if (!is_array($uids)){
			$uids = array(intval($uids));
		}
		$awh = array();
		for ($i=0; $i<count($uids); $i++){
			array_push($awh, "u.userid=".bkint($uids[$i]));
		}
	
		$sql = "
			SELECT
				DISTINCT
				u.userid as id,
				u.avatar as avt,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm
			FROM ".$db->prefix."user u
			WHERE ".implode(" OR ", $awh)."
		";
		return $db->query_read($sql);
	}
	
	private static function CategoryRatingSQLExt(Ab_Database $db){
		$ret = new stdClass();
		$ret->fld = "";
		$ret->tbl = "";
		$userid = Abricos::$user->id;
		if (BlogManager::$isURating && $userid>0){
			$ret->fld .= "
				,IF(ISNULL(vt.userid), null, IF(vt.voteup>0, 1, IF(vt.votedown>0, -1, 0))) as vmy
			";
			$ret->tbl .= "
				LEFT JOIN ".$db->prefix."urating_vote vt 
					ON vt.module='blog' AND vt.elementtype='cat' 
					AND vt.elementid=cat.catid 
					AND vt.userid=".bkint($userid)."
			";
		}
		return $ret;
	}
	
	public static function CategoryList(Ab_Database $db){
		$urt = BlogTopicQuery::CategoryRatingSQLExt($db);
		
		$sql = "
			SELECT
				cat.catid as id,
				cat.name as nm,
				cat.title as tl,
				cat.descript as dsc,
				cat.isprivate prv,
				
				cat.rating as rtg,
				cat.votecount as vcnt,
				
				cat.reputation as rep,
				cat.topiccount as tcnt,
				cat.membercount as mcnt,
				
				IF(ISNULL(cur.userid), 0, cur.isadmin) as adm,
				IF(ISNULL(cur.userid), 0, cur.ismoder) as mdr,
				IF(ISNULL(cur.userid), 0, cur.ismember) as mbr
				".$urt->fld."
				
			FROM ".$db->prefix."bg_cat cat
			LEFT JOIN ".$db->prefix."bg_catuserrole cur ON cat.catid=cur.catid 
				AND cur.userid=".bkint(Abricos::$user->id)."
			".$urt->tbl."
			WHERE cat.language='".bkstr(Abricos::$LNG)."' AND cat.deldate=0
			ORDER BY rtg DESC, tcnt DESC, tl
		";
		return $db->query_read($sql);
	}
	
	public static function Category(Ab_Database $db, $catid){
		$urt = BlogTopicQuery::CategoryRatingSQLExt($db);
		
		$sql = "
			SELECT
				cat.catid as id,
				cat.name as nm,
				cat.title as tl,
				cat.descript as dsc,
				cat.isprivate prv,
				cat.rating as rtg,
				cat.votecount as vcnt,
				cat.reputation as rep,
				cat.topiccount as tcnt,
				cat.membercount as mcnt,
				
				IF(ISNULL(cur.userid), 0, cur.isadmin) as adm,
				IF(ISNULL(cur.userid), 0, cur.ismoder) as mdr,
				IF(ISNULL(cur.userid), 0, cur.ismember) as mbr
				
				".$urt->fld."
			
			FROM ".$db->prefix."bg_cat cat
			LEFT JOIN ".$db->prefix."bg_catuserrole cur ON cat.catid=cur.catid
				AND cur.userid=".bkint(Abricos::$user->id)."
			".$urt->tbl."
			WHERE cat.catid=".bkint($catid)." AND cat.deldate=0
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	

	/**
	 * Информация о последнем созданной категории пользователем
	 * 
	 * @param Ab_Database $db
	 * @param integer $userid
	 */
	public static function CategoryLastCreated(Ab_Database $db, $userid){
		$sql = "
			SELECT
				cat.catid as id,
				cat.dateline as dl
			FROM ".$db->prefix."bg_cat cat
			WHERE language='".bkstr(Abricos::$LNG)."' AND userid=".bkint($userid)." 
			ORDER BY dateline DESC
			LIMIT 1
		";
		return $db->query_first($sql);		
	}
	
	public static function CategoryAppend(Ab_Database $db, $userid, $d){
		$sql = "
			INSERT INTO ".$db->prefix."bg_cat
			(userid, language, title, name, descript, isprivate, reputation, dateline, upddate) VALUES (
				".bkint($userid).",
				'".bkstr(Abricos::$LNG)."',
				'".bkstr($d->tl)."',
				'".bkstr($d->nm)."',
				'".bkstr($d->dsc)."',
				".($d->prv>0?1:0).",
				".bkint($d->rep).",
				".TIMENOW.",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function CategoryUpdate(Ab_Database $db, $catid, $d){
		$sql = "
			UPDATE ".$db->prefix."bg_cat
			SET 
				title='".bkstr($d->tl)."',
				name='".bkstr($d->nm)."',
				descript='".bkstr($d->dsc)."',
				isprivate=".($d->prv>0?1:0).",
				reputation=".bkint($d->rep).",
				upddate=".TIMENOW."
			WHERE catid=".bkint($catid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function CategoryRatingUpdate(Ab_Database $db, $catid, $votecount, $voteup, $votedown){
		$sql = "
			UPDATE ".$db->prefix."bg_cat
			SET
				rating=".bkint($voteup-$votedown).",
				voteup=".bkint($voteup).",
				votedown=".bkint($votedown).",
				votecount=".bkint($votecount).",
				votedate=".TIMENOW."
			WHERE catid=".bkint($catid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function CategoryTopicCountUpdate(Ab_Database $db, $catid = 0){
		$sql = "
			UPDATE ".$db->prefix."bg_cat cat
			SET cat.topiccount = (
				SELECT count(*) as cnt
				FROM ".$db->prefix."bg_topic t
				WHERE cat.catid=t.catid AND t.deldate=0 AND t.isdraft=0 
				GROUP BY t.catid
			)
		";
		if ($catid > 0){
			$sql .= "
				WHERE catid=".bkint($catid)."
			";
		}
		$db->query_write($sql);
	}

	public static function CategoryMemberCountUpdate(Ab_Database $db, $catid = 0){
		$sql = "
			UPDATE ".$db->prefix."bg_cat cat
			SET cat.membercount = (
				SELECT count(*) as cnt
				FROM ".$db->prefix."bg_catuserrole ur
				WHERE cat.catid=ur.catid AND ur.ismember=1
				GROUP BY ur.catid
			)
		";
		if ($catid > 0){
			$sql .= "
				WHERE cat.catid=".bkint($catid)."
			";
		}
		$db->query_write($sql);
	}
	
	public static function CategoryUser(Ab_Database $db, $catid, $userid){
		$sql = "
			SELECT
				isadmin as adm,
				ismoder as mdr,
				ismember as mbr
			FROM ".$db->prefix."bg_catuserrole
			WHERE catid=".bkint($catid)." AND userid=".bkint($userid)."
			LIMIT 1
		";
		return $db->query_first($sql);		
	}
	
	public static function CategoryUserSetAdmin(Ab_Database $db, $catid, $userid, $isAdmin = false){
		$sql = "
			INSERT INTO ".$db->prefix."bg_catuserrole
			(catid, userid, isadmin, dateline, upddate) VALUES(
				".bkint($catid).",
				".bkint($userid).",
				".($isAdmin?1:0).",
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				isadmin=".($isAdmin?1:0).",
				upddate=".TIMENOW."
		";
		$db->query_write($sql);
	}
	
	public static function CategoryUserSetMember(Ab_Database $db, $catid, $userid, $isMember, $pubkey){
		$sql = "
			INSERT INTO ".$db->prefix."bg_catuserrole
				(catid, userid, ismember, pubkey, dateline, upddate) VALUES(
				".bkint($catid).",
				".bkint($userid).",
				".($isMember?1:0).",
				'".bkstr($pubkey)."',
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				ismember=".($isMember?1:0).",
				upddate=".TIMENOW."
		";
		$db->query_write($sql);
	}
	
	public static function CategoryRemove(Ab_Database $db, $catid){
		$sql = "
			UPDATE ".$db->prefix."bg_cat
			SET deldate=".TIMENOW."
			WHERE catid=".bkint($catid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function TagList(Ab_Database $db, $page, $limit){
		$sql = "
			SELECT
				t.tagid as id,
				t.name as nm,
				t.title as tl,
				t.topiccount as cnt
			FROM ".$db->prefix."bg_tag t
			ORDER BY cnt DESC
			LIMIT ".bkint($limit)."
		";
		return $db->query_read($sql);
	}
	
	public static function AuthorRatingSQLExt(Ab_Database $db){
		$urt = new stdClass();
		$urt->fld = "";
		$urt->tbl = "";
		$userid = Abricos::$user->id;
		if (BlogManager::$isURating && $userid>0){
			$urt->fld .= "
			,IF(ISNULL(urt.reputation), 0, urt.reputation) as rep,
			IF(ISNULL(urt.skill), 0, urt.skill) as rtg
			";
			$urt->tbl .= "
			LEFT JOIN ".$db->prefix."urating_user urt ON t.userid=urt.userid
			";
		}
		
		return $urt;
	}
	
	public static function Author(Ab_Database $db, $authorid){
		$urt = BlogTopicQuery::AuthorRatingSQLExt($db);
	
		$sql = "
			SELECT
				t.userid as id,
				u.username as unm,
				u.avatar as avt,
				u.firstname as fnm,
				u.lastname as lnm,
				count(t.topicid) as tcnt
				".$urt->fld."
			FROM ".$db->prefix."bg_topic t
			INNER JOIN ".$db->prefix."user u ON t.userid=u.userid
			".$urt->tbl."
			WHERE t.isdraft=0 AND t.deldate=0 AND t.userid=".bkint($authorid)."
			GROUP BY t.userid
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function AuthorByUserName(Ab_Database $db, $username){
		$urt = BlogTopicQuery::AuthorRatingSQLExt($db);
	
		$sql = "
			SELECT
				t.userid as id,
				u.username as unm,
				u.avatar as avt,
				u.firstname as fnm,
				u.lastname as lnm,
				count(t.topicid) as tcnt
				".$urt->fld."
			FROM ".$db->prefix."bg_topic t
			INNER JOIN ".$db->prefix."user u ON t.userid=u.userid
			".$urt->tbl."
			WHERE t.isdraft=0 AND t.deldate=0 AND u.username='".bkstr($username)."'
			GROUP BY t.userid
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function AuthorList(Ab_Database $db, $page, $limit){
		$urt = BlogTopicQuery::AuthorRatingSQLExt($db);
	
		$sql = "
			SELECT
				t.userid as id,
				u.username as unm,
				u.avatar as avt,
				u.firstname as fnm,
				u.lastname as lnm,
				count(t.topicid) as tcnt
				".$urt->fld."
			FROM ".$db->prefix."bg_topic t
			INNER JOIN ".$db->prefix."user u ON t.userid=u.userid
			".$urt->tbl."
			WHERE t.isdraft=0 AND t.deldate=0
			GROUP BY t.userid
			ORDER BY tcnt DESC
		";
		return $db->query_read($sql);
	}
	
	public static function CommentLiveList(Ab_Database $db, $page, $limit){
		$sql = "
			SELECT
				c.commentid as id,
				c.contentid as ctid,
				c.body,
				c.dateline as dl,

				t.topicid as tid,
				a.cnt,
				
				u.userid as uid,
				u.username as unm,
				u.avatar as avt,
				u.firstname as fnm,
				u.lastname as lnm
				
			FROM (
				SELECT ap.contentid, max( ap.dateline ) AS dl, count(ap.contentid) as cnt
				FROM ".$db->prefix."cmt_comment ap
				INNER JOIN ".$db->prefix."bg_topic tp ON ap.contentid = tp.contentid
				INNER JOIN ".$db->prefix."bg_cat catt ON tp.catid = catt.catid
				WHERE tp.deldate = 0 AND catt.deldate=0 AND tp.isdraft = 0 AND tp.language='".bkstr(Abricos::$LNG)."'
				GROUP BY contentid
				ORDER BY dl DESC
				LIMIT ".bkint($limit)."
			) a
			LEFT JOIN ".$db->prefix."cmt_comment c ON a.contentid = c.contentid AND c.dateline = a.dl
			LEFT JOIN ".$db->prefix."user u ON c.userid = u.userid
			LEFT JOIN ".$db->prefix."bg_topic t ON c.contentid = t.contentid
			LEFT JOIN ".$db->prefix."bg_cat cat ON t.catid = cat.catid
			WHERE t.deldate = 0 AND cat.deldate=0 AND t.isdraft = 0 AND t.language='".bkstr(Abricos::$LNG)."'
			ORDER BY dl DESC
		";
		return $db->query_read($sql);
	}
	
	public static function TagUpdate(Ab_Database $db, $tags){
		if (!is_array($tags) || count($tags) == 0){ return; }
		$av = array();
		for ($i=0;$i<count($tags);$i++){
			$tag = $tags[$i];
			array_push($av, "(
				'".bkstr($tag)."',
				'".bkstr(translateruen($tag))."',
				'".bkstr(Abricos::$LNG)."'
			)");
		}
		
		$sql = "
			INSERT IGNORE INTO ".$db->prefix."bg_tag 
			(title, name, language) VALUES
			".implode(", ", $av)."	
		";
		$db->query_write($sql);
	}
	
	public static function TopicTagUpdate(Ab_Database $db, $topicid, $tags){

		// зачистить все по топику
		$sql = "
			DELETE FROM ".$db->prefix."bg_toptag
			WHERE topicid=".bkint($topicid)."
		";
		$db->query_write($sql);
		
		if (!is_array($tags) || count($tags) == 0){ return; }
		
		$av = array();
		for ($i=0;$i<count($tags);$i++){
			array_push($av, "title='".bkstr($tags[$i])."'");
		}
		
		// вставить новый список тегов
		$sql = "
			INSERT INTO ".$db->prefix."bg_toptag
				(topicid, tagid)
			SELECT 
				".bkint($topicid)." as topicid,
				tagid
			FROM ".$db->prefix."bg_tag
			WHERE (".implode(" OR ", $av).") AND language='".bkstr(Abricos::$LNG)."'
		";
		$db->query_write($sql);
	}
	
	public static function TopicTagCountUpdate(Ab_Database $db, $tags){
		if (!is_array($tags) || count($tags) == 0){ return; }
		$av = array();
		for ($i=0;$i<count($tags);$i++){
			$tag = $tags[$i];
			array_push($av, "t.title='".bkstr($tag)."'");
		}
		$sql = "
			UPDATE ".$db->prefix."bg_tag t
			SET topiccount=(
				SELECT count(*)
				FROM ".$db->prefix."bg_toptag tt
				INNER JOIN ".$db->prefix."bg_topic top ON top.topicid=tt.topicid
				WHERE t.tagid=tt.tagid AND top.isdraft=0 AND top.deldate=0
			)
			WHERE (".implode(" OR ", $av).") AND language='".bkstr(Abricos::$LNG)."'
		";
		$db->query_write($sql);
	}
	
	/**
	 * Топик по которому есть рассылка уведомлений о том, что он опубликован
	 * @param Ab_Database $db
	 */
	public static function SubscribeTopic(Ab_Database $db){
		$sql = "
			SELECT 
				t.topicid as id,
				t.scblastuserid as sluid
			FROM ".$db->prefix."bg_topic t
			INNER JOIN ".$db->prefix."bg_cat cat ON t.catid=cat.catid
			WHERE t.scbcomplete=0 AND t.isindex=1 AND t.catid>0 AND t.deldate=0 AND cat.deldate=0
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	/**
	 * Список подписчиков на блог которым еще не было отправлено письмо о новом топике
	 * 
	 * 
	 * @param Ab_Database $db
	 * @param integer $catid
	 * @param integer $lastUserId
	 * @param integer $limit
	 */
	public static function SubscribeUserList(Ab_Database $db, $catid, $lastUserId, $limit = 25){
		$modAntibot = Abricos::GetModule('antibot');
		
		$sql = "
			SELECT 
				u.userid as id,
				u.username as unm,
				u.lastname as lnm,
				u.firstname as fnm,
				u.email as eml,
				IF ((cur.pubkey IS NULL), '', cur.pubkey) as pubkey,
				IF ((cur.scboff IS NULL), 0, cur.scboff) as scboff,
				IF ((uns.userid IS NULL), 0, 1) as scboffall
				
			FROM ".$db->prefix."bg_catuserrole cur
			INNER JOIN ".$db->prefix."user u ON cur.userid=u.userid
			LEFT JOIN ".$db->prefix."bg_scbunset uns ON cur.userid=uns.userid
			
			WHERE cur.ismember=1 AND cur.catid=".bkint($catid)." 
				AND u.email <> '' AND cur.userid > ".bkint($lastUserId)."
				".(!empty($modAntibot) ? " AND u.antibotdetect=0" : "")."
				
			ORDER BY cur.userid
			LIMIT ".bkint($limit)."
		";
		return $db->query_read($sql);
	}
	
	/**
	 * Обновить информацию о последнем пользователе которому было отправлено письмо по подписке
	 * @param Ab_Database $db
	 * @param integer $topicid
	 * @param integer $lastUserid
	 */
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
	
	
	
		
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                  МЕТОДЫ НА УДАЛЕНИЕ/ПЕРЕРАБОТКУ               */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
// TODO: Удалить


/**
 * Запросы для приложения
 */
class BlogQueryApp_OLD {
	
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
				AND t.language='".bkstr(Abricos::$LNG)."'
			ORDER BY t.pubdate DESC
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
				t.pubdate as dl
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

class BlogQuery_OLD {
	
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
			ORDER BY a.pubdate DESC
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
				a.upddate as de,
				a.pubdate as dp,
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
			ORDER BY a.pubdate DESC
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
				a.pubdate, a.catid, a.title, d.phrase as catph, d.name as catnm
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
				a.upddate as de,
				a.pubdate as dp,
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
				a.upddate as de,
				a.pubdate as dp,
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
			(metadesc, metakeys, name, title, catid, intro, contentid, userid, dateline, upddate, pubdate, status, language) VALUES
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
				upddate=".bkint($obj->de).",
				metadesc='".bkstr($obj->mtd)."',
				metakeys='".bkstr($obj->mtk)."'
			WHERE topicid=".bkint($obj->id)."
		";
		$db->query_write($sql);
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