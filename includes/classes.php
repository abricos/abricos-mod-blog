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
	public $userid;
	public $pubDate;
	public $intro;
	public $commentCount;
	
	public function __construct(BlogManager $manager, $d){
		$this->manager = $manager;
		
		$id = $d['id'];
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
		$ret->tl		= $this->title;
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
	
	public $topics = array();
	
	public function __construct(){
		
		
	}
	
	/*
	public function Update($rows){
		$man = BlogManager::$instance;
		$list = array();
		while (($row = $man->db->fetch_array($rows))){
			array_push($list, new Topic($man, $row));
		}
		$this->list = $list;
	}
	/**/
	
	public function ToAJAX(){
		/*
		$ret = array();
		
		for ($i=0;$i<count($this->list); $i++){
			array_push($ret, $this->list[$i]->ToAJAX());
		}
		return $ret;
		/**/
	}
	
}



class Tag {
	
}

class Category {
	
}

?>