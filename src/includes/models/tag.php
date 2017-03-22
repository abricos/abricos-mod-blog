<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'db/tag.php';

/**
 * Class BlogTag
 *
 * @property string $title
 * @property string $slug
 * @property int $topicCount
 *
 * @property string $url
 */
class BlogTag extends Ab_Model {
    protected $_structModule = 'blog';
    protected $_structName = 'Tag';

    public function old__get($name){
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
 * Interface BlogTagListArgs
 *
 * @property int $limit
 * @property int $page
 */
interface BlogTagListArgs extends Ab_IAttrsData {
}


/**
 * Class BlogTagList
 *
 * @property BlogTagListArgs $args
 *
 * @method BlogTag Get(int $id)
 * @method BlogTag GetByIndex(int $index)
 */
class BlogTagList extends Ab_ModelList {
    protected $_structModule = 'blog';
    protected $_structName = 'TagList';

}


/**
 * Class BlogTagListOptions
 *
 * @property BlogTagListOptionsVars $vars
 * @property int $total
 */
class BlogTagListOptions extends AbricosResponse {
    const CODE_OK = 1;

}
