<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogConfig
 */
class BlogConfig {

    /**
     * @var BlogConfig
     */
    public static $instance;

    /**
     * Фильтр по имени домена (если несколько, через запятую)
     *
     * @var string
     */
    public $domainFilter = "";

    /**
     * Количество отправляемых писем за один раз
     *
     * @var integer
     */
    public $subscribeSendLimit = 25;


    /**
     * Рейтинг топика для выхода на главную
     *
     * @var integer
     */
    public $topicIndexRating = 5;


    /**
     * Рейтинг пользователя необходимый для создания категории
     *
     * @var integer
     */
    public $categoryCreateRating = 5;

    public function __construct($cfg){
        BlogConfig::$instance = $this;

        if (empty($cfg)){
            $cfg = array();
        }

        if (isset($cfg['subscribeSendLimit'])){
            $this->subscribeSendLimit = intval($cfg['subscribeSendLimit']);
        }

        if (isset($cfg['topicIndexRating'])){
            $this->topicIndexRating = intval($cfg['topicIndexRating']);
        }

        if (isset($cfg['domainFilter'])){
            $this->domainFilter = trim($cfg['domainFilter']);
        }

        if (isset($cfg['categoryCreateRating'])){
            $this->categoryCreateRating = intval($cfg['categoryCreateRating']);
        }
    }
}


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
     * Автор
     *
     * @var BlogUser
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

        $this->user = new BlogUser($d);
        $this->isDraft = isset($d['dft']) && intval($d['dft']) > 0;
        $this->isIndex = isset($d['idx']) && intval($d['idx']) > 0;
        $this->isAutoIndex = isset($d['aidx']) && intval($d['aidx']) > 0;
        $this->publicDate = isset($d['dl']) ? intval($d['dl']) : 0;

        $this->title = strval($d['tl']);
        $this->intro = strval($d['intro']);
        $this->bodyLength = intval($d['bdlen']);

        /*
        if (!is_null($this->voteMy) || !$this->IsVotingPeriod()){
            $this->rating = isset($d['rtg']) ? intval($d['rtg']) : 0;

            // показать значение, значит запретить голосовать
            if (is_null($this->voteMy)){
                $this->voteMy = 0;
            }
        } else {
            // голосовать еще можно
            $this->rating = null;
        }
        /**/
    }

    private $_commentOwner;

    public function GetCommentOwner(){
        if (!empty($this->_commentOwner)){
            return $this->_commentOwner;
        }
        /** @var CommentApp $commentApp */
        $commentApp = Abricos::GetApp('comment');

        return $this->_commentOwner = $commentApp->InstanceClass('Owner', array(
            "module" => "blog",
            "type" => "topic",
            "ownerid" => $this->id
        ));
    }

    /**
     * Можно ли еще голосовать за топик
     */
    public function IsVotingPeriod(){
        return $this->publicDate > TIMENOW - 60 * 60 * 24 * 31;
    }

    /**
     * @return BlogCategory
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
        $ret->user = $this->user->ToAJAX();
        $ret->intro = $this->intro;
        $ret->bdlen = $this->bodyLength;

        $ret->dft = $this->isDraft ? 1 : 0;
        $ret->idx = $this->isIndex ? 1 : 0;

        if (BlogManager::$instance->IsAdminRole()){
            $ret->aidx = $this->isAutoIndex ? 1 : 0;
        }

        $ret->dl = $this->publicDate;

        $ret->rtg = $this->rating;
        $ret->vcnt = $this->voteCount;
        $ret->vmy = $this->voteMy;

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

/**
 * Запись в блоге
 */
class BlogTopic extends BlogTopicInfo {

    public $body;

    public $metakeys;
    public $metadesc;

    public function __construct($d){
        parent::__construct($d);
        $this->body = strval($d['bd']);
        $this->metakeys = isset($d['mtks']) ? strval($d['mtks']) : '';
        $this->metadesc = isset($d['mtdsc']) ? strval($d['mtdsc']) : '';
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

    public $topicCount;
    public $reputation;
    public $rating;

    public function __construct($d){
        parent::__construct($d);
        $this->topicCount = $d['tcnt'] * 1;
        $this->reputation = $d['rep'] * 1;
        $this->rating = $d['rtg'] * 1;
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
        for ($i = 0; $i < count($this->list); $i++){
            array_push($ret->authors, $this->list[$i]->ToAJAX());
        }
        return $ret;
    }
}


class BlogTopicTag {
    public $id;
    public $title;
    public $name;
    public $topicCount;

    public function __construct($d){
        $d = array_merge(array(
            'id' => 0,
            'tl' => '',
            'nm' => '',
            'cnt' => 0
        ), $d);

        $this->id = intval($d['id']);
        $this->title = strval($d['tl']);
        $this->name = strval($d['nm']);
        $this->topicCount = intval($d['cnt']);
    }

    public function ToAJAX(){
        $ret = new stdClass();
        $ret->id = $this->id;
        $ret->tl = $this->title;
        $ret->nm = $this->name;
        if ($this->topicCount > 0){
            $ret->cnt = $this->topicCount;
        }
        return $ret;
    }

    public function URL(){
        return "/blog/tag/".$this->title."/";
    }
}

function BlogTopicTag_sortByTitle(BlogTopicTag $t1, BlogTopicTag $t2){
    if ($t1->title < $t2->title){
        return -1;
    }
    if ($t1->title > $t2->title){
        return 1;
    }
    return 0;
}


class BlogTopicTagList {

    private $list;

    public function __construct($list){
        $this->list = $list;
    }

    public function Count(){
        return count($this->list);
    }

    public function SortByTitle(){
        usort($this->list, "BlogTopicTag_sortByTitle");
    }

    /**
     * @param integer $index
     * @return BlogTopicTag
     */
    public function GetByIndex($index){
        return $this->list[$index];
    }

    public function ToAJAX(){
        $ret = new stdClass();
        $ret->tags = array();
        for ($i = 0; $i < count($this->list); $i++){
            array_push($ret->tags, $this->list[$i]->ToAJAX());
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
     * Рейтинг категории
     *
     * @var integer
     */
    public $rating;

    /**
     * Количество голосов за рейтинг
     *
     * @var integer
     */
    public $voteCount;

    /**
     * Голос текущего пользователя
     * null - нет голоса, -1 - ПРОТИВ, 1 - ЗА, 0 - Воздержался
     *
     * @var integer
     */
    public $voteMy;

    public function __construct($d){
        $this->id = intval($d['id']);
        $this->title = $d['tl'];
        $this->name = $d['nm'];
        $this->descript = $d['dsc'];
        $this->topicCount = intval($d['tcnt']);
        $this->memberCount = intval($d['mcnt']);
        $this->reputation = intval($d['rep']);
        $this->isPrivate = intval($d['prv']) > 0;
        $this->isAdminFlag = intval($d['adm']) > 0;
        $this->isModerFlag = intval($d['mdr']) > 0;
        $this->isMemberFlag = intval($d['mbr']) > 0;

        $this->rating = intval($d['rtg']);
        $this->voteCount = intval($d['vcnt']);
        $this->voteMy = isset($d['vmy']) ? intval($d['vmy']) : 0;
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

        $ret->adm = $this->isAdminFlag ? 1 : 0;
        $ret->mdr = $this->isModerFlag ? 1 : 0;
        $ret->mbr = $this->isMemberFlag ? 1 : 0;

        $ret->rtg = $this->rating;
        $ret->vcnt = $this->voteCount;
        $ret->vmy = $this->voteMy;
        return $ret;
    }

    public function IsTopicWrite(){
        return $this->IsAdmin() || $this->isMemberFlag || $this->isModerFlag;
    }

    public function IsAdmin(){
        if (BlogManager::$instance->IsAdminRole()){
            return true;
        }
        return $this->isAdminFlag;
    }

    public function URL(){
        return "/blog/".$this->name."/";
    }
}

class BlogPersonalCategory {

    public $title;

    /**
     * @var BlogUser
     */
    public $user;

    public function __construct(BlogUser $user){
        $this->user = $user;
        $i18n = BlogModule::$instance->GetI18n();

        $this->title =
            str_replace("{v#unm}", $user->userName, $i18n['catperson']);
    }

    public function URL(){
        return "/blog/author/".$this->user->userName."/";
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

    public function ToAJAX(){
        $ret = new stdClass();
        $ret->categories = array();
        for ($i = 0; $i < count($this->list); $i++){
            array_push($ret->categories, $this->list[$i]->ToAJAX());
        }
        return $ret;
    }
}

class BlogCommentLive {
    /**
     * Идентификатор комментария
     *
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
        $this->id = $d['id'];
        $this->topicid = $d['tid'];
        $this->body = $d['body'];
        $this->date = $d['dl'];
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
        for ($i = 0; $i < count($this->list); $i++){
            array_push($ret->comments, $this->list[$i]->ToAJAX());
        }
        return $ret;
    }

    public function Count(){
        return count($this->list);
    }

    /**
     * @param integer $index
     * @return BlogCommentLive
     */
    public function GetByIndex($index){
        return $this->list[$index];
    }
}
