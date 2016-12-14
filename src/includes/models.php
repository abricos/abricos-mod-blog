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
 */
class Blog extends AbricosModel {
    protected $_structModule = 'blog';
    protected $_structName = 'Blog';
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
 * @property Blog $blog
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
