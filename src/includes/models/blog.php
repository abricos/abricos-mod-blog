<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'db/blog.php';

/**
 * Class Blog
 *
 * @property int $id
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
 *
 * @property UProfileUser $user
 * @property string $url
 */
class Blog extends Ab_Model {
    protected $_structModule = 'blog';
    protected $_structName = 'Blog';

    public function old__get($name){
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
                if ($this->type === BlogApp::BLOG_TYPE_PERSONAL){
                    $val .= 'author/'.$this->user->username."/";
                } else {
                    $val .= $this->slug."/";
                }

                return $this->_data[$name] = $val;
        }
        // return parent::__get($name);
    }
}


/**
 * Interface BlogListArgs
 *
 * @property int[] $ids
 */
interface BlogListArgs extends Ab_IAttrsData {
}

/**
 * Class BlogList
 *
 * @method Blog GetByIndex(int $index)
 */
class BlogList extends Ab_ModelList {
    protected $_structModule = 'blog';
    protected $_structName = 'BlogList';

    /**
     * @param BlogApp $app
     * @param mixed $data
     */
    public function Fill($app, $data){
        if (!$app->IsViewRole()){
            $this->SetError(Ab_Response::ERR_FORBIDDEN);
            return;
        }

        /** @var BlogListArgs $args */
        $args = $this->SetArgs($data);

        $blogIds = array();
        $ids = $args->ids;
        $count = min(count($ids), 50);
        for ($i = 0; $i < $count; $i++){
            $id = intval($ids[$i]);

            $blog = $app->Cache('Blog', $id);
            if (!empty($blog)){
                $this->Add($blog);
            } else {
                $blogIds[] = $id;
            }
        }

        if (count($blogIds) === 0){
            return;
        }

        /** @var BlogUserRoleList $userRoleList */
        $userRoleList = $app->CreateFilled('BlogUserRoleList', $blogIds);

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        if (!empty($uratingApp)){
            $votingList = $uratingApp->VotingList('blog', 'blog', $blogIds);
        }

        $rows = BlogQuery_Blog::ListByIds($app->db, $blogIds);
        while (($d = $app->db->fetch_array($rows))){
            /** @var Blog $blog */
            $blog = $app->Create('Blog', $d);

            $userRole = $userRoleList->GetByBlogId($blog->id);
            if (empty($userRole)){
                $userRole = $app->Create('BlogUserRole', array(
                    'blogid' => $blog->id,
                    'userid' => Abricos::$user->id
                ));
            }
            $blog->userRole = $userRole;

            if (!empty($votingList)){
                $blog->voting = $votingList->GetByOwnerId($blog->id);
            }

            $app->SetCache('Blog', $blog->id, $blog);

            $this->Add($blog);
        }
    }

    private $_listBySlug = array();

    /**
     * @param $slug
     * @return Blog
     */
    public function GetBySlug($slug){
        $slug = trim($slug);
        return isset($this->_listBySlug[$slug]) ?
            $this->_listBySlug[$slug] : $this->GetNotFound();
    }

    /**
     * @param Blog $blog
     * @return mixed
     */
    public function Add($blog){
        $this->_listBySlug[$blog->slug] = $blog;
        return parent::Add($blog);
    }

    public function GetNotFound(){
        $blog = $this->GetApp()->Create('Blog');
        $blog->SetError(Ab_Response::ERR_NOT_FOUND);
        return $blog;
    }

    /**
     * @param int $blogid
     * @return Blog
     */
    public function Get($blogid){
        $blogid = intval($blogid);
        $blog = parent::Get($blogid);

        if (empty($blog)){
            return $this->GetNotFound();
        }
        return $blog;
    }

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
class BlogUserRole extends Ab_Model {
    protected $_structModule = 'blog';
    protected $_structName = 'BlogUserRole';
}

/**
 * Class BlogUserRoleList
 *
 * @method BlogUserRole Get(int $id)
 * @method BlogUserRole GetByIndex(int $index)
 */
class BlogUserRoleList extends Ab_ModelList {

    protected $_structModule = 'blog';
    protected $_structName = 'BlogUserRoleList';

    private $_mapByBlogId = array();

    /**
     * @param BlogUserRole $item
     */
    public function Add($item){
        $item = parent::Add($item);

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

    /**
     * @param BlogApp $app
     * @param int[] $blogIds
     */
    public function Fill($app, $blogIds){
        $rows = BlogQuery_Blog::BlogUserRoleList($app->db, $blogIds);
        while (($d = $app->db->fetch_array($rows))){
            $this->Add($d);
        }
    }
}
