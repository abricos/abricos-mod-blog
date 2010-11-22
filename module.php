<?php
/**
 * Модуль "Блог"
 * 
 * @version $Id$
 * @package Abricos
 * @subpackage Blog
 * @copyright Copyright (C) 2008 Abricos All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

CMSRegistry::$instance->modules->GetModule('comment');
$mod = new BlogModule();
CMSRegistry::$instance->modules->Register($mod);

/**
 * Модуль "Блог" 
 * @package Abricos
 * @subpackage Blog
 */
class BlogModule extends CMSModule {
	

	// @TODO: на удаление
	private static $instance = null;
	
	public $topicid; 
	public $topicinfo;
	public $page = 1;
	public $baseUrl = "";
	public $category = "";
	public $tag = "";
	public $taglimit = 50;
	
	private $_manager = null;
	
	public function BlogModule(){
		// версия модуля
		$this->version = "0.4.1";

		// имя модуля 
		$this->name = "blog";

		$this->takelink = "blog";
		
		$this->permission = new BlogPermission($this);
	}
	
	/**
	 * @return BlogManager
	 */
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once 'includes/manager.php';
			$this->_manager = new BlogManager($this);
		}
		return $this->_manager;
	}
	
	private function IsPage($p){
		if (substr($p, 0, 4) == 'page'){
			$c = strlen($p);
			if ($c<=4){ return -1; }
			return intval(substr($p, 4, $c-4));
		}
		return -1;
	}
	
	public function GetTopicLink($topic){
		return "/blog/".$topic['catnm']."/".$topic['topicid']."/";
	}
	
	public function GetContentName(){
		$manager = $this->GetManager();
		$adress = $this->registry->adress;
		$cname = '';
		$baseUrl = "/".$this->takelink."/";
		
		if ($adress->level <= 1){
			$this->baseUrl = $baseUrl;
			$cname = 'index';
		}else if ($adress->level==2){
			$p = $adress->dir[1];
			
			if ($p == 'tag'){
				$cname = 'taglist';
				$this->taglimit = 0;
			}else{
				$numpage = $this->IsPage($p); 
				$cname = 'index';
				if ($numpage>-1){
					$this->baseUrl = $baseUrl;
					$this->page = $numpage;
				}else{
					$this->baseUrl = $baseUrl.$p."/";
					$this->category = $p;
				}
			}
		}else if ($adress->level >= 3){
			$p = $adress->dir[2];
			$p1 = $adress->dir[1];
			$baseUrl = $baseUrl.$adress->dir[1]."/";
			if ($p1 == 'tag'){
				$this->tag = $p;
				$cname = 'index';
				$this->baseUrl = $baseUrl.$adress->dir[2]."/";
				if ($adress->level > 3){
					$numpage = $this->IsPage($adress->dir[3]);
					if ($numpage > -1){
						$this->page = $numpage;
					}
				}
			}else{
				$numpage = $this->IsPage($p); 
				if ($numpage>-1){
					$cname = 'index';
					$this->baseUrl = $baseUrl;
					$this->page = $numpage;
					$this->category = $adress->dir[1];
				}else{
					$this->topicid = intval($adress->dir[2]);
					$this->topicinfo = BlogQuery::TopicInfo($this->registry->db, $this->topicid);
					if (!empty($this->topicinfo)){
						Brick::$contentId = $this->topicinfo['contentid'];
						$cname = 'topic';
					}
				}
			}
		}
		if ($cname == ''){
			$this->registry->SetPageStatus(PAGESTATUS_404);
		}
		return $cname;
	}
	
	// Отправить подписчикам уведомление по почте 
	public function OnComment(){
		Brick::$builder->LoadBrickS('blog', 'cmtmailer', null);
	}
	
}

/**
 * Статус записи в блоге 
 */
class BlogTopicStatus {
	/**
	 * Черновик
	 * @var integer
	 */
	const DRAFT = 0;
	
	/**
	 * Опубликова
	 * @var integer
	 */
	const PUBLISH = 1; 
}

class BlogAction {
	const BLOG_VIEW = 10;
	const TOPIC_WRITE = 20;
	const BLOG_ADMIN = 50;
}

class BlogPermission extends CMSPermission {
	
	public function BlogPermission(BlogModule $module){
		
		$defRoles = array(
			new CMSRole(BlogAction::BLOG_VIEW, 1, User::UG_GUEST),
			new CMSRole(BlogAction::BLOG_VIEW, 1, User::UG_REGISTERED),
			new CMSRole(BlogAction::BLOG_VIEW, 1, User::UG_ADMIN),
			
			new CMSRole(BlogAction::TOPIC_WRITE, 1, User::UG_ADMIN),
			
			new CMSRole(BlogAction::BLOG_ADMIN, 1, User::UG_ADMIN)
		);
		
		parent::CMSPermission($module, $defRoles);
	}
	
	public function GetRoles(){
		$roles = array();
		$roles[BlogAction::BLOG_VIEW] = $this->CheckAction(BlogAction::BLOG_VIEW);
		$roles[BlogAction::TOPIC_WRITE] = $this->CheckAction(BlogAction::TOPIC_WRITE);
		$roles[BlogAction::BLOG_ADMIN] = $this->CheckAction(BlogAction::BLOG_ADMIN);
		return $roles;
	}
}

?>