<?php 
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

/**
 * Информация о записе в блоге
 */
class BlogTopicInfo {
	
	/**
	 * @var BlogManager
	 */
	public $manager;
	
	public $id;
	public $catid;
	public $title;
	public $pubDate;
	public $intro;
	public $bodyLength;
	public $commentCount;
	public $contentId;
	
	public $tags = array();
	public $userid;
	/**
	 * @var BlogUser
	 */
	public $user;
	
	public function __construct($d){
		$this->manager = BlogManager::$instance;
		
		$this->id			= $d['id'];
		$this->catid		= $d['catid'];
		$this->title		= $d['tl'];
		$this->userid		= $d['uid'];
		$this->intro		= $d['intro'];
		$this->bodyLength	= $d['bdlen'];
		$this->commentCount	= $d['cmt'];
		$this->contentId	= $d['ctid'];
		$this->pubDate		= $d['dl'];
		
		$this->user = new BlogUser($d);
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->catid		= $this->catid;
		$ret->tl		= $this->title;
		$ret->uid		= $this->userid;
		$ret->user		= $this->user->ToAJAX();
		$ret->intro		= $this->intro;
		$ret->bdlen		= $this->bodyLength;
		$ret->cmt		= $this->commentCount;
		$ret->ctid		= $this->contentId;
		$ret->dl		= $this->pubDate;
		
		$ret->tags = array();
		for ($i=0;$i<count($this->tags);$i++){
			array_push($ret->tags, $this->tags[$i]->ToAJAX());
		}
		return $ret;
	}
}

/**
 * Запись в блоге
 */
class BlogTopic extends BlogTopicInfo {

	public $body;
	
	public function __construct($d){
		parent::__construct($d);
		$this->body = $d['bd'];
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->bd = $this->body;
		return $ret;
	}
}

class BlogTopicList {
	
	public $list;
	
	public function __construct($list){
		$this->list = $list;
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->topics = array();
		for ($i=0;$i<count($this->list); $i++){
			array_push($ret->topics, $this->list[$i]->ToAJAX());
		}
		return $ret;
	}
}

class BlogUser {
	public $id;
	public $userName;
	public $avatar;
	public $firstName;
	public $lastName;
	
	public function __construct($d){
		$this->id			= intval($d['uid'])>0 ? $d['uid'] : $d['id'];
		$this->userName		= $d['unm'];
		$this->avatar		= $d['avt'];
		$this->firstName	= $d['fnm'];
		$this->lastName		= $d['lnm'];
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id = $this->id;
		$ret->unm = $this->userName;
		$ret->avt = $this->avatar;
		$ret->fnm = $this->firstName;
		$ret->lnm = $this->lastName;
		return $ret;
	}
}


class BlogTopicTag {
	public $id;
	public $title;
	public $name;
	
	public function __construct($d){
		$this->id = $d['id'];
		$this->title = $d['tl'];
		$this->name = $d['nm'];
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id = $this->id;
		$ret->tl = $this->title;
		$ret->nm = $this->name;
		return $ret;
	}
}

class BlogCategory {
	public $id;
	public $title;
	public $name;
	
	public function __construct($d){
		$this->id = $d['id'];
		$this->title = $d['tl'];
		$this->name = $d['nm'];
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id = $this->id;
		$ret->tl = $this->title;
		$ret->nm = $this->name;
		return $ret;
	}
}

class BlogCategoryList {

	public $list;

	public function __construct($list){
		$this->list = $list;
	}

	public function ToAJAX(){
		$ret = new stdClass();
		$ret->categories = array();
		for ($i=0;$i<count($this->list); $i++){
			array_push($ret->categories, $this->list[$i]->ToAJAX());
		}
		return $ret;
	}
}

/**
 * Роль пользователя в категории
 */
class BlogCategoryUserRole {

	public $catid = 0;
	public $userid = 0;
	
	public $isMemeber = false;
	public $isAdmin = false;
	public $isModer = false;
	
	
	public function __construct($catid, $userid, $d){
		$this->catid = $catid;
		$this->userid = $userid;
		
		$this->isMemeber = $d['mbr']==1;
		$this->isAdmin = $d['adm'];
		$this->isModer = $d['mdr'];
	}
}

class BlogCommentLive {
	/**
	 * Идентификатор комментария
	 * @var integer
	 */
	public $id;
	public $topicid;
	public $body;
	public $date;
	
	/**
	 * @var BlogUser
	 */
	public $user;
	
	/**
	 * @var BlogTopicInfo
	 */
	public $topic;
	
	public function __construct($d){
		$this->id			= $d['id'];
		$this->topicid		= $d['tid'];
		$this->body			= $d['body'];
		$this->date			= $d['dl'];
		$this->user = new BlogUser($d);
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id = $this->id;
		$ret->bd = $this->body;
		$ret->dl = $this->date;
		$ret->topic = $this->topic->ToAJAX();
		$ret->user = $this->user->ToAJAX();
		return $ret;
	}
}

class BlogCommentLiveList {

	public $list;

	public function __construct($list){
		$this->list = $list;
	}

	public function ToAJAX(){
		$ret = new stdClass();
		$ret->comments = array();
		for ($i=0;$i<count($this->list); $i++){
			array_push($ret->comments, $this->list[$i]->ToAJAX());
		}
		return $ret;
	}
}



?>