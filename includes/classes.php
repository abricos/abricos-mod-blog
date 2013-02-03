<?php 
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

/**
 * Информация о топике (записе в блоге)
 */
class BlogTopicInfo {

	/**
	 * Идентификатор топика
	 * @var integer
	 */
	public $id;

	/**
	 * Автор
	 * @var BlogUser
	 */
	public $user;
	
	/**
	 * Идентификатор категории
	 * @var integer
	 */
	public $catid;
	
	/**
	 * Черновик
	 * @var boolean
	 */
	public $isDraft;
	

	/**
	 * Дата публикации
	 * @var integer
	 */
	public $publicDate;

	/**
	 * Количество комментариев
	 * @var integer
	 */
	public $commentCount;
	
	/**
	 * Заголовок
	 * @var string
	 */
	public $title;
	
	
	public $intro;
	public $bodyLength;
	public $contentId;
	
	public $tags = array();
	
	public function __construct($d){
		$this->id			= $d['id'];
		$this->catid		= $d['catid'];
		
		$this->user			= new BlogUser($d);
		$this->isDraft		= $d['dft']>0;
		$this->publicDate	= intval($d['dl']);
		$this->commentCount	= intval($d['cmt']);
		
		$this->title		= $d['tl'];
		$this->intro		= $d['intro'];
		$this->bodyLength	= $d['bdlen'];
		$this->contentId	= $d['ctid'];
		
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
		$ret->dl		= $this->publicDate;
		
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
	
	/**
	 * Идентификатор категории
	 * @var integet
	 */
	public $id;
	
	/**
	 * Заголовок
	 * @var string
	 */
	public $title;
	
	/**
	 * Имя для формирования URL
	 * @var string
	 */
	public $name;

	/**
	 * Кол-во опубликованных топиков
	 * @var integer
	 */
	public $topicCount;
	
	/**
	 * Кол-во читателей
	 * @var integer
	 */
	public $memberCount;
	
	/**
	 * Необходимая репутация для записи в блог
	 * @var integer
	 */
	public $reputation;
	
	/**
	 * Закрытая категория
	 * @var boolean
	 */
	public $isPrivate;

	/**
	 * Текущий пользователь имеет права Админа на эту категорию
	 * @var boolean
	 */
	public $isAdmin;
	
	/**
	 * Текущий пользователь имеет права Модератора на эту категорию
	 * @var boolean
	 */
	public $isModer;
	
	/**
	 * Текущий пользователь является членом категории
	 * @var boolean
	 */
	public $isMember;
	
	public function __construct($d){
		$this->id = $d['id']*1;
		$this->title = $d['tl'];
		$this->name = $d['nm'];
		$this->topicCount = $d['tcnt']*1;
		$this->memberCount = $d['mcnt']*1;
		$this->reputation = $d['rep']*1;
		$this->isPrivate = $d['prv']>0;
		$this->isAdmin = $d['prm']>0;
		$this->isModer = $d['mdr']>0;
		$this->isMember= $d['mbr']>0;
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id = $this->id;
		$ret->tl = $this->title;
		$ret->nm = $this->name;
		$ret->tcnt = $this->topicCount;
		$ret->mcnt = $this->memberCount;
		$ret->rep = $this->reputation;
		$ret->prv = $this->isPrivate?1:0;
		$ret->adm = $this->isAdmin?1:0;
		$ret->mdr = $this->isModer?1:0;
		$ret->mbr = $this->isMember?1:0;
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

	/**
	 * Идентификатор категории
	 * @var integer
	 */
	public $catid = 0;
	
	/**
	 * Идентификатор пользователя
	 * @var integer
	 */
	public $userid = 0;
	
	/**
	 * Пользователь подписан на блог (является его членом)
	 * @var boolean
	 */
	public $isMemeber = false;
	
	/**
	 * Пользователь имеет права админа на категорию
	 * @var boolean
	 */
	public $isAdmin = false;
	
	/**
	 * Пользователь имеет права модератора на категорию
	 * @var boolean
	 */
	public $isModer = false;
	
	/**
	 * 
	 * @param integer $catid
	 * @param integer $userid
	 * @param array $d информация роли полученной из базы
	 */
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