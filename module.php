<?php
/**
 * Модуль "Блог"
 * 
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

Abricos::GetModule('comment');

/**
 * Модуль "Блог" 
 * @package Abricos
 * @subpackage Blog
 */
class BlogModule extends Ab_Module {

	/**
	 * @var BlogModule
	 */
	public static $instance = null;
	
	private $_manager = null;
	
	public function BlogModule(){
		// версия модуля
		$this->version = "0.5";

		// имя модуля 
		$this->name = "blog";

		$this->takelink = "blog";
		
		$this->permission = new BlogPermission($this);
		
		BlogModule::$instance = $this;
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
	
	public function GetContentName(){
		$cname = 'topiclist';
		
		$dir = Abricos::$adress->dir;
		
		switch($dir[1]){
			case 'new':
			case 'pub':
			case 'pers':
							
			break;
		}
		
		
		return $cname;
	}
	
	
	/*
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
			if ($p1 == '_unsubscribe'){
				$cname = 'unsubscribe';
			}else if ($p1 == 'tag'){
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
	
	public function GetLink(){
		return $this->registry->adress->host."/".$this->takelink."/";
	}
	
	public function RSS_GetItemList($inBosUI = false){
		$ret = array();
		
		$url = $this->registry->adress->host;
		if ($inBosUI){
			$url .= "/bos/#app=blog/topiclist/showTopicViewPanel/";
		} else {
			$url .= "/".$this->takelink."/";
		}
		
		$manager = $this->GetManager();
		$rows = $manager->TopicLastList(10);
		while (($row = $this->registry->db->fetch_array($rows))){
			$title = $row['catph']." / ".$row['tl'];
				
			if ($inBosUI){
				$link = $url.$row['id']."/";
			}else{
				$link = $url.$row['catnm']."/".$row['id']."/";
			}
			$item = new RSSItem($title, $link, $row['intro'], $row['dp']);
			$item->modTitle = $this->lang['modtitle'];
			array_push($ret, $item);
		}
		return $ret;
	}
	
	public function RssMetaLink(){
		return $this->registry->adress->host."/rss/blog/";
	}
	/**/
	
}

class BlogAction {
	const VIEW	= 10;
	const WRITE	= 20;
	const ADMIN	= 50;
}

/**
 * Роли пользователей в блоге
 * 
 * Если пользователь не админ и есть доступ на запись, то:
 * 		Если устнавлена система репутации, то запись определяется уровнем репутации,
 * 		Иначе вся запись попадает под модерацию админа
 * 
 */
class BlogPermission extends Ab_UserPermission {

	public function BlogPermission(BlogModule $module){
		
		$defRoles = array(
			new Ab_UserRole(BlogAction::VIEW, Ab_UserGroup::GUEST),
			new Ab_UserRole(BlogAction::VIEW, Ab_UserGroup::REGISTERED),
			new Ab_UserRole(BlogAction::VIEW, Ab_UserGroup::ADMIN),

			new Ab_UserRole(BlogAction::WRITE, Ab_UserGroup::ADMIN),

			new Ab_UserRole(BlogAction::ADMIN, Ab_UserGroup::ADMIN),
		);
		
		// Если есть модуль рейтинг пользователя, то разрешить роль 
		// пользователю на публикацию топиков
		$modURating = Abricos::GetModule('urating');
		if (!empty($modURating)){
			array_push($defRoles, new Ab_UserRole(BlogAction::WRITE, Ab_UserGroup::REGISTERED));
		}
		
		parent::__construct($module, $defRoles);
	}

	public function GetRoles(){
		return array(
			BlogAction::VIEW => $this->CheckAction(BlogAction::VIEW),
			BlogAction::WRITE => $this->CheckAction(BlogAction::WRITE),
			BlogAction::ADMIN => $this->CheckAction(BlogAction::ADMIN)
		);
	}
}
Abricos::ModuleRegister(new BlogModule());


?>