<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Информация о топике (записе в блоге)
 */
class BlogTopicInfo {

    /**
     * Идентификатор топика
     *
     * @var integer
     */
    public $id;

    /**
     * Author ID
     *
     * @var int
     */
    public $userid;

    /**
     * Автор
     *
     * @var UProfileUser
     */
    public $user;

    /**
     * Идентификатор категории
     *
     * @var integer
     */
    public $catid;

    /**
     * Черновик
     *
     * @var boolean
     */
    public $isDraft;

    /**
     * Топик выведен на главную
     *
     * @var boolean
     */
    public $isIndex;

    /**
     * Автоматически присваивать индекс главной (исходя из рейтинга)
     *
     * @var boolean
     */
    public $isAutoIndex;

    /**
     * Дата публикации
     *
     * @var integer
     */
    public $publicDate;

    /**
     * Заголовок
     *
     * @var string
     */
    public $title;

    /**
     * Сокращенный текст записи
     *
     * @var string
     */
    public $intro;

    /**
     * Объем символов основного текста
     *
     * @var integer
     */
    public $bodyLength;

    /**
     * Метки (теги)
     *
     * @var array
     */
    public $tags = array();

    /**
     * @var CommentStatistic
     */
    public $commentStatistic;

    /**
     * @var URatingVoting
     */
    public $voting;

    public function __construct($d){
        $this->id = isset($d['id']) ? intval($d['id']) : 0;
        $this->catid = intval($d['catid']);
        $this->userid = intval($d['uid']);

        $this->isDraft = isset($d['dft']) && intval($d['dft']) > 0;
        $this->isIndex = isset($d['idx']) && intval($d['idx']) > 0;
        $this->isAutoIndex = isset($d['aidx']) && intval($d['aidx']) > 0;
        $this->publicDate = isset($d['dl']) ? intval($d['dl']) : 0;

        $this->title = strval($d['tl']);
        $this->intro = strval($d['intro']);
        $this->bodyLength = intval($d['bdlen']);
    }

    /**
     * @return BlogCategory|int
     */
    public function Category(){
        if ($this->catid == 0){
            return new BlogPersonalCategory($this->user);
        }
        /** @var BlogApp $app */
        $app = Abricos::GetApp('blog');
        $cats = $app->CategoryList();
        return $cats->Get($this->catid);
    }

    public function URL(){
        $cat = $this->Category();
        return $cat->URL().$this->id."/";
    }

    public function ToAJAX(){
        $ret = new stdClass();
        $ret->id = $this->id;
        $ret->catid = $this->catid;
        $ret->tl = $this->title;

        $ret->userid = $this->userid;
        $ret->intro = $this->intro;
        $ret->bdlen = $this->bodyLength;

        $ret->dft = $this->isDraft ? 1 : 0;
        $ret->idx = $this->isIndex ? 1 : 0;

        /** @var BlogApp $app */
        $app = Abricos::GetApp('blog');

        if ($app->IsAdminRole()){
            $ret->aidx = $this->isAutoIndex ? 1 : 0;
        }

        $ret->dl = $this->publicDate;

        if (!empty($this->commentStatistic)){
            $ret->commentStatistic = $this->commentStatistic->ToJSON();
        }

        if (!empty($this->voting)){
            $ret->voting = $this->voting->ToJSON();
        }

        $ret->tags = array();
        for ($i = 0; $i < count($this->tags); $i++){
            array_push($ret->tags, $this->tags[$i]->ToAJAX());
        }
        return $ret;
    }
}


class old_BlogTopicList {

    public $list;

    /**
     * Всего таких записей в базе
     *
     * @var integer
     */
    public $total;

    /**
     * Из них новых записей
     *
     * @var integer
     */
    public $totalNew;

    public function __construct($list, $total = 0, $totalNew = 0){
        $this->list = $list;
        $this->total = $total;
        $this->totalNew = $totalNew;
    }

    public function ToAJAX(){
        $ret = new stdClass();
        $list = array();
        for ($i = 0; $i < count($this->list); $i++){
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

    /**
     * @param $topicid
     * @return BlogTopicInfo
     */
    public function Get($topicid){
        $count = count($this->list);

        for ($i = 0; $i < $count; $i++){
            if (intval($this->list[$i]->id) === intval($topicid)){
                return $this->list[$i];
            }
        }
        return null;
    }
}

class BlogUser {
    public $id;
    public $userName;
    public $avatar;
    public $firstName;
    public $lastName;

    public function __construct($d){
        $this->id = intval(isset($d['uid']) ? $d['uid'] : $d['id']);
        $this->userName = strval($d['unm']);
        $this->avatar = isset($d['avt']) ? strval($d['avt']) : '';
        $this->firstName = isset($d['fnm']) ? strval($d['fnm']) : '';
        $this->lastName = isset($d['lnm']) ? strval($d['lnm']) : '';
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

    private function Avatar($size){
        $url = empty($this->avatar) ?
            '/modules/uprofile/images/nofoto'.$size.'.gif' :
            '/filemanager/i/'.$this->avatar.'/w_'.$size.'-h_'.$size.'/avatar.gif';
        return '<img src="'.$url.'">';
    }

    public function Avatar24(){
        return $this->Avatar(24);
    }

    public function Avatar90(){
        return $this->Avatar(90);
    }
}

class BlogAuthor extends BlogUser {

    /**
     * @deprecated
     */
    private $reputation;

    /**
     * @deprecated
     */
    private $rating;

    public $topicCount;

    public function __construct($d){
        parent::__construct($d);
        $this->topicCount = $d['tcnt'] * 1;
    }

    public function ToAJAX(){
        $ret = parent::ToAJAX();
        $ret->tcnt = $this->topicCount;
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
        for ($i = 0; $i < count($this->list); $i++){
            array_push($ret->authors, $this->list[$i]->ToAJAX());
        }
        return $ret;
    }
}


class BlogCategory {

    /**
     * Идентификатор категории
     *
     * @var integet
     */
    public $id;

    /**
     * Заголовок
     *
     * @var string
     */
    public $title;

    /**
     * Имя для формирования URL
     *
     * @var string
     */
    public $name;

    /**
     * Описание категории
     *
     * @var string
     */
    public $descript;

    /**
     * Кол-во опубликованных топиков
     *
     * @var integer
     */
    public $topicCount;

    /**
     * Кол-во читателей
     *
     * @var integer
     */
    public $memberCount;

    /**
     * Необходимая репутация для записи в блог
     *
     * @var integer
     */
    public $reputation;

    /**
     * Закрытая категория
     *
     * @var boolean
     */
    public $isPrivate;

    /**
     * Текущий пользователь имеет права Админа на эту категорию
     *
     * @var boolean
     */
    public $isAdminFlag;

    /**
     * Текущий пользователь имеет права Модератора на эту категорию
     *
     * @var boolean
     */
    public $isModerFlag;

    /**
     * Текущий пользователь является членом категории
     *
     * @var boolean
     */
    public $isMemberFlag;

    /**
     * @deprecated
     */
    public $rating;

    /**
     * @deprecated
     */
    public $voteCount;

    /**
     * @deprecated
     */
    public $voteMy;

    /**
     * @var URatingVoting
     */
    public $voting;

    public function __construct($d){
        $this->id = intval($d['id']);
        $this->title = $d['tl'];
        $this->name = $d['nm'];
        $this->descript = $d['dsc'];
        $this->topicCount = intval($d['tcnt']);
        $this->memberCount = intval($d['mcnt']);
        $this->reputation = intval($d['rep']);
        $this->isPrivate = intval($d['prv']) > 0;
        $this->isMemberFlag = intval($d['mbr']) > 0;
    }

    public function ToAJAX(){
        $ret = new stdClass();
        $ret->id = $this->id;
        $ret->tl = $this->title;
        $ret->nm = $this->name;
        $ret->dsc = $this->descript;
        $ret->tcnt = $this->topicCount;
        $ret->mcnt = $this->memberCount;
        $ret->rep = $this->reputation;
        $ret->prv = $this->isPrivate ? 1 : 0;

        $ret->mbr = $this->isMemberFlag ? 1 : 0;

        if (!empty($this->voting)){
            $ret->voting = $this->voting->ToJSON();
        }
        return $ret;
    }

    public function IsTopicWrite(){
        return $this->IsAdmin() || $this->isMemberFlag || $this->isModerFlag;
    }

    public function IsAdmin(){
        $app = Abricos::GetApp('blog');
        if ($app->IsAdminRole()){
            return true;
        }
        return $this->isAdminFlag;
    }
}

class BlogPersonalCategory {

    public $title;

    /**
     * @var UProfileUser
     */
    public $user;

    public function __construct(UProfileUser $user){
        $this->user = $user;
        $i18n = Abricos::GetModule('blog')->I18n();

        $this->title =
            str_replace("{v#unm}", $user->username, $i18n->Translate('catperson'));
    }

    public function URL(){
        return "/blog/author/".$this->user->username."/";
    }
}

class BlogCategoryList {

    private $list;
    private $map;
    private $mapn;

    public function __construct($list){
        $this->list = $list;
        $this->map = array();
        $this->mapn = array();
        for ($i = 0; $i < count($list); $i++){
            $this->map[$list[$i]->id] = $i;
            $this->mapn[$list[$i]->name] = $i;
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

    /**
     * @param string $name
     * @return BlogCategory
     */
    public function GetByName($name){
        $index = $this->mapn[$name];
        return $this->GetByIndex($index);
    }

}
