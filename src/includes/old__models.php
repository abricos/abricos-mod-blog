<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


/**
 * Class BlogAuthor
 *
 * @property int $id User ID
 * @property int $topicCount
 */
class BlogAuthor extends AbricosModel {
    protected $_structModule = 'blog';
    protected $_structName = 'Author';
}

/**
 * Interface BlogAuthorListOptionsVars
 *
 * @property int $limit
 * @property int $page
 */
interface BlogAuthorListOptionsVars {
}

/**
 * Class BlogAuthorListOptions
 *
 * @property BlogAuthorListOptionsVars $vars
 * @property int $total
 */
class BlogAuthorListOptions extends AbricosResponse {
    const CODE_OK = 1;

    protected $_structModule = 'blog';
    protected $_structName = 'AuthorListOptions';
}


/**
 * Class BlogAuthorList
 *
 * @property BlogAuthorListOptions $options
 *
 * @method BlogAuthor Get(int $id)
 * @method BlogAuthor GetByIndex(int $index)
 */
class BlogAuthorList extends BlogListWithOptions {
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
