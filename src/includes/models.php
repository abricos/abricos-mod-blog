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
 */
class Blog extends AbricosModel {
    protected $_structModule = 'blog';
    protected $_structName = 'Blog';

    const TYPE_PUBLIC = 'public';
    const TYPE_PERSONAL = 'personal';
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
        switch ($name){
            case 'user':
            case 'blog':
                return $this->app->AttributeGetter($this, $name);
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
}

/**
 * Interface BlogTopicListOptionsVars
 *
 * @property int $limit
 * @property int $page
 * @property string $type
 * @property string $blogid
 * @property int $userid
 * @property string $tag
 * @property bool $onlyNew
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
 */
class BlogTag extends AbricosModel {
    protected $_structModule = 'blog';
    protected $_structName = 'Tag';
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
