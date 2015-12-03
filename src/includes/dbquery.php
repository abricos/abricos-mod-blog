<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogTopicQuery
 */
class BlogTopicQuery {

    public static function DomainFilterSQLExt(){
        $dmfilter = "AND (cat.deldate=0 OR t.catid=0)";
        $cfgDF = BlogConfig::$instance->domainFilter;
        if (!empty($cfgDF)){
            $arr = explode(",", $cfgDF);
            $ca = array();
            $ta = array();

            for ($i = 0; $i < count($arr); $i++){
                array_push($ca, "cat.domain='".trim($arr[$i])."'");
                array_push($ta, "t.domain='".trim($arr[$i])."'");
            }

            if (count($ta) > 0){
                return array(
                    "cat" => $ca,
                    "t" => $ta
                );
            }
        }
        return null;
    }

    private static function TopicFields(Ab_Database $db){
        return "
			t.topicid as id,
			t.catid as catid,
			t.title as tl,
			t.intro as intro,
			length(t.body) as bdlen,
			
			t.rating as rtg,
			t.votecount as vcnt,
			
			t.userid as uid,
			u.username as unm,
			u.avatar as avt,
			u.firstname as fnm,
			u.lastname as lnm,
			t.isdraft as dft,
			t.isindex as idx,
			t.autoindex as aidx,
			t.pubdate as dl
		";
    }

    private static function TopicRatingSQLExt(Ab_Database $db){
        $ret = new stdClass();
        $ret->fld = "";
        $ret->tbl = "";
        $userid = Abricos::$user->id;
        if (BlogManager::$isURating && $userid > 0){
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
     *
     * @param Ab_Database $db
     * @param integer $userid
     */
    public static function TopicPublicCountByUser(Ab_Database $db, $userid){
        $day = 60 * 60 * 24;
        $t1 = intval(floor(TIMENOW / $day) * $day);
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
     * @param Ab_Database $db
     * @param integer $topicid
     */
    public static function TopicById(Ab_Database $db, $topicid){
        $urt = BlogTopicQuery::TopicRatingSQLExt($db);

        $where = "t.topicid = ".bkint($topicid)."";

        $userid = Abricos::$user->id;
        $sql = "
			SELECT
				".BlogTopicQuery::TopicFields($db).",
				t.body as bd,
				t.metakeys as mtks,
				t.metadesc as mtdsc
				".$urt->fld."
			FROM ".$db->prefix."bg_topic t
			LEFT JOIN ".$db->prefix."bg_cat cat ON t.catid = cat.catid
			INNER JOIN ".$db->prefix."user u ON t.userid = u.userid
			".$urt->tbl."
			WHERE ".$where." AND t.deldate=0 AND (cat.deldate=0 OR t.catid=0)
				AND (t.isdraft=0 OR (t.isdraft=1 AND t.userid=".bkint($userid)."))
			LIMIT 1
		";
        return $db->query_first($sql);
    }


    public static function TopicList(Ab_Database $db, $page = 1, $limit = 10, $fType = 'index', $fPrm = '', $isCount = false){
        $from = $limit * (max($page, 1) - 1);
        $urt = BlogTopicQuery::TopicRatingSQLExt($db);

        $newPeriod = TIMENOW - 60 * 60 * 24;

        $filterRating = "";
        if (BlogManager::$isURating){
            $filterRating = " AND (t.rating >= 5 OR t.isindex=1)";
        }

        $filter = '';
        if ($fType == "index"){ // главная
            if ($fPrm == "new"){
                $filter = " AND t.pubdate>".$newPeriod;
                $filterRating = "";
            }
        } else if ($fType == 'pub'){        // коллективные
            $filter = " AND t.catid>0";
            if ($fPrm == 'new'){
                $filter .= " AND t.pubdate>".$newPeriod;
                $filterRating = "";
            }
        } else if ($fType == 'pers'){        // персональные
            $filter = " AND t.catid=0";
            if ($fPrm == 'new'){
                $filter .= " AND t.pubdate>".$newPeriod;
                $filterRating = "";
            }
        } else if ($fType == 'cat'){
            $fa = explode("/", $fPrm);
            $filter = " AND t.catid=".bkint($fa[0]);

            if (isset($fa[1]) && $fa[1] == 'new'){
                $filter .= " AND t.pubdate>".$newPeriod;
                $filterRating = "";
            }

        } else if ($fType == 'tag'){
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

        $dmfilter = "AND (cat.deldate=0 OR t.catid=0)";
        $dmfa = BlogTopicQuery::DomainFilterSQLExt();
        if (!empty($dmfa)){
            $dmfilter = " AND (
				(cat.deldate=0 AND (".implode(" OR ", $dmfa['cat']).")) 
				OR 
				(t.catid=0 AND (".implode(" OR ", $dmfa['t'])."))
			)";
        }

        $sql = "
			SELECT ".$fld."
			FROM ".$db->prefix."bg_topic t
			LEFT JOIN ".$db->prefix."bg_cat cat ON t.catid = cat.catid
			INNER JOIN ".$db->prefix."user u ON t.userid = u.userid
			".$urt->tbl."
			WHERE t.deldate=0 AND t.isdraft=0 AND t.language='".bkstr(Abricos::$LNG)."'
				".$dmfilter."
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
    public static function TopicDraftList(Ab_Database $db, $userid, $page = 1, $limit = 10){
        $from = $limit * (max($page, 1) - 1);
        $urt = BlogTopicQuery::TopicRatingSQLExt($db);

        $sql = "
			SELECT
				".BlogTopicQuery::TopicFields($db)."
				".$urt->fld."
			FROM ".$db->prefix."bg_topic t
			LEFT JOIN ".$db->prefix."bg_cat cat ON t.catid = cat.catid
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
    public static function TopicListByAuthor(Ab_Database $db, $userid, $page = 1, $limit = 10){
        $from = $limit * (max($page, 1) - 1);
        $urt = BlogTopicQuery::TopicRatingSQLExt($db);

        $sql = "
			SELECT
				".BlogTopicQuery::TopicFields($db)."
				".$urt->fld."
			FROM ".$db->prefix."bg_topic t
			LEFT JOIN ".$db->prefix."bg_cat cat ON t.catid = cat.catid
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
        for ($i = 0; $i < count($ids); $i++){
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
			INNER JOIN ".$db->prefix."user u ON t.userid = u.userid
			".$urt->tbl."
			WHERE ".implode(" OR ", $ids)."
		";
        return $db->query_read($sql);
    }

    public static function TopicAppend(Ab_Database $db, $userid, $d){
            $sql = "
			INSERT INTO ".$db->prefix."bg_topic
			(catid, userid, language, title, name, intro, body, metakeys, metadesc, isdraft, autoindex, pubdate, dateline, upddate) VALUES (
				".bkint($d->catid).",
				".bkint($userid).",
				'".bkstr(Abricos::$LNG)."',
				'".bkstr($d->tl)."',
				'".bkstr($d->nm)."',
				'".bkstr($d->intro)."',
				'".bkstr($d->body)."',
				'".bkstr($d->mtks)."',
				'".bkstr($d->mtdsc)."',
				".($d->dft > 0 ? 1 : 0).",
				1,
				".bkint($d->pdt).",
				".TIMENOW.",
				".TIMENOW."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function TopicUpdate(Ab_Database $db, $topicid, $d){
        $sql = "
			UPDATE ".$db->prefix."bg_topic
			SET
				catid=".bkint($d->catid).",
				title='".bkstr($d->tl)."',
				name='".bkstr($d->nm)."',
				intro='".bkstr($d->intro)."',
				body='".bkstr($d->body)."',
				metakeys='".bkstr($d->mtks)."',
				metadesc='".bkstr($d->mtdsc)."',
				isdraft=".($d->dft > 0 ? 1 : 0).",
				pubdate=".bkint($d->pdt).",
				upddate=".TIMENOW."
			WHERE topicid=".bkint($topicid)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function TopicMetaTagUpdate(Ab_Database $db, $topicid, $metakeys, $metadesc){

        $sql = "
			UPDATE ".$db->prefix."bg_topic
			SET
				metakeys='".bkstr($metakeys)."',
				metadesc='".bkstr($metadesc)."'
			WHERE topicid=".bkint($topicid)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function TopicRatingUpdate(Ab_Database $db, $topicid, $votecount, $voteup, $votedown){
        $sql = "
			UPDATE ".$db->prefix."bg_topic
			SET
				rating=".bkint($voteup - $votedown).",
				voteup=".bkint($voteup).",
				votedown=".bkint($votedown).",
				votecount=".bkint($votecount).",
				votedate=".TIMENOW."
			WHERE topicid=".bkint($topicid)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    /**
     * Автоматическое обновление статуса вывода на главной согласно рейтинку
     *
     * @param Ab_Database $db
     * @param boolean $topicid
     * @param boolean $isIndex
     */
    public static function TopicIndexUpdateByRating(Ab_Database $db, $topicid, $isIndex){
        $sql = "
			UPDATE ".$db->prefix."bg_topic
			SET isindex=".bkint($isIndex ? 1 : 0)."
			WHERE topicid=".bkint($topicid)." AND autoindex=1
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function TopicIndexUpdateByAdmin(Ab_Database $db, $topicid, $isIndex, $isAutoIndex){
        $sql = "
			UPDATE ".$db->prefix."bg_topic
			SET isindex=".bkint($isIndex ? 1 : 0).",
				autoindex=".bkint($isAutoIndex ? 1 : 0)."
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
        for ($i = 0; $i < count($tids); $i++){
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
        for ($i = 0; $i < count($tids); $i++){
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
        for ($i = 0; $i < count($uids); $i++){
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
        if (BlogManager::$isURating && $userid > 0){
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

        $dmfilter = "";
        $dmfa = BlogTopicQuery::DomainFilterSQLExt();
        if (!empty($dmfa)){
            $dmfilter = " AND (".implode(" OR ", $dmfa['cat']).")";
        }

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
				".$dmfilter."
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
			(userid, domain, language, title, name, descript, isprivate, reputation, dateline, upddate) VALUES (
				".bkint($userid).",
				'".bkstr(Abricos::$DOMAIN)."',
				'".bkstr(Abricos::$LNG)."',
				'".bkstr($d->tl)."',
				'".bkstr($d->nm)."',
				'".bkstr($d->dsc)."',
				".($d->prv > 0 ? 1 : 0).",
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
				isprivate=".($d->prv > 0 ? 1 : 0).",
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
				rating=".bkint($voteup - $votedown).",
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
				".($isAdmin ? 1 : 0).",
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				isadmin=".($isAdmin ? 1 : 0).",
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
				".($isMember ? 1 : 0).",
				'".bkstr($pubkey)."',
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				ismember=".($isMember ? 1 : 0).",
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

        $dmfilter = "AND (cat.deldate=0 OR t.catid=0)";
        $dmfa = BlogTopicQuery::DomainFilterSQLExt();
        if (!empty($dmfa)){
            $dmfilter = " AND (
				(cat.deldate=0 AND (".implode(" OR ", $dmfa['cat'])."))
					OR
				(t.catid=0 AND (".implode(" OR ", $dmfa['t'])."))
			)";
        }

        $sql = "
			SELECT
				DISTINCT tg.tagid as id,
				tg.name as nm,
				tg.title as tl,
				tg.topiccount as cnt
			FROM ".$db->prefix."bg_tag tg
			INNER JOIN ".$db->prefix."bg_toptag tgtp ON tg.tagid = tgtp.tagid
			INNER JOIN ".$db->prefix."bg_topic t ON t.topicid = tgtp.topicid
			INNER JOIN ".$db->prefix."bg_cat cat ON cat.catid = t.catid
			WHERE t.deldate=0 AND t.isdraft=0 AND t.isindex=1 AND t.language='".bkstr(Abricos::$LNG)."'
				".$dmfilter."
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
        if (BlogManager::$isURating && $userid > 0){
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
        $dmfilter = "";
        $dmfa = BlogTopicQuery::DomainFilterSQLExt();
        if (!empty($dmfa)){
            $dmfilter = " AND (".implode(" OR ", $dmfa['cat']).")";
        }

        $sql = "
            SELECT DISTINCT
                o.lastCommentid as id,
                c.body,
                o.lastCommentDate as dl,
				t.topicid as tid,
                o.commentCount as cnt,
                o.lastUserid as uid,
                u.username as unm,
                u.firstname as fnm,
                u.lastname as lnm,
                u.avatar as avt
			FROM ".$db->prefix."comment_ownerstat o
			INNER JOIN ".$db->prefix."comment c ON c.commentid=o.lastCommentid
			INNER JOIN ".$db->prefix."user u ON u.userid=o.lastUserid
			INNER JOIN ".$db->prefix."bg_topic t ON t.topicid=o.ownerid
            INNER JOIN ".$db->prefix."bg_cat cat ON t.catid = cat.catid
			WHERE o.ownerModule='blog' AND o.ownerType='topic'
                AND t.deldate=0 AND cat.deldate=0 AND t.isdraft=0 AND t.language='".bkstr(Abricos::$LNG)."'
					".$dmfilter."
			ORDER BY o.lastCommentDate DESC
			LIMIT ".bkint($limit)."
		";
        return $db->query_read($sql);
    }

    public static function TagUpdate(Ab_Database $db, $tags){
        if (!is_array($tags) || count($tags) == 0){
            return;
        }
        $av = array();
        for ($i = 0; $i < count($tags); $i++){
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

        if (!is_array($tags) || count($tags) == 0){
            return;
        }

        $av = array();
        for ($i = 0; $i < count($tags); $i++){
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
        if (!is_array($tags) || count($tags) == 0){
            return;
        }
        $av = array();
        for ($i = 0; $i < count($tags); $i++){
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
     *
     * @param Ab_Database $db
     */
    public static function SubscribeTopic(Ab_Database $db){
        $sql = "
			SELECT 
				t.topicid as id,
				t.scblastuserid as sluid
			FROM ".$db->prefix."bg_topic t
			INNER JOIN ".$db->prefix."bg_cat cat ON t.catid=cat.catid
			WHERE t.isdraft=0 AND t.scbcomplete=0 AND t.isindex=1 AND t.catid>0 AND t.deldate=0 AND cat.deldate=0
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
     *
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


    public static function CategoryUserRoleByPubKey(Ab_Database $db, $userid, $pubkey){
        $sql = "
			SELECT *
			FROM ".$db->prefix."bg_catuserrole
			WHERE userid=".bkint($userid)." AND pubkey='".bkstr($pubkey)."'
			LIMIT 1
		";
        return $db->query_first($sql);
    }


    public static function UnSubscribeCategory(Ab_Database $db, $userid, $pubkey){
        $sql = "
			UPDATE ".$db->prefix."bg_catuserrole
			SET scboff=1
			WHERE userid=".bkint($userid)." AND pubkey='".bkstr($pubkey)."'
		";
        $db->query_write($sql);
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