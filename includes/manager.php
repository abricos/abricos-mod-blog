<?php
/**
 * @package Abricos
 * @subpackage
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
	
	private $_disableRoles = false;
	
	public function __construct($module){
		parent::__construct($module);
		
		BlogManager::$instance = $this;
	}
	
	public function DisableRoles(){
		$this->_disableRoles = true;
	}
	
	public function EnableRoles(){
		$this->_disableRoles = false;
	}
	
	public function IsAdminRole(){
		if ($this->_disableRoles){ return true; }
		return $this->IsRoleEnable(BlogAction::BLOG_ADMIN);
	}
	
	public function IsWriteRole(){
		if ($this->_disableRoles){ return true; }
		return $this->IsRoleEnable(BlogAction::TOPIC_WRITE);
	}
	
	public function IsViewRole(){
		if ($this->_disableRoles){ return true; }
		return $this->IsRoleEnable(BlogAction::BLOG_VIEW);
	}
	
	public function AJAX($d){

		switch($d->do){
			case "topic": 
				return $this->TopicToAJAX($d->topicid);
			case "topiclist": 
				return $this->TopicListToAJAX($d);
			case "categorylist": 
				return $this->CategoryListToAJAX();
			case "commentlivelist": 
				return $this->CommentLiveListToAJAX($d);
		}

		// TODO: Удалить
		return $this->AJAX_MethodToRemove($d);
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
		if (!$this->IsViewRole()){
			return null;
		}
		if (!is_object($cfg)){
			$cfg = new stdClass();
		}
		$cfg->page = max(intval($cfg->page), 1);
		$cfg->limit = max(1, min(50, intval($cfg->limit)));
		
		$topics = array();
		
		$rows = BlogTopicQuery::TopicList($this->db, $cfg->page, $cfg->limit);
		while (($row = $this->db->fetch_array($rows))){
			array_push($topics, new BlogTopicInfo($row));
		}
		
		$this->TopicSetTags($topics);
		
		return new BlogTopicList($topics);
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
	public function Topic($topicid){
		if (!$this->IsViewRole()){
			return null;
		}
	
		$row = BlogTopicQuery::Topic($this->db, $topicid);
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
	 * @return BlogCategoryList
	 */
	public function CategoryList(){
		if (!$this->IsViewRole()){
			return null;
		}
		
		$cats = array();
		$rows = BlogTopicQuery::CategoryList($this->db);
		while (($row = $this->db->fetch_array($rows))){
			array_push($cats, new BlogCategory($row));
		}
		
		return new BlogCategoryList($cats);
	}
	
	public function CategoryListToAJAX(){
		$catList = $this->CategoryList();
		if (is_null($catList)){
			return null;
		}
		return $catList->ToAJAX();
	}

	/**
	 * Прямой эфир
	 * @param object $cfg
	 * @return BlogCommentLiveList
	 */
	public function CommentLiveList($cfg){
		if (!$this->IsViewRole()){ return null; }
		
		if (!is_object($cfg)){
			$cfg = new stdClass();
		}
		$cfg->page = max(intval($cfg->page), 1);
		$cfg->limit = 5;
		
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
	
	
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	/*                  МЕТОДЫ НА УДАЛЕНИЕ/ПЕРЕРАБОТКУ               */
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	// TODO: Удалить
	
	public function AJAX_MethodToRemove($d){
		// старая версия, на переработку
		if ($d->type == 'topic'){
			switch($d->do){
				case "save": return $this->TopicSave($d->data);
				case "remove": return $this->TopicRemove($d->id);
				case "restore": return $this->TopicRestore($d->id);
				case "publish": return $this->TopicPublish($d->id);
				case "rclear": return $this->TopicRecycleClear();
				default: return $this->Topic($d->id);
			}
		}else if ($d->type == 'category'){
			switch($d->do){
				case "save": return $this->CategorySave($d->data);
				default: return $this->Category($d->id);
			}
		}else {
			switch($d->do){
				case "boardinit": return $this->BoardInit();
				case "boardtopic": return $this->BoardTopic($d->topicid);
			}
		}
		return -1;
	}
	
	
	public function DSProcess($name, $rows){
		$p = $rows->p;
		$db = $this->db;
		
		switch ($name){
			case 'categorylist':
				foreach ($rows as $r){
					if ($r->f == 'a'){ $this->CategoryAppend($r->d); }
					if ($r->f == 'u'){ $this->CategoryUpdate($r->d); }
					if ($r->f == 'd'){ $this->CategoryRemove($r->d->id); }
				}
				break;
		}
	}
	
	public function DSGetData($name, $rows){
		
		$p = $rows->p;
		$db = $this->db;
		
		switch ($name){
			case 'categorylist':
				return $this->CategoryList();
			
			case 'topiclist':
				return $this->TopicList_methodToRemove($p->page, $p->limit);
				
			case 'topiclistcount':
				$ret = array();
				array_push($ret, array("cnt" => $this->TopicListCount()));
				return $ret;
				
			case 'grouplist':
				return $this->UserGroupList();
		}
		
		return null;
	}
	
	
	
	public function Bos_OnlineData(){
		return $this->BoardInit(5);
	}
	
	/**
	 * Данные инициализации приложения
	 */
	public function BoardInit($limit = 15){
		if (!$this->IsViewRole()){ return null; }
		
		$ret =  $this->BoardData(0, $limit, -1);
		
		$ret->categories = array();
		$rows = BlogQueryApp::CategoryList($this->db);
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret->categories, $row);
		}
		
		return $ret;
	}
	
	private function BoardData($page, $limit, $topicid = -1){
		$ret =  new stdClass();
		
		$ret->topics = array();
		$rows = BlogQueryApp::TopicList($this->db, $page, $limit, $topicid);
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret->topics, $row);
		}
		
		$ret->tags = array();
		$rows = BlogQueryApp::TagList($this->db, $page, $limit, $topicid);
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret->tags, $row);
		}

		$ret->toptags = array();
		$rows = BlogQueryApp::TopicTagList($this->db, $page, $limit, $topicid);
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret->toptags, $row);
		}
		
		$ret->users = array();
		$rows = BlogQueryApp::TopicUserList($this->db, $page, $limit, $topicid);
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret->users, $row);
		}
		
		// отправить сообщения рассылки из очереди (подобие крона)
		$this->SubscribeTopicCheck();
		
		return $ret;
	}
	
	public function BoardTopic($topicid){
		if (!$this->IsViewRole()){ return null; }
		$ret =  $this->BoardData(0, 1, $topicid);
		
		// отправить сообщения рассылки из очереди (подобие крона)
		$this->SubscribeTopicCheck();
		
		return $ret;
	}

	/**
	 * Есть ли доступ к записи в блоге?
	 * @param integer $topicid идентификатор записи в блоге
	 * @param integer $type тип действия (0-чтение, 1-изменение/удаление)
	 */
	public function TopicAccess($topicid, $type=0){
		// админу можно все
		if ($this->IsAdminRole()){ return true; }

		$info = BlogQuery::TopicInfo($this->db, $topicid);
		// автору можно все, если конечно же роли позволят
		if ($info['userid'] == $this->userid){
			return ($type == 0 && $this->IsViewRole()) || ($type == 1 && $this->IsWriteRole());
		}
		return $type == 0 && $this->IsViewRole() && $info['status'] == BlogTopicStatus::PUBLISH;
	}
	
	/**
	 * Получить запись в блоге
	 * @param integer $topicId идентификатор записи
	 */
	/*
	public function Topic($topicid){
		if (!$this->TopicAccess($topicid, 0)){
			return null;
		}
		$db = $this->db;
		$topic = BlogQuery::Topic($db, $topicid);
		
		$rows = BlogQuery::Tags($db, $topicid);
		$tags = array();
		while (($row = $db->fetch_array($rows))){
			array_push($tags, $row['ph']);
		}
		$topic['tags'] = implode(', ', $tags);
		
		return $topic;	
	}
	/**/
	
	public function TopicSave($d){
		$d->id = intval($d->id);
		$d->nm = translateruen($d->tl);
		$utm = Abricos::TextParser();
		$utmf = Abricos::TextParser(true);
		if (!$this->IsAdminRole()){
			$d->tl = $utmf->Parser($d->tl);
			$d->intro = $utm->Parser($d->intro);
			$d->body = $utm->Parser($d->body);
		}
		
		if ($d->st == 1 && empty($d->dp)){
			$d->dp = TIMENOW;
		}else if (empty($obj->st)){
			$d->dp = 0;
		}
		$d->de = TIMENOW;
		
		if ($d->id > 0) {
			if (!$this->TopicAccess($d->id, 1)){ return; }
			$info = BlogQuery::TopicInfo($this->db, $d->id);
			$d->cid = $info['contentid'];
			BlogQuery::TopicUpdate($this->db, $d);
		}else{
			if (!$this->IsWriteRole()){ return; }
			$d->uid = $this->userid;
			$d->dl = TIMENOW;
			$d->id = BlogQuery::TopicAppend($this->db, $d); 
		}
		$this->TopicTagsUpdate($d);
		
		if ($d->dp > 0){
			$this->SubscribeTopicCheck(250);
		}
		
		return $d->id;
	}
	
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
	
	public function TopicRemove($topicid){
		if (!$this->TopicAccess($topicid, 1)){ return; }
		
		BlogQuery::TopicRemove($this->db, $topicid);
	}
	
	public function TopicRestore($topicid){
		if (!$this->TopicAccess($topicid, 1)){ return; }
		BlogQuery::TopicRestore($this->db, $topicid);
	}
	
	public function TopicRecycleClear(){
		if (!$this->IsWriteRole()){ return; }
		BlogQuery::TopicRecycleClear($this->db, $this->userid);
	}
	
	public function TopicPublish($topicid){
		if (!$this->TopicAccess($topicid, 1)){ return; }
		BlogQuery::TopicPublish($this->db, $topicid);
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
	
	public function CategoryBlock (){
		if (!$this->IsViewRole()){ return null; }
		return BlogQuery::CategoryBlock($this->db);
	}
	
	public function Category($categoryid){
		if (!$this->IsViewRole()){ return null; }
		return BlogQuery::CategoryById($this->db, $categoryid, true);
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
	
	public function CategoryAppend($d){
		if (!$this->IsAdminRole()){ return null; }
		
		$d = $this->CategoryDataParse($d);
		if (is_null($d)){ return null; }
		
		return BlogQuery::CategoryAppend($this->db, $d);
	}
	
	public function CategoryUpdate($d){
		if (!$this->IsAdminRole()){ return null; }
		
		$d = $this->CategoryDataParse($d);
		if (is_null($d)){ return null; }
		
		BlogQuery::CategoryUpdate($this->db, $d);
	}
	
	public function CategoryRemove($id){
		if (!$this->IsAdminRole()){ return null; }
		BlogQuery::CategoryRemove($this->db, $id);
	}
	
	public function Page($category, $tagid, $from, $count){
		if (!$this->IsViewRole()){ return null; }
		
		return BlogQuery::Page(Abricos::$db, $category, $tagid, $from, $count);
	}
	
	public function TopicLastList($count){
		if (!$this->IsViewRole()){ return null; }
		return BlogQuery::Page($this->db, "", "", 0, $count);
	}
	
	/**
	 * Список последних комментариев
	 * @param integer $count вернуть кол-во $count
	 */
	/*
	public function CommentLive($count){
		if (!$this->IsViewRole()){ return null; }
		return BlogQuery::CommentLive($this->db, $count);
	}
	/**/
	
	// комментарии
	public function IsCommentList($contentid){
		return true;
	}
	
	public function IsCommentAppend($contentid){
		return true;
	}
	
	/**
	 * Отправить уведомление о новом комментарии.
	 * 
	 * @param object $data
	 */
	public function CommentSendNotify($data){
		// данные по комментарию:
		// $data->id	- идентификатор комментария
		// $data->pid	- идентификатор родительского комментария
		// $data->uid	- пользователь оставивший комментарий
		// $data->bd	- текст комментария
		// $data->cid	- идентификатор контента
		
		
		$topic = BlogQuery::TopicInfo($this->db, 0, $data->cid);
		if (empty($topic)){ return; }
		
		$brick = Brick::$builder->LoadBrickS('blog', 'templates', null, null);
		$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
		$tpLink = "http://".$host.$this->module->GetTopicLink($topic);
		
		// уведомление "комментарий на комментарий"
		if ($data->pid > 0){ 

			$parent = CommentQuery::Comment($this->db, $data->pid, $data->cid, true);
			if ($parent['uid'] != $this->userid){ 
				$user = UserQuery::User($this->db, $parent['uid']);
				$email = $user['email'];
				if (!empty($email)){ 
					$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlsubject'], array(
						"tl" => $topic['title']
					));
					$body = Brick::ReplaceVarByData($brick->param->var['cmtemlbody'], array(
						"email" => $email,
						"tpclnk" => $tpLink,
						"tl" => $topic['title'],
						"unm" => $this->user->info['username'],
						"cmt1" => $parent['bd'],
						"cmt2" => $data->bd,
						"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
					));
					Abricos::Notify()->SendMail($email, $subject, $body);
				}
			}
			if ($parent['userid'] == $topic['userid']){
				// автору уже ушло уведомление, второе слать не имеет смысла 
				return; 
			}
		}
		
		// уведомление автору
		if ($topic['userid'] == $this->userid){
			// свой комментарий в уведомление не нуждается 
			return;
		}
		$autor = UserQuery::User($this->db, $topic['userid']);
		$email = $autor['email'];
		if (!empty($email)){ 
			$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlautorsubject'], array(
				"tl" => $topic['title']
			));
			$body = Brick::ReplaceVarByData($brick->param->var['cmtemlautorbody'], array(
				"email" => $email,
				"tpclnk" => $tpLink,
				"tl" => $topic['title'],
				"unm" => $this->user->info['username'],
				"cmt" => $data->bd,
				"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
			));
			Abricos::Notify()->SendMail($email, $subject, $body);
		}
	}
}

?>