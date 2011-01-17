<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage User
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

require_once 'dbquery.php';

class BlogManager extends ModuleManager {
	
	/**
	 * 
	 * @var BlogModule
	 */
	public $module = null;
	
	/**
	 * User
	 * @var User
	 */
	public $user = null;
	
	public $userid = 0;

	public function BlogManager(BlogModule $module){
		parent::ModuleManager($module);
		
		$this->user = CMSRegistry::$instance->user;
		$this->userid = $this->user->info['userid'];
	}
	
	public function IsAdminRole(){
		return $this->module->permission->CheckAction(BlogAction::BLOG_ADMIN) > 0;
	}
	
	public function IsWriteRole(){
		return $this->module->permission->CheckAction(BlogAction::TOPIC_WRITE) > 0;
	}
	
	public function IsViewRole(){
		return $this->module->permission->CheckAction(BlogAction::BLOG_VIEW) > 0;
	}
	
	public function AJAX($d){
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
				return $this->TopicList($p->page, $p->limit);
				
			case 'topiclistcount':
				$ret = array();
				array_push($ret, array("cnt" => $this->TopicListCount()));
				return $ret;
		}
		
		return null;
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
	
	public function TopicSave($d){
		$d->id = intval($d->id);
		$d->nm = translateruen($d->tl);
		$utm = CMSRegistry::$instance->GetUserTextManager();
		if (!$this->IsAdminRole()){
			$d->tl = $utm->Parser($d->tl);
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
		return $d->id;
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
	
	public function TopicList($page, $total){
		return BlogQuery::TopicListByUserId($this->db, $this->userid, $page, $total);
	}
	
	public function TopicListCount(){
		return BlogQuery::TopicCountByUserId($this->db, $this->userid); 
	}
	
	public function CategoryBlock (){
		if (!$this->IsViewRole()){ return; }
		return BlogQuery::CategoryBlock($this->db);
	}
	
	public function CategoryList(){
		if (!$this->IsViewRole()){ return; }
		return BlogQuery::CategoryList($this->db);
	}
	
	public function Category($categoryid){
		if (!$this->IsViewRole()){ return; }
		return BlogQuery::CategoryById($this->db, $categoryid, true);
	}
	
	public function CategoryAppend($d){
		if (!$this->IsAdminRole()){ return; }
		BlogQuery::CategoryAppend($this->db, $d);
	}
	
	public function CategoryUpdate($d){
		if (!$this->IsAdminRole()){ return; }
		BlogQuery::CategoryUpdate($this->db, $d);
	}
	
	public function CategoryRemove($id){
		if (!$this->IsAdminRole()){ return; }
		BlogQuery::CategoryRemove($this->db, $id);
	}
	
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
						"tpclnk" => $tpLink,
						"tl" => $topic['title'],
						"unm" => $this->user->info['username'],
						"cmt1" => $parent['bd'],
						"cmt2" => $data->bd,
						"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
					));
					CMSRegistry::$instance->GetNotification()->SendMail($email, $subject, $body);
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
				"tpclnk" => $tpLink,
				"tl" => $topic['title'],
				"unm" => $this->user->info['username'],
				"cmt" => $data->bd,
				"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
			));
			CMSRegistry::$instance->GetNotification()->SendMail($email, $subject, $body);
		}
	}
}

?>