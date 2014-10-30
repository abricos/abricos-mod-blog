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
	
	const TOPIC_PAGE_LIMIT = 10;

	/**
	 * @var BlogModule
	 */
	public static $instance = null;
	
	private $_manager = null;
	
	public function BlogModule(){
		// версия модуля
		$this->version = "0.5.2-dev";

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
	
	private $_cachepa = null;
	
	private function PageConvert($p){
		if (substr($p, 0, 4) == 'page'){
			$c = strlen($p);
			if ($c<=4){
				return 0;
			}
			return intval(substr($p, 4, $c-4));
		}
		return 0;
	}
	
	/**
	 * @return BlogParserAddress
	 */
	public function ParserAddress(){
		
		if (!empty($this->_cachepa)){ return $this->_cachepa; }

		$man = $this->GetManager();
		$pa = new BlogParserAddress();
		$pa->uri = "/".$this->takelink."/";
		
		$dir = Abricos::$adress->dir;
		$lvl = Abricos::$adress->level;
		
		for ($i=1;$i<$lvl;$i++){
			if ($this->PageConvert($dir[$i])>0){ break; }
			$pa->uri .= $dir[$i]."/";
		}

		$d1 = $dir[1]; $d2 = $dir[2]; $d3 = $dir[3]; $d4 = $dir[4];
		$page = 1;
		
		if ($lvl == 1){ //blog/
			
			$pa->type = 'topiclist';
			$pa->topicListFilter = "index";
			$pa->pageTitle = $this->lang['pagetitle']['index'];
			
		} else if ($d1 == '_unsubscribe'){
			
			$pa->type = 'unsubscribe';
			$pa->usbUserId = intval($d2);
			$pa->usbKey = $d3;
			$pa->usbCatId = $d4;
				
		} else if (($page=$this->PageConvert($d1)) > 0){ //blog/pageN/
			
			$pa->type = 'topiclist';
			$pa->topicListFilter = "index";
			$pa->page = $page;
			$pa->pageTitle = str_replace("{v#page}", $page, $this->lang['pagetitle']['indexpage']);
			
		} else if ($d1 == 'new'){ //blog/new/...
			
			$pa->type = 'topiclist';
			$pa->topicListFilter = "index/new";
			$pa->pageTitle = $this->lang['pagetitle']['indexnew'];
			
			if (($page=$this->PageConvert($d2)) > 0){ //blog/new/pageN/
				$pa->page = $page;
				$pa->pageTitle = str_replace("{v#page}", $page, $this->lang['pagetitle']['indexnewpage']);
			}
			
		} else if ($d1 == 'pub' || $d1 == 'pers'){ //blog/[pub|pers]/...
			
			$pa->type = 'topiclist';
			$pa->topicListFilter = $d1;
			$pa->pageTitle = $this->lang['pagetitle'][$d1 == 'pub' ? 'pub' : 'pers'];
			
			if ($d2 == 'new'){ //blog/[pub|pers]/new/
				$pa->topicListFilter = $d1."/new";

				$pa->pageTitle = $this->lang['pagetitle'][$d1=='pub'?'pubnew':'persnew'];
				
				if (($page=$this->PageConvert($d3)) > 0){ //blog/[pub|pers]/new/pageN/
					$pa->page = $page;
					$pa->pageTitle = str_replace("{v#page}", $page,
						$this->lang['pagetitle'][$d1 == 'pub' ? 'pubpagepage' : 'perspagepage']
					);
				}
			}else if (($page=$this->PageConvert($d2)) > 0){ //blog/[pub|pers]/pageN/
				$pa->page = $page;
				
				$pa->pageTitle = str_replace("{v#page}", $page,
					$this->lang['pagetitle'][$d1 == 'pub' ? 'pubpage' : 'perspage']
				);
			}

		} else if ($d1 == 'tag'){ //blog/[pub|pers]/...
			
			if ($lvl == 2){
				$pa->type = 'taglist';
			}else{
				$pa->type = 'tagview';
				$pa->topicListFilter = $d1."/".urldecode($d2);
				$pa->pageTitle = str_replace("{v#name}", urldecode($d2),
					$this->lang['pagetitle']['tag']
				);
			}

		} else if ($d1 == 'author'){
			$page=$this->PageConvert($d3);
			
			$pa->pageTitle = $this->lang['pagetitle']['authors'];

			if ($lvl == 2){//blog/author/
				// список авторов
				$pa->type = 'authorlist';
			}else if($lvl == 4 && $page == 0){//blog/author/%username%/%topicid%/
				
				$topicid = intval($d3);
				$topic = $man->Topic($topicid);
					
				if (empty($topic)){
					$pa->err404 = true;
				}else{
					$pa->type = 'topicview';
					$pa->topicListFilter = '';
					$pa->topic = $topic;
				
					// указать контентid для комментарий
					Brick::$contentId = $topic->contentid;
				}
				
			}else{//blog/author/%username%/
				$username = urldecode($d2);
				
				$pa->author = $man->AuthorByUserName($username);
				
				if (empty($pa->author)){
					$pa->err404 = true;
				}else{
					$pa->type = 'authorview';
					$pa->topicListFilter = "author/".$pa->author->id;
					$pa->pageTitle = str_replace("{v#name}", $username,
						$this->lang['pagetitle']['author']
					);
				}
			}

		} else if (!empty($d1)){ //blog/%category_name%/
			$cats = $man->CategoryList();
			$pa->cat = $cats->GetByName($d1);
			if (!empty($pa->cat)){
				$pa->type = 'catview';
				$pa->topicListFilter = "cat/".$pa->cat->id;
				
				$pa->pageTitle = str_replace("{v#name}", $pa->cat->title,
					$this->lang['pagetitle']['cat']
				);
				
				if ($d2 == 'new'){ //blog/%category_name%/new/
					$pa->topicListFilter .= "/new";
				
					if (($page=$this->PageConvert($d3)) > 0){ //blog/%category_name%/new/pageN/
						$pa->page = $page;
					}
				}else if (($page=$this->PageConvert($d2)) > 0){ //blog/%category_name%/pageN/
					$pa->page = $page;
					$pa->pageTitle = str_replace("{v#name}", $pa->cat->title,
						$this->lang['pagetitle']['catpage']
					);
					$pa->pageTitle = str_replace("{v#page}", $page,
						$pa->pageTitle
					);
				}else if ($lvl > 2){ //blog/%category_name%/%topicid%/
					
					$topicid = intval($d2);
					$topic = $man->Topic($topicid);
					
					if (empty($topic)){
						$pa->err404 = true;
					}else{
						$pa->type = 'topicview';
						$pa->topicListFilter = '';
						$pa->topic = $topic;
						
						// указать контентid для комментарий
						Brick::$contentId = $topic->contentid;
					}
				}
				
			}else{
				$pa->err404 = true;
			}
		}
		
		if (!empty($pa->topicListFilter)){
			$pa->topicList = $this->GetManager()->TopicList(array(
				"limit" => BlogModule::TOPIC_PAGE_LIMIT,
				"filter" => $pa->topicListFilter,
				"page" => $pa->page
			));
		}

		if (empty($pa->type) && !$pa->err404){
			$pa->type = 'topiclist'; 
		}
		
		$this->_cachepa = $pa;

		return $pa;
	}
	
	public function GetContentName(){
		$pa = $this->ParserAddress();
		if ($pa->err404){ return ''; } 
		
		return $pa->type;
	}

	public function GetLink(){
		return Abricos::$adress->host."/".$this->takelink."/";
	}
	
	public function RSS_GetItemList($inBosUI = false){
		$ret = array();
		
		$url = Abricos::$adress->host;
		if ($inBosUI){
			$url .= "/bos/#app=blog/wspace/ws/topic/TopicViewWidget/";
		} else {
			$url .= "/".$this->takelink."/";
		}
		
		
		$topics = $this->GetManager()->TopicList();
		
		for ($i=0;$i<$topics->Count();$i++){
			$topic = $topics->GetByIndex($i);
			$cat = $topic->Category();
			
			$title = $topic->title." / ".$cat->title;

			if ($inBosUI){
				$link = $url.$topic->id."/";
			}else{
				$link = $url.$cat->name."/".$topic->id."/";
			}
			$item = new RSSItem($title, $link, $topic->intro, $topic->publicDate);
			$item->modTitle = $this->lang['modtitle'];
			array_push($ret, $item);
		}
	
		return $ret;
	}

	public function RssMetaLink(){
		return Ab_URI::fetch_host()."/rss/blog/";
	}

    /**
     * This module added menu item in BOS Panel
     * @return bool
     */
    public function Bos_IsMenu(){
        return true;
    }


}

class BlogParserAddress {
	
	/**
	 * META заголовок страницы
	 * @var string
	 */
	public $pageTitle = '';
	
	public $type = '';
	public $page = 1;
	public $uri = '/blog/';
	public $err404 = false;
	
	/**
	 * Категория для type = 'categoryview'
	 * @var BlogCategory
	 */
	public $cat = null;
	
	/**
	 * @var BlogTopicList
	 */
	public $topicList = null;
	
	/**
	 * Фильтр для списка топиков
	 * @var string
	 */
	public $topicListFilter = "";
	
	/**
	 * @var BlogTopic
	 */
	public $topic = null;

	/**
	 * Автор
	 * @var BlogAuthor
	 */
	public $author = null;
	
	public $usbUserId = 0;
	
	public $usbKey = "";
	
	public $usbCatId = 0;
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