<?php 
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

class Topic {
	
	/**
	 * @var BlogManager
	 */
	public $manager;
	
	public $id;
	public $catid;
	public $title;
	public $authorid;
	public $pubDate;
	public $intro;
	public $bodyLength;
	public $commentCount;
	public $contentId;
	
	public $tags = array();
	
	public function __construct($d){
		$this->manager = BlogManager::$instance;
		
		$this->id			= $d['id'];
		$this->catid		= $d['catid'];
		$this->title		= $d['tl'];
		$this->authorid		= $d['uid'];
		$this->intro		= $d['intro'];
		$this->bodyLength	= $d['bdlen'];
		$this->commentCount	= $d['cmt'];
		$this->contentId	= $d['ctid'];
		$this->pubDate		= $d['dl'];
	}
	
	public function Extend(){
		return new TopicExtended($this);
	}
	
	public function CloneToExtend(TeamExtended $teamex){
		$objs = get_class_vars(get_class($this));
		foreach($objs as $key => $val){
			$teamex->$key = $this->$key;
		}
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->catid		= $this->catid;
		$ret->tl		= $this->title;
		$ret->uid		= $this->authorid;
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

class TopicExtended extends Topic {

	/**
	 * @var Ab_Database
	 */
	public $db;
	
	/**
	 * @var User
	 */
	public $user;
	
	/**
	 * @var integer
	 */
	public $userid;
	
	public function __construct(Topic $topic){
		$topic->CloneToExtend($this);
		
		$man = $topic->manager;
		$this->db = $man->db;
		$this->user = $man->user;
		$this->userid = $man->userid;
	}
	
	public function Extend(){
		return $this;
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->extended = true;
	
		return $ret;
	}
	
}

class TopicList {
	
	public $topics;
	public $users;
	
	public function __construct($topics, $users){
		$this->topics = $topics;
		$this->users = $users;
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->topics = array();
		for ($i=0;$i<count($this->topics); $i++){
			array_push($ret->topics, $this->topics[$i]->ToAJAX());
		}
		$ret->users = $this->users;
		return $ret;
	}
	
}



class TopicTag {
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

class Category {
	
}


?>