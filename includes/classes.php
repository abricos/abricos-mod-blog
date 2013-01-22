<?php 
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

class BlogTopicManager {
	
	/**
	 * @var BlogManager
	 */
	public $blogManager = null;
	
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
	
	public function __construct(BlogManager $manager){
		$this->blogManager = $manager;
		$this->db = $manager->db;
		$this->user = $manager->user;
		$this->userid = $manager->userid;
	}
	
	public function IsAdminRole(){ return $this->blogManager->IsAdminRole(); }
	public function IsWriteRole(){ return $this->blogManager->IsWriteRole(); }
	public function IsViewRole(){ return $this->blogManager->IsViewRole(); }
	
	public final function AJAX($d){
		switch($d->do){
			case 'topiclist':	return $this->TopicList($d->cfg);
		}
		return null;
	}
	
}

class Topic {
	
	/**
	 * @var BlogTopicManager
	 */
	public $manager;
	
	public $id;
	public $catid;
	public $title;
	public $userid;
	public $pubDate;
	public $intro;
	public $commentCount;
	
	public function __construct(BlogTopicManager $manager, $d){
		$this->manager = $manager;
		
		$id = $d['id'];
	}
	
	public function Extend(){
		return new TopicExtend();
	}
}

class TopicExtended extends Topic {
	
}



class Tag {
	
}

class Category {
	
}

?>