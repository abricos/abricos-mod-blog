<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classes.php';

class BlogManager extends Ab_ModuleManager {
	
	/**
	 * 
	 * @var BlogModule
	 */
	public $module = null;
	
	/**
	 * @var BlogManager
	 */
	public static $instance = null;
	
	public static $isURating = false;
	
	public function __construct($module){
		parent::__construct($module);

		BlogManager::$instance = $this;
		
		$modURating = Abricos::GetModule("urating");
		BlogManager::$isURating = !empty($modURating); 
	}
	
	public function IsAdminRole(){
		return $this->IsRoleEnable(BlogAction::ADMIN);
	}
	
	public function IsWriteRole(){
		if ($this->IsAdminRole()){ return true; }
		return $this->IsRoleEnable(BlogAction::WRITE);
	}
	
	public function IsViewRole(){
		if ($this->IsWriteRole()){ return true; }
		return $this->IsRoleEnable(BlogAction::VIEW);
	}

	public function AJAX($d){

		switch($d->do){
			case "topic": 
				return $this->TopicToAJAX($d->topicid);
			case "topicsave": 
				return $this->TopicSave($d);
			case "topiclist": 
				return $this->TopicListToAJAX($d);
			case "categorylist": 
				return $this->CategoryListToAJAX();
			case "categorysave": 
				return $this->CategorySave($d);
			case "categoryjoin":
				return $this->CategoryJoin($d->catid);
			case "categoryremove":
				return $this->CategoryRemove($d->catid);
			case "author": 
				return $this->AuthorToAJAX($d->authorid);
			case "authorlist": 
				return $this->AuthorListToAJAX($d);
			case "commentlivelist": 
				return $this->CommentLiveListToAJAX($d);
			case "taglist": 
				return $this->TagListToAJAX($d);
		}

		return null;
	}
	
	public function ParamToObject($o){
		if (is_array($o)){
			$ret = new stdClass();
			foreach($o as $key => $value){
				$ret->$key = $value;
			}
			return $ret;
		}else if (!is_object($o)){
			return new stdClass();
		}
		return $o;
	}
	
	/**
	 * @return URatingManager
	 */
	public function GetURatingManager(){
		return Abricos::GetModule('urating')->GetManager();
	}
	
	public function ToArray($rows, &$ids1 = "", $fnids1 = 'uid', &$ids2 = "", $fnids2 = '', &$ids3 = "", $fnids3 = ''){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row);
			if (is_array($ids1)){
				$ids1[$row[$fnids1]] = $row[$fnids1];
			}
			if (is_array($ids2)){
				$ids2[$row[$fnids2]] = $row[$fnids2];
			}
			if (is_array($ids3)){
				$ids3[$row[$fnids3]] = $row[$fnids3];
			}
		}
		return $ret;
	}
	
	public function ToArrayId($rows, $field = "id"){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row[$field]] = $row;
		}
		return $ret;
	}
	
	private function TopicSetTags($topics){
		$tids = array();

		foreach ($topics as $topic){
			array_push($tids, $topic->id);
		}
		
		$rows = BlogTopicQuery::TopicTagList($this->db, $tids);
		$toptags = $this->ToArray($rows);
		
		$rows = BlogTopicQuery::TagListByTopicIds($this->db, $tids);
		$dbtags = $this->ToArrayId($rows);
		
		for ($i=0; $i<count($topics); $i++){
			$topic = $topics[$i];
			$tags = array();
			for ($ii=0; $ii<count($toptags); $ii++){
				$tt = $toptags[$ii];
				if ($tt['tid'] == $topic->id){
					array_push($tags, new BlogTopicTag($dbtags[$tt['tgid']]));
				}
			}
			$topic->tags = $tags;
		}		
	}
	
	/**
	 * Список записей блога
	 * 
	 * @param object $cfg параметры списка
	 * @return array
	 */
	public function TopicList($cfg){
		if (!$this->IsViewRole()){ return null; }
		
		$cfg = $this->ParamToObject($cfg);
		
		if (empty($cfg->limit)){ $cfg->limit = 10; }
		if (empty($cfg->page)){ $cfg->page = 1; }
				
		$page = $cfg->page = max(intval($cfg->page), 1);
		$limit = $cfg->limit = max(1, min(25, intval($cfg->limit)));
		
		if (!is_string($cfg->filter)){
			$cfg->filter = "";
		}
		
		$fa = explode("/", $cfg->filter);
		$fType = $fa[0]; $fPrm = $fa[1];
		if (!empty($fa[2])){
			$fPrm .= "/".$fa[2];
		}
		$total = 0;
		$totalNew = 0;
		
		switch ($fType){
		case "draft":
			$rows = BlogTopicQuery::TopicDraftList($this->db, $this->userid, $page, $limit);
			break;

		case "author":
			$rows = BlogTopicQuery::TopicListByAuthor($this->db, $fPrm,  $page, $limit);
			break;
		
		case "index":	// список на главной (интересные/новые)
		case "pub":		// коллективные (интересные/новые)
		case "pers":	// персональные (хорошие/новые)
			$rows = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm);
			$total = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm, true);

			if ($fPrm == "new"){
				$totalNew = $total;
			}else{
				$totalNew = BlogTopicQuery::TopicList($this->db, page, $limit, $fType, "new", true);
			}
			break;
		
		case "tag":
			$rows = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm);
			$total = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm, true);
			break;
		case "cat":
			$rows = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm);
			$total = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm, true);
			
			if ($fa[2] == "new"){
				$totalNew = $total;
			}else{
				$totalNew = BlogTopicQuery::TopicList($this->db, page, $limit, $fType, $fa[1]."/new", true);
			}
			break;

		default:
			$rows = BlogTopicQuery::TopicList($this->db, $page, $limit);
			$total = BlogTopicQuery::TopicList($this->db, $page, $limit, "", "", true);
			$totalNew = BlogTopicQuery::TopicList($this->db, $page, $limit, "", "new", true);
			break;
		}
		
		$topics = array();
		
		while (($row = $this->db->fetch_array($rows))){
			array_push($topics, new BlogTopicInfo($row));
		}
		
		$this->TopicSetTags($topics);
		
		return new BlogTopicList($topics, $total, $totalNew);
	}
	
	public function TopicListToAJAX($cfg){
		$topicList = $this->TopicList($cfg);
		if (is_null($topicList)){
			return null;
		}
		return $topicList->ToAJAX();
	}
	
	/**
	 * @return BlogTopic
	 */
	public function Topic($topicid, $contentid = 0){
		if (!$this->IsViewRole()){
			return null;
		}
	
		$row = BlogTopicQuery::Topic($this->db, $topicid, $contentid);
		if (empty($row)){
			return null;
		}
		$topic = new BlogTopic($row);
		$this->TopicSetTags(array($topic));
		
		return $topic;
	}
	
	public function TopicToAJAX($topicid){
		$topic = $this->Topic($topicid);
		if (is_null($topic)){
			return null;
		}
	
		$ret = new stdClass();
		$ret->topic = $topic->ToAJAX();
		return $ret;
	}
	
	/**
	 * Сохранение топика
	 * 
	 * Коды ошибок:
	 * 	null - нет прав,
	 *	1 - заголовок не может быть пустым,
	 *	2 - должны быть указаны метки,
	 *  11 - черновиков не более 25 на профиль,
	 *  12 - публиковать не более 3-х в сутки,
	 * 	20 - недостаточно репутации для публикации в любой категории,
	 *  21 - недостаточно репутации для публикации именно в этй категории,
	 *  99 - неизвестная ошибка
	 *  
	 * @param object $d
	 */
	public function TopicSave($d){
		if (!$this->IsWriteRole()){ return null; }
		
		$ret = new stdClass();
		$ret->error = 0;
		$ret->topicid = 0;
		
		$d->id = intval($d->id);
		$d->dft = intval($d->dft);
		$d->catid = intval($d->catid);
		
		// проверка категории на возможность публиковать в ней
		$cat = null; // null - персональный блог
		if ($d->catid > 0){
			$cat = $this->Category($d->catid);

			if (empty($cat)){ return null; } // hacker?
				
			if (!$cat->IsTopicWrite()){
				return null; // только участник может публиковать в блог
			}
		}
		
		// проверка топика
		$topic = null; // текущий топик в базе, если null - создается новый
		if ($d->id > 0){
			$topic = $this->Topic($d->id);
			if (empty($topic)){ return null; } // hacker?
			
			if (!$this->IsAdminRole()){
				// автор ли топика правит его?
				if ($topic->user->id != $this->userid){ return null; } // hacker?
			}
			$d->pdt = $topic->publicDate;
		}
		
		$isNewPublic = false;
		$isNewDraft = false;
		
		// проверка на добавление в базу нового топика
		if ($d->id == 0){
			if ($d->dft==1){ // будет добавлен черновик
				$isNewDraft = true;
				$d->pdt = 0;
			}else{ // будет опубликован новый топик
				$isNewPublic = true;
				$d->pdt = TIMENOW;
			}
		}else{
			if ($topic->isDraft && $d->dft!=0){ // черновик станет публикацией
				$isNewPublic = true;
				if ($topic->publicDate == 0){ // публикация в первый раз
					$d->pdt = TIMENOW;
				}
			}else if (!$topic->isDraft && $d->dft==0){ // публикация станет черновиком
				
			}else{ // просто сохранен без смены статуса черновика
				
			}
		}
		
		if (!$this->IsAdminRole()){
			
			// ограничения по количеству
			if ($isNewDraft){ // не более 25 черновиков на профиль
				$row = BlogTopicQuery::TopicDraftCountByUser($this->db, $this->userid);
				if (!empty($row) && $row['cnt'] >= 25){
					$ret->error = 11;
					return $ret;
				}
			}else if ($isNewPublic){ // проверки по публикации
				$row = BlogTopicQuery::TopicPublicCountByUser($this->db, $this->userid);
				$pubCount = intval($row['cnt']);
				
				if ($pubCount > 3){ // не более 3 публикаций в день
					$ret->error = 12;
					return $ret;
				}
				
				// ограничения по репутации
				if (BlogManager::$isURating){ // работает система репутации пользователя

					$urep = $this->GetURatingManager()->UserReputation();

					// ограничения по репутации категории
					if (!empty($cat) && $urep->reputation < $cat->reputation){
						$ret->error = 21;
						return $ret;
					}
					
					if ($urep->reputation == 0 && empty($cat)){ // публикует с нулевой репутацией в персональный блог
						// ограничения для персонального блога не более 3-х публикаций в сутки
					
					}else if ($urep->reputation < 1){ // для публикации в коллективном блоге необходима репутация > 0
						$ret->error = 20;
						return $ret;
					}
				
				}
			}
		}
		
		$utm = Abricos::TextParser();
		$utmf = Abricos::TextParser(true);
		
		
		// список тегов
		$tags = array();
		for($i=0;$i<count($d->tags);$i++){
			$tag = $utmf->Parser($d->tags[$i]);
			
			if (function_exists('mb_strtolower')){
				$tag = mb_strtolower($tag, 'UTF-8');
			}
			
			if (empty($tag)){ continue; }
			array_push($tags, $tag);
		}
		
		if (count($tags) == 0){ // хотябы одно ключевое слово должно быть заполнено
			$ret->error = 2;
			return $ret;
		}
		
		$d->tl = $utmf->Parser($d->tl);
		if (empty($d->tl)){
			$ret->error = 1;
			return $ret;
		}
		$d->nm = $utmf->Parser($d->nm);
		if (empty($d->nm)){
			$d->nm = translateruen($d->tl);
		}
		
		$d->intro = $utm->Parser($d->intro);
		$d->body = $utm->Parser($d->body);
		
		// все проверки выполнены, добавление/сохранение топика
		if ($d->id == 0){
			$d->id = BlogTopicQuery::TopicAppend($this->db, $this->userid, $d);
			if ($d->id == 0){
				$ret->error = 99;
				return $ret;
			}
		}else{
			BlogTopicQuery::TopicUpdate($this->db, $d->id, $topic->contentid, $d);
		}
		
		// обновление тегов
		BlogTopicQuery::TagUpdate($this->db, $tags);
		BlogTopicQuery::TopicTagUpdate($this->db, $d->id, $tags);
		BlogTopicQuery::TopicTagCountUpdate($this->db, $tags);
		
		
		$ret->topicid = $d->id;
		return $ret;
	}

	private $_categoryListCache = null;

	/**
	 * @return BlogCategoryList
	 */
	public function CategoryList(){
		if (!$this->IsViewRole()){ return null; }
		
		
		if (!empty($this->_categoryListCache)){
			return $this->_categoryListCache;
		}
		
		$cats = array();
		$rows = BlogTopicQuery::CategoryList($this->db);
		while (($row = $this->db->fetch_array($rows))){
			array_push($cats, new BlogCategory($row));
		}
		
		$this->_categoryListCache = new BlogCategoryList($cats);
		return $this->_categoryListCache;
	}
	
	public function Category($catid){
		if (!$this->IsViewRole()){ return null; }
		
		$row = BlogTopicQuery::Category($this->db, $catid);
		if (empty($row)){ return null; }
		
		return new BlogCategory($row);
	}
	
	public function CategoryListToAJAX(){
		$catList = $this->CategoryList();
		if (is_null($catList)){
			return null;
		}
		return $catList->ToAJAX();
	}

	/**
	 * Сохранение категории (блога)
	 * 
	 * Коды ошибок:
	 * 	null - нет прав,
	 *	1 - заголовок не может быть пустым,
	 *	5 - пользователю можно создавать категорию не более одной в сутки,
	 * 	10 - недостаточно репутации,
	 *  99 - неизвестная ошибка
	 */
	public function CategorySave($d){
		if (!$this->IsWriteRole()){ return null; }
		
		$ret = new stdClass();
		$ret->error = 0;
		$ret->catid = 0;
		
		$utm = Abricos::TextParser();
		$utmf = Abricos::TextParser(true);
		
		$d->tl = $utmf->Parser($d->tl);
		if (empty($d->tl)){
			$ret->error = 1;
			return $ret;
		}
		$d->nm = $utmf->Parser($d->nm);
		if (empty($d->nm)){
			$d->nm = translateruen($d->tl);
		}
		
		$d->dsc = $utm->Parser($d->dsc);
		$d->rep = intval($d->rep);
		$d->prv = intval($d->prv);
		
		if (!$this->IsAdminRole()){
				
			if (BlogManager::$isURating){ // работает система репутации пользователя
				$rep = $this->GetURatingManager()->UserReputation();
				if ($rep->reputation < 5){ // для создании/редактировании категории необходима репутация >= 5
					$ret->error = 10;
					return $ret;
				}
			}
		}
		
		if ($d->id == 0){ // создание новой категории
			
			if (!$this->IsAdminRole()){
					
				// категорию создает не админ
				// значит нужно наложить ограничения
				// не более 1 категории в день (пока так)
				$dbCat = BlogTopicQuery::CategoryLastCreated($this->db, $this->userid);
				if (!empty($dbCat) && $dbCat['dl']+60*60*24 > TIMENOW){
					$ret->error = 5;
					return $ret;
				}
			}
			
			$d->id = BlogTopicQuery::CategoryAppend($this->db, $this->userid, $d);
			if ($d->id == 0){
				$ret->error = 99;
				return $ret;
			}
			
			// создатель категории становиться ее админом
			BlogTopicQuery::CategoryUserSetAdmin($this->db, $d->id, $this->userid, true);
			BlogTopicQuery::CategoryUserSetMember($this->db, $d->id, $this->userid, true);
			
			BlogTopicQuery::CategoryMemberCountUpdate($this->db, $d->id);
		}else{
			// А есть ли права админа на правку категории
			$cat = $this->Category($d->id);
			if(empty($cat)){ return null; }
			
			if (!$cat->IsAdmin()){ return null; }
			
			BlogTopicQuery::CategoryUpdate($this->db, $d->id, $d);
		}

		$ret->catid = $d->id;
		
		$cats = $this->CategoryListToAJAX();
		$ret->categories = $cats->categories;
		
		return $ret;
	}
	
	/**
	 * Вступить/выйти из блога текущему пользователю
	 */
	public function CategoryJoin($catid){
		if (!$this->IsViewRole() || $this->userid == 0){ return null; }
		
		$cat = $this->Category($catid);
		if (is_null($cat)){ return null; }
		
		BlogTopicQuery::CategoryUserSetMember($this->db, $catid, $this->userid, !$cat->isMemberFlag);
		
		BlogTopicQuery::CategoryMemberCountUpdate($this->db, $catid);
		
		// повторно запросить категорию
		$cat = $this->Category($catid);
		
		$ret = new stdClass();
		$ret->category = $cat->ToAJAX();
		return $ret;
	}
	
	public function CategoryRemove($catid){
		if (!$this->IsAdminRole()){ return null; }
		
		BlogTopicQuery::CategoryRemove($this->db, $catid);
		
		$ret = new stdClass();
		$cats = $this->CategoryListToAJAX();
		$ret->categories = $cats->categories;
		return $ret;
	}
	
	public function AuthorList($cfg){
		if (!$this->IsViewRole()){
			return null;
		}
		
		$cfg = $this->ParamToObject($cfg);
		$cfg->page = max(intval($cfg->page), 1);
		
		if (empty($cfg->limit)){ $cfg->limit = 5; }
		$cfg->limit = max(min($cfg->limit, 25), 1);
		
		
		$list = array();
		
		$rows = BlogTopicQuery::AuthorList($this->db, $cfg->page, $cfg->limit);
		while (($row = $this->db->fetch_array($rows))){
			array_push($list, new BlogAuthor($row));
		}
		return new BlogAuthorList($list);
	}
	
	public function AuthorListToAJAX($cfg){
		$list = $this->AuthorList($cfg);
		if (is_null($list)){
			return null;
		}
		
		return $list->ToAJAX();
	}
	
	public function Author($authorid){
		if (!$this->IsViewRole()){
			return null;
		}
	
		$row = BlogTopicQuery::Author($this->db, $authorid);
		if (empty($row)){ return null; }
		return new BlogAuthor($row);
	}
	
	public function AuthorToAJAX($authorid){
		$author = $this->Author($authorid);
		if (is_null($author)){
			return null;
		}
		$ret = new stdClass();
		$ret->author = $author->ToAJAX();
		return $ret;
	}
	/**
	 * Прямой эфир
	 * @param object $cfg
	 * @return BlogCommentLiveList
	 */
	public function CommentLiveList($cfg = null){
		if (!$this->IsViewRole()){ return null; }
		
		$cfg = $this->ParamToObject($cfg);
		
		$cfg->page = max(intval($cfg->page), 1);

		if (empty($cfg->limit)){ $cfg->limit = 5; }
		$cfg->limit = max(min($cfg->limit, 25), 1);
		
		
		$list = array();
		$tids = array();
		
		$rows = BlogTopicQuery::CommentLiveList($this->db, $cfg->page, $cfg->limit);
		while (($row = $this->db->fetch_array($rows))){
			$cmtLive = new BlogCommentLive($row);
			
			array_push($list, $cmtLive);
			array_push($tids, $cmtLive->topicid);
		}
		$topics = array();
		$rows = BlogTopicQuery::TopicListByIds($this->db, $tids);
		while (($row = $this->db->fetch_array($rows))){
			$topic = new BlogTopicInfo($row);
			array_push($topics, new BlogTopicInfo($row));

			for ($i=0;$i<count($list);$i++){
				if ($list[$i]->topicid == $topic->id){
					$list[$i]->topic = $topic;
				}
			}
		}
		
		$this->TopicSetTags($topics);
		
		return new BlogCommentLiveList($list);
	}
	
	public function CommentLiveListToAJAX($cfg){
		$list = $this->CommentLiveList($cfg);
		if (is_null($list)){
			return null;
		}
		return $list->ToAJAX();
	}

	public function TagList($cfg){
		if (!$this->IsViewRole()){ return null; }
	
		$cfg = $this->ParamToObject($cfg);
		if (empty($cfg->limit)){ $cfg->limit = 25;}
		
		$cfg->limit = max(min($cfg->limit, 100), 1);
		
		$list = array();
	
		$rows = BlogTopicQuery::TagList($this->db, $cfg->page, $cfg->limit);
		while (($row = $this->db->fetch_array($rows))){
			array_push($list, new BlogTopicTag($row));
		}
		return new BlogTopicTagList($list);
	}
	
	public function TagListToAJAX($cfg){
		$list = $this->TagList($cfg);
		if (is_null($list)){ return null; }
	
		return $list->ToAJAX();
	}

	/**
	 * Можно ли проголосовать текущему пользователю за категорию/топик
	 *
	 * Метод вызывается из модуля URating
	 *
	 * Возвращает код ошибки:
	 *  0 - все нормально, голосовать можно,
	 *  2 - голосовать можно только с положительным рейтингом,
	 *  3 - недостаточно голосов (закончились голоса),
	 *  4 - нальзя голосовать за свой топик
	 *
	 *
	 * @param URatingUserReputation $uRep
	 * @param string $act
	 * @param integer $userid
	 * @param string $eltype
	 */
	public function URating_IsElementVoting(URatingUserReputation $uRep, $act, $elid, $eltype){
		$man = URatingManager::$instance;
		if (!($eltype=='cat' || $eltype=='topic')){ return null; }
		
		if ($this->IsAdminRole()){ // админу можно голосовать всегда
			return 0;
		}
	
		if ($uRep->reputation < 1){ // голосовать можно только с положительным рейтингом
			return 2;
		}
		
		$votes = $man->UserVoteCountByDay();
	
		// кол-во голосов равно кол-ву репутации умноженной на 2
		$voteRepCount = intval($votes['blog']);
		if ($uRep->reputation*2 <= $voteRepCount){
			return 3;
		}
		
		// можно ли еще ставить голосо за топик?
		if ($eltype == 'topic'){
			$topic = $this->Topic($elid);
			if (empty($topic) || !$topic->IsVotingPeriod()){
				return null;
			}
			if ($topic->user->id == $this->userid){
				return 4;
			}
		}
	
		return 0;
	}
	
	/**
	 * Занести результат расчета репутации пользователя
	 *
	 * Метод вызывается из модуля urating
	 *
	 * @param string $eltype
	 * @param integer $elid
	 * @param array $vote
	 */
	public function URating_OnElementVoting($eltype, $elid, $info){
		
		if ($eltype == 'cat'){
			BlogTopicQuery::CategoryRatingUpdate($this->db, $elid, $info['cnt'], $info['up'], $info['down']);
		}else if($eltype == 'topic'){
			BlogTopicQuery::TopicRatingUpdate($this->db, $elid, $info['cnt'], $info['up'], $info['down']);
		}
	}
	
	/**
	 * Расчет рейтинга пользователя
	 *
	 * Метод запрашивает модуль URating
	 *
	 * +10 - за каждый положительный голос в репутацию
	 * -10 - за каждый отрицательный голос в репутацию
	 *
	 * @param integer $userid
	 */
	public function URating_UserCalculate($userid){
		
		// $rep = $this->UserReputation($userid);
	
		$ret = new stdClass();
		// $ret->skill = $rep->reputation * 10;
		return $ret;
	}	

	/**
	 * Есть ли разрешение на добавление комментария к топику?
	 * 
	 * Метод запрашивает модуль Comment
	 * 
	 * @param integer $contentid
	 */
	public function Comment_IsWrite($contentid){
		$topic = $this->Topic(0, $contentid);
		if (empty($topic)){ return false; }
		
		return true;
	}

	/**
	 * Есть ли разрешение на вывод списка комментариев?
	 *  
	 * Метод запрашивает модуль Comment
	 * 
	 * @param integer $contentid
	 */
	public function Comment_IsViewList($contentid){
		$topic = $this->Topic(0, $contentid);
		
		if (empty($topic)){ return false; }
		
		return true;
	}
	
	/**
	 * Отправить уведомление о новом комментарии.
	 *
	 * @param object $data
	 */
	public function Comment_SendNotify($data){
		// данные по комментарию:
		// $data->id	- идентификатор комментария
		// $data->pid	- идентификатор родительского комментария
		// $data->uid	- пользователь оставивший комментарий
		// $data->bd	- текст комментария
		// $data->cid	- идентификатор контента
	
		$topic = $this->Topic(0, $data->cid);
		if (empty($topic)){ return; }
		
		$brick = Brick::$builder->LoadBrickS('blog', 'templates', null, null);
		$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
		$tpLink = "http://".$host.$topic->URL();
	
		// уведомление "комментарий на комментарий"
		if ($data->pid > 0){
	
			$parent = CommentQuery::Comment($this->db, $data->pid, $data->cid, true);
			if ($parent['uid'] != $this->userid){
				$user = UserQuery::User($this->db, $parent['uid']);
				$email = $user['email'];
				if (!empty($email)){
					$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlsubject'], array(
						"tl" => $topic->title
					));
					$body = Brick::ReplaceVarByData($brick->param->var['cmtemlbody'], array(
						"email" => $email,
						"tpclnk" => $tpLink,
						"tl" => $topic->title,
						"unm" => $this->user->info['username'],
						"cmt1" => $parent['bd'],
						"cmt2" => $data->bd,
						"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
					));
					Abricos::Notify()->SendMail($email, $subject, $body);
				}
			}
			if ($parent['userid'] == $topic->user->id){
				// автору уже ушло уведомление, второе слать не имеет смысла
				return;
			}
		}
	
		// уведомление автору
		if ($topic->user->id == $this->userid){
			// свой комментарий в уведомление не нуждается
			return;
		}
		$autor = UserQuery::User($this->db, $topic->user->id);
		$email = $autor['email'];
		if (!empty($email)){
			$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlautorsubject'], array(
				"tl" => $topic->title
			));
			$body = Brick::ReplaceVarByData($brick->param->var['cmtemlautorbody'], array(
				"email" => $email,
				"tpclnk" => $tpLink,
				"tl" => $topic->title,
				"unm" => $this->user->info['username'],
				"cmt" => $data->bd,
				"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
			));
			Abricos::Notify()->SendMail($email, $subject, $body);
		}
	}
	
	
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	/*                  МЕТОДЫ НА УДАЛЕНИЕ/ПЕРЕРАБОТКУ               */
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	// TODO: Удалить

	public function SubscribeTopicCheck($sendlimit = 25){

		$cfgSPL = intval(Abricos::$config['module']['blog']['subscribeSendLimit']);
		if ($cfgSPL > 0){
			$sendlimit = $cfgSPL;
		}
		
		// Топик в блоге из очереди на рассылку
		$topic = BlogQuery::SubscribeTopic($this->db);
		if (empty($topic)){ return; }
		
		$gps = explode(",", $topic['grouplist']);
		if (count($gps) == 0){ return; }

		// полчить список пользователей для рассылки
		$users = array();
		$lastid = 0;
		$rows = BlogQuery::SubscribeUserList($this->db, $topic['catid'], $gps, $topic['scblastuserid'], $sendlimit);
		while (($u = $this->db->fetch_array($rows))){
			$lastid = max($u['id'], $lastid);

			if ($u['id'] == $topic['userid'] || empty($u['eml']) || $u['scboff']==1 || $u['scboffall']==1){
				continue; 
			}
			if (empty($u['pubkey'])){
				// Сам пользователь еще не подписан на блог. 
				// Необходимо его подписать и присвоить ключ отписки
				$u['pubkey'] = $this->SubscribeUserOnBlog($topic['catid'], $u['id']);
			}
			array_push($users, $u);
		}

		if ($lastid == 0){
			BlogQuery::SubscribeTopicComplete($this->db, $topic['topicid']);
		}else{
			BlogQuery::SubscribeTopicUpdate($this->db, $topic['topicid'], $lastid);
		}
		
		// осуществить рассылку
		for ($i=0; $i<count($users); $i++){
			$this->SubscribeTopicSend($topic, $users[$i]);
		}
	}
	
	private $_brickTemplates = null;
	
	private function UserNameBuild($user){
		$firstname = !empty($user['fnm']) ? $user['fnm'] : $user['firstname'];
		$lastname = !empty($user['lnm']) ? $user['lnm'] : $user['lastname'];
		$username = !empty($user['unm']) ? $user['unm'] : $user['username'];
		return (!empty($firstname) && !empty($lastname)) ? $firstname." ".$lastname : $username;
	}
	
	private function SubscribeTopicSend($topic, $user){
		$email = $user['eml'];
		if (empty($email)){ return; }
		
		if (is_null($this->_brickTemplates)){
			$this->_brickTemplates = Brick::$builder->LoadBrickS('blog', 'templates', null, null);
		}
		$brick = $this->_brickTemplates;
		
		$brick = Brick::$builder->LoadBrickS('blog', 'templates', null, null);
		
		$v = $brick->param->var;
		$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
		
		
		$tLnk = "http://".$host."/blog/".$topic['catname']."/".$topic['topicid']."/";
		$unLnkBlog = "http://".$host."/blog/_unsubscribe/".$user['id']."/".$user['pubkey']."/".$topic['catid']."/";
		$unLnkAll = "http://".$host."/blog/_unsubscribe/".$user['id']."/".$user['pubkey']."/all/";

		$subject = Brick::ReplaceVarByData($v['topicnewsubj'], array(
			"tl" => $topic['cattitle']
		));
		$body = Brick::ReplaceVarByData($v['topicnew'], array(
			"email" => $email,
			"blog" => $topic['cattitle'],
			"topic" => $topic['title'],
			"unm" => $this->UserNameBuild($topic),
			"tlnk" => $tLnk,
			"unlnkall" => $unLnkAll,
			"unlnkallblog" => $unLnkBlog,
			"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
		));
		Abricos::Notify()->SendMail($email, $subject, $body);
	}
	
	public function SubscribeUserOnBlog($catid, $userid){
		$pubkey = md5(TIMENOW.$catid.$userid);
		$id = BlogQuery::SubscribeUserOnBlog($this->db, $catid, $userid, $pubkey);
		return $pubkey;
	}
	
	private function TopicTagsUpdate($obj){
		$tagarr = array();
		$tags = explode(",", $obj->tags);
		foreach ($tags as $t){
			$t = trim($t);
			if (empty($t)){ continue; }
			$tagarr[$t]['phrase'] = $t;
			$tagarr[$t]['name'] = translateruen($t);
		}
		BlogQuery::TagSetId($this->db, $tagarr);
		BlogQuery::TagUpdate($this->db, $obj->id, $tagarr);
	}

	/**
	 * Список записей в блоге текущего пользователя
	 * @param integer $page
	 * @param integer $total
	 */
	public function TopicList_methodToRemove($page, $total){
		if (!$this->IsWriteRole()){ return null; }
		
		// отправить сообщения рассылки из очереди (подобие крона)
		$this->SubscribeTopicCheck();
		
		return BlogQuery::TopicListByUserId($this->db, $this->userid, $page, $total);
	}
	
	/**
	 * Кол-во записей в блоге текущего пользователя
	 */
	public function TopicListCount(){
		if (!$this->IsWriteRole()){ return null; }
		return BlogQuery::TopicCountByUserId($this->db, $this->userid); 
	}
	
	public function UserGroupList(){
		if (!$this->IsAdminRole()){ return null; }
		
		Abricos::$user->GetManager();
		
		return UserQueryExt::GroupList($this->db);
	}

	
	private function CategoryDataParse($d){
		$utm = Abricos::TextParser(true);
		$d->ph = $utm->Parser($d->ph);
		$d->nm = $utm->Parser($d->nm);
		
		if (empty($d->ph)){
			return null;
		}
		if (empty($d->nm)){
			$d->nm = translateruen($d->ph);
		}
		
		$arr = explode(",", $d->gps);
		$narr = array();
		for ($i=0; $i<count($arr); $i++){
			$n = intval($arr[$i]);
			if ($n > 0){
				array_push($narr, $n);
			}
		}
		$d->gps = implode(",", $narr);
		
		return $d;
	}
	

}

?>