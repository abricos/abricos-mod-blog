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
	
	/**
	 * Сокращенный текст записи
	 * @var string
	 */
	public $intro;
	
	/**
	 * Объем символов основного текста
	 * @var integer
	 */
	public $bodyLength;
	
	/**
	 * Идентификатор основного текста
	 * @var integer
	 */
	public $contentid;
	
	/**
	 * Метки (теги)
	 * @var array
	 */
	public $tags = array();
	
	/**
	 * Рейтинг топика
	 * @var integer
	 */
	public $rating;
	
	/**
	 * Количество голосов за рейтинг
	 * @var integer
	 */
	public $voteCount;
	
	/**
	 * Голос текущего пользователя
	 * null - нет голоса, -1 - ПРОТИВ, 1 - ЗА, 0 - Воздержался
	 * @var integer
	 */
	public $voteMy;
	
	
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
		$this->contentid	= $d['ctid'];

		$this->voteCount	= intval($d['vcnt']);
		$this->voteMy		= $d['vmy'];
		
		if ($this->user->id == Abricos::$user->id){
			$this->voteMy = 0;
		}
		
		if (!is_null($this->voteMy) || !$this->IsVotingPeriod()){
			$this->rating	= intval($d['rtg']); 

			// показать значение, значит запретить голосовать
			if (is_null($this->voteMy)){
				$this->voteMy = 0;
			}
		}else{
			// голосовать еще можно
			$this->rating	= null;
		}
	}
	
	/**
	 * Можно ли еще голосовать за топик
	 */
	public function IsVotingPeriod(){
		return $this->publicDate > TIMENOW-60*60*24*31;
	}
	
	/**
	 * @return BlogCategory
	 */
	public function Category(){
		$cats = BlogManager::$instance->CategoryList();
		return $cats->Get($this->catid);
	}
	public function URL(){
		$cat = $this->Category();
		return $cat->URL().$this->id."/";
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->catid		= $this->catid;
		$ret->tl		= $this->title;
		$ret->user		= $this->user->ToAJAX();
		$ret->intro		= $this->intro;
		$ret->bdlen		= $this->bodyLength;
		$ret->cmt		= $this->commentCount;
		$ret->ctid		= $this->contentid;
		$ret->dl		= $this->publicDate;
		
		$ret->rtg	= $this->rating;
		$ret->vcnt	= $this->voteCount;
		$ret->vmy	= $this->voteMy;
		
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
	
	/**
	 * Всего таких записей в базе
	 * @var integer
	 */
	public $total;
	
	/**
	 * Из них новых записей
	 * @var integer
	 */
	public $totalNew;
	
	public function __construct($list, $total=0, $totalNew=0){
		$this->list = $list;
		$this->total = $total;
		$this->totalNew = $totalNew;
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$list = array();
		for ($i=0;$i<count($this->list); $i++){
			array_push($list, $this->list[$i]->ToAJAX());
		}
		$ret->topics = new stdClass();
		$ret->topics->list = $list;
		$ret->topics->total = $this->total;
		$ret->topics->totalNew = $this->totalNew;
		
		return $ret;
	}
	
	public function Count(){
		return count($this->list);
	}
	
	/**
	 * @param integer $index
	 * @return BlogTopic
	 */
	public function GetByIndex($index){
		return $this->list[$index];
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
	
	public function GetUserName(){
		if (!empty($this->firstName) && !empty($this->lastName)){
			return $this->firstName." ".$this->lastName;
		}
		return $this->userName;
	}
	
	public function URL(){
		$mod = Abricos::GetModule('uprofile');
		if (empty($mod)){
			return "#";
		}
		return '/uprofile/#app=uprofile/ws/showws/'.$this->id.'/';
	}
	
	public function Avatar24(){
		$url = empty($this->avatar) ?
			'/modules/uprofile/images/nofoto24.gif' :
			'/filemanager/i/'.$this->avatar.'/w_24-h_24/avatar.gif';
		return '<img src="'.$url.'">';
	}
}

class BlogAuthor extends BlogUser {
	
	public $topicCount;
	public $reputation;
	public $rating;
	
	public function __construct($d){
		parent::__construct($d);
		$this->topicCount	= $d['tcnt']*1;
		$this->reputation	= $d['rep']*1;
		$this->rating		= $d['rtg']*1;
	}
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->tcnt = $this->topicCount;
		$ret->rep = $this->reputation;
		$ret->rtg = $this->rating;
		return $ret;
	}
}

class BlogAuthorList {

	public $list;

	public function __construct($list){
		$this->list = $list;
	}

	public function ToAJAX(){
		$ret = new stdClass();
		$ret->authors = array();
		for ($i=0;$i<count($this->list); $i++){
			array_push($ret->authors, $this->list[$i]->ToAJAX());
		}
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
	
	public function URL(){
		return "/blog/tag/".$this->title."/";
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
	 * Описание категории
	 * @var string
	 */
	public $descript;

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
	public $isAdminFlag;
	
	/**
	 * Текущий пользователь имеет права Модератора на эту категорию
	 * @var boolean
	 */
	public $isModerFlag;
	
	/**
	 * Текущий пользователь является членом категории
	 * @var boolean
	 */
	public $isMemberFlag;
	
	/**
	 * Рейтинг категории
	 * @var integer
	 */
	public $rating;
	
	/**
	 * Количество голосов за рейтинг
	 * @var integer
	 */
	public $voteCount;
	
	/**
	 * Голос текущего пользователя
	 * null - нет голоса, -1 - ПРОТИВ, 1 - ЗА, 0 - Воздержался
	 * @var integer
	 */
	public $voteMy;
	
	public function __construct($d){
		$this->id			= intval($d['id']);
		$this->title		= $d['tl'];
		$this->name			= $d['nm'];
		$this->descript		= $d['dsc'];
		$this->topicCount	= intval($d['tcnt']);
		$this->memberCount	= intval($d['mcnt']);
		$this->reputation	= intval($d['rep']);
		$this->isPrivate	= $d['prv']>0;
		$this->isAdminFlag		= $d['prm']>0;
		$this->isModerFlag		= $d['mdr']>0;
		$this->isMemberFlag		= $d['mbr']>0;
		
		$this->rating		= intval($d['rtg']);
		$this->voteCount	= intval($d['vcnt']);
		$this->voteMy		= $d['vmy'];
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id	= $this->id;
		$ret->tl	= $this->title;
		$ret->nm	= $this->name;
		$ret->dsc	= $this->descript;
		$ret->tcnt	= $this->topicCount;
		$ret->mcnt	= $this->memberCount;
		$ret->rep	= $this->reputation;
		$ret->prv	= $this->isPrivate?1:0;
		
		$ret->adm	= $this->isAdminFlag?1:0;
		$ret->mdr	= $this->isModerFlag?1:0;
		$ret->mbr	= $this->isMemberFlag?1:0;
		
		$ret->rtg	= $this->rating;
		$ret->vcnt	= $this->voteCount;
		$ret->vmy	= $this->voteMy;
		return $ret;
	}
	
	public function IsTopicWrite(){
		return $this->IsAdmin() || $this->isMemberFlag || $this->isModerFlag;
	}
	
	public function IsAdmin(){
		if (BlogManager::$instance->IsAdminRole()){ return true; }
		return $this->isAdminFlag;
	}
	
	public function URL(){
		return "/blog/".$this->name."/";
	}
}

class BlogCategoryList {

	private $list;
	private $map;

	public function __construct($list){
		$this->list = $list;
		$this->map = array();
		for ($i=0;$i<count($list);$i++){
			$this->map[$list[$i]->id] = $i;
		}
	}
	
	public function Count(){
		return count($this->list);
	}

	/**
	 * @param integer $id
	 * @return BlogCategory
	 */
	public function Get($id){
		$index = $this->map[$id];
		return $this->GetByIndex($index);
	}
	
	/**
	 * @param integer $index
	 * @return BlogCategory
	 */
	public function GetByIndex($index){
		return $this->list[$index];
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