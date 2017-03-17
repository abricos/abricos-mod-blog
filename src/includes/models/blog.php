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

    const TYPE_PUBLIC = 'public';
    const TYPE_PERSONAL = 'personal';

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
                if ($this->type === Blog::TYPE_PERSONAL){
                    $val .= 'author/'.$this->user->username."/";
                } else {
                    $val .= $this->slug."/";
                }

                return $this->_data[$name] = $val;
        }
        // return parent::__get($name);
    }

    protected $_isFilled = false;

    public function Fill($app){
        if ($this->_isFilled){
            return;
        }
        $this->_isFilled = true;

        $d = BlogQuery::Blog($app->db, $this->id);
        $this->Update($d);
    }
}

/**
 * Class BlogList
 *
 * @method Blog GetByIndex(int $index)
 */
class BlogList extends Ab_ModelList {
    protected $_structModule = 'blog';
    protected $_structName = 'BlogList';

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

    /**
     * @param BlogApp $app
     */
    public function Fill($app){
        if (!$app->IsViewRole()){
            $this->SetError(Ab_Response::ERR_FORBIDDEN);
            return;
        }

        $rows = BlogQuery_Blog::BlogList($app->db);
        while (($d = $app->db->fetch_array($rows))){
            /** @var Blog $blog */
            $blog = $app->Create('Blog', $d);
            $this->Add($blog);
        }

        $blogids = $this->Ids();

        /** @var BlogUserRoleList $userRoleList */
        $userRoleList = $app->CreateFilled('BlogUserRoleList', $blogids);

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        if (!empty($uratingApp)){
            $votingList = $uratingApp->VotingList('blog', 'blog', $blogids);
        }

        $count = $this->Count();
        for ($i = 0; $i < $count; $i++){
            $blog = $this->GetByIndex($i);

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
        }
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
     * @param int[] $blogids
     */
    public function Fill($app, $blogids){
        $rows = BlogQuery_Blog::BlogUserRoleList($app->db, $blogids);
        while (($d = $app->db->fetch_array($rows))){
            $this->Add($d);
        }
    }
}
