<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'old_models.php';

/**
 * Class Blog
 *
 * @property int $userid
 * @property string $type
 * @property string $title
 * @property string $slug
 * @property string $descript
 * @property int $newTopicUserRep
 * @property int $topicCount
 * @property int $memberCount
 * @property BlogUserRole $userRole
 * @property URatingVoting $voting
 * @property int $dateline
 * @property int $upddate
 * @property bool $isEasyData
 *
 * @property UProfileUser $user
 * @property string $url
 */
class Blog extends AbricosModel {
    protected $_structModule = 'blog';
    protected $_structName = 'Blog';

    const TYPE_PUBLIC = 'public';
    const TYPE_PERSONAL = 'personal';

    public function __get($name){
        if (isset($this->_data[$name])){
            return $this->_data[$name];
        }
        switch ($name){
            case 'user':
                /** @var UProfileApp $uprofileApp */
                $uprofileApp = Abricos::GetApp('uprofile');
                return $this->_data[$name]
                    = $uprofileApp->User($this->userid);
            case 'url':
                $val = '/blog/';
                if ($this->type === Blog::TYPE_PERSONAL){
                    $val .= 'author/'.$this->user->username."/";
                } else {
                    $val .= $this->slug."/";
                }

                return $this->_data[$name] = $val;
        }
        return parent::__get($name);
    }
}

/**
 * Class BlogList
 *
 * @method Blog Get(int $id)
 * @method Blog GetByIndex(int $index)
 */
class BlogList extends AbricosModelList {
}

/**
 * Class BlogConfig
 *
 * @property int $subscribeSendLimit Количество отправляемых писем за один раз
 * @property int $topicIndexRating Рейтинг топика для выхода на главную
 * @property int $categoryCreateRating Рейтинг пользователя необходимый для создания категории
 */
class BlogConfig extends AbricosModel {
    protected $_structModule = 'blog';
    protected $_structName = 'Config';
}

/**
 * Interface BlogSaveVars
 *
 * @property int $blogid
 * @property string $type
 * @property string $title
 * @property string $slug
 * @property string $descript
 * @property int $newTopicUserRep
 */
interface BlogSaveVars {
}

/**
 * Class BlogSave
 *
 * @property BlogSaveVars $vars
 * @property int $blogid
 */
class BlogSave extends AbricosResponse {
    const CODE_OK = 1;
    const CODE_EMPTY_TITLE = 2;

    protected $_structModule = 'blog';
    protected $_structName = 'BlogSave';
}

/**
 * Class BlogUserRole
 *
 * @property int $blogid
 * @property int $userid
 * @property bool $isMember
 * @property bool $deliveryOff
 * @property string $pubKey
 */
class BlogUserRole extends AbricosModel {
    protected $_structModule = 'blog';
    protected $_structName = 'BlogUserRole';
}

/**
 * Class BlogUserRoleList
 *
 * @method BlogUserRole Get(int $id)
 * @method BlogUserRole GetByIndex(int $index)
 */
class BlogUserRoleList extends AbricosModelList {

    private $_mapByBlogId = array();

    /**
     * @param BlogUserRole $item
     */
    public function Add($item){
        parent::Add($item);

        $this->_mapByBlogId[$item->blogid] = $item;
    }

    /**
     * @param int $blogid
     * @return BlogUserRole|null
     */
    public function GetByBlogId($blogid){
        if (!isset($this->_mapByBlogId[$blogid])){
            return null;
        }
        return $this->_mapByBlogId[$blogid];
    }
}

/**
 * Class BlogTopic
 *
 * @property BlogApp $app
 *
 * @property int $blogid
 * @property int $userid
 * @property string $title
 * @property string $intro
 * @property string $body
 * @property string $metaDesc
 * @property string $metaKeys
 * @property bool $isDraft
 * @property bool $isIndex
 * @property bool $autoIndex
 * @property int $pubdate
 * @property int $views
 * @property int $deliveryUserId
 * @property bool $deliveryCompleted
 * @property URatingVoting $voting
 * @property CommentStatistic $commentStatistic
 * @property BlogTagList $tagList
 * @property int $dateline
 * @property int $upddate
 *
 * @property UProfileUser $user
 * @property Blog $blog
 * @property string $url
 */
class BlogTopic extends AbricosModel {
    protected $_structModule = 'blog';
    protected $_structName = 'Topic';

    public static function GetCommentOwner($topicid){
        /** @var CommentApp $commentApp */
        $commentApp = Abricos::GetApp('comment');

        return $commentApp->InstanceClass('Owner', array(
            "module" => "blog",
            "type" => "topic",
            "ownerid" => $topicid
        ));
    }

    public function __get($name){
        if (isset($this->_data[$name])){
            return $this->_data[$name];
        }

        switch ($name){
            case 'user':
                /** @var UProfileApp $uprofileApp */
                $uprofileApp = Abricos::GetApp('uprofile');
                return $this->_data[$name]
                    = $uprofileApp->User($this->userid);
            case 'blog':
                return $this->_data[$name]
                    = $this->app->BlogList()->Get($this->blogid);
            case 'url':
                return $this->_data['url']
                    = $this->blog->url.$this->id.'/';
        }
        return parent::__get($name);
    }

}

/**
 * Class BlogTopicList
 *
 * @method BlogTopic Get(int $id)
 * @method BlogTopic GetByIndex(int $index)
 */
class BlogTopicList extends AbricosModelList {

    public $total = 0;

    public $totalNew = 0;

    /**
     * @param BlogTopic $item
     */
    public function Add($item){
        /** @var UProfileApp $uprofileApp */
        $uprofileApp = Abricos::GetApp('uprofile');
        $uprofileApp->UserAddToPreload($item->userid);

        return parent::Add($item);
    }

    public function ToJSON(){
        $ret = parent::ToJSON();

        $ret->total = $this->total;
        $ret->totalNew = $this->totalNew;

        return $ret;
    }
}

/**
 * Interface BlogTopicListOptionsVars
 *
 * @property int $limit
 * @property int $page
 * @property string $type
 * @property int $blogid
 * @property string $blogSlug
 * @property int $userid
 * @property string $username
 * @property string $tag
 * @property bool $onlyNew
 * @property string $ids
 * @property bool $idsUse
 * @property bool $idsSort
 */
interface BlogTopicListOptionsVars {
}

/**
 * Class BlogTopicListOptions
 *
 * @property BlogTopicListOptionsVars $vars
 * @property int $total
 */
class BlogTopicListOptions extends AbricosResponse {
    const CODE_OK = 1;

    protected $_structModule = 'blog';
    protected $_structName = 'TopicListOptions';

    /**
     * @var BlogTopicList
     */
    public $topicList;
}


/**
 * Interface BlogTopicSaveVars
 *
 * @property int $topicid
 * @property int $blogid
 * @property string $intro
 * @property string $body
 * @property bool $isDraft
 * @property bool $autoIndex
 */
interface BlogTopicSaveVars {
}

/**
 * Class BlogTopicSave
 *
 * @property BlogTopicSaveVars $vars
 * @property int $blogid
 */
class BlogTopicSave extends AbricosResponse {
    const CODE_OK = 1;
    const CODE_EMPTY_TITLE = 2;

    protected $_structModule = 'blog';
    protected $_structName = 'TopicSave';
}

/**
 * Class BlogTag
 *
 * @property string $title
 * @property string $slug
 * @property int $topicCount
 *
 * @property string $url
 */
class BlogTag extends AbricosModel {
    protected $_structModule = 'blog';
    protected $_structName = 'Tag';

    public function __get($name){
        if (isset($this->_data[$name])){
            return $this->_data[$name];
        }
        switch ($name){
            case 'url':
                return $this->_data[$name]
                    = '/blog/tag/'.urlencode($this->title).'/';
        }
        return parent::__get($name);
    }

}

/**
 * Class BlogTagList
 *
 * @method BlogTag Get(int $id)
 * @method BlogTag GetByIndex(int $index)
 */
class BlogTagList extends AbricosModelList {
}

/**
 * Interface BlogTagListOptionsVars
 *
 * @property int $limit
 * @property int $page
 */
interface BlogTagListOptionsVars {
}

/**
 * Class BlogTagListOptions
 *
 * @property BlogTagListOptionsVars $vars
 * @property int $total
 */
class BlogTagListOptions extends AbricosResponse {
    const CODE_OK = 1;

    protected $_structModule = 'blog';
    protected $_structName = 'TagListOptions';
}


/**
 * Class BlogTagInTopic
 *
 * @property int $topicid
 * @property int $tagid
 */
class BlogTagInTopic extends AbricosModel {
    protected $_structModule = 'blog';
    protected $_structName = 'TagInTopic';
}

/**
 * Class BlogTagInTopicList
 *
 * @method BlogTagInTopic Get(int $id)
 * @method BlogTagInTopic GetByIndex(int $index)
 */
class BlogTagInTopicList extends AbricosModelList {
}
