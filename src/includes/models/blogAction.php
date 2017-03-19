<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'db/blogAction.php';

/**
 * Interface BlogSaveArgs
 *
 * @property int $blogid Blog ID for update method
 * @property string $type
 * @property string $title
 * @property string $slug
 * @property string $descript
 * @property int $newTopicUserRep
 */
interface BlogSaveArgs extends Ab_IAttrsData {
}

/**
 * Class BlogSave
 *
 * @property BlogSaveArgs $args
 *
 * @property int $blogid
 *
 * @method BlogApp GetApp()
 */
class BlogSave extends Ab_Model {
    protected $_structModule = 'blog';
    protected $_structName = 'BlogSave';

    const CODE_OK = 1;
    const CODE_EMPTY_TITLE = 2;

    public function SetArgs($data){
        /** @var BlogSaveArgs $args */
        $args = parent::SetArgs($data);

        if ($args->IsEmptyValue('title')){
            $this->SetError(
                AbricosResponse::ERR_BAD_REQUEST,
                BlogSave::CODE_EMPTY_TITLE
            );
        }

        $args->type = BlogApp::BLOG_TYPE_PUBLIC;

        if ($args->IsEmptyValue('slug')){
            $args->slug = translateruen($args->title);
        }

        return $args;
    }

    /**
     * @param BlogApp $app
     * @param $isAppend
     * @param mixed $data
     */
    public function Fill($app, $isAppend, $data){
        if (!$app->IsAdminRole()){
            $this->SetError(Ab_Response::ERR_FORBIDDEN);
            return;
        }

        $args = $this->SetArgs($data);
        if ($this->IsError()){
            return;
        }

        if ($isAppend){
            $this->blogid = BlogQuery_BlogAction::BlogAppend($app->db, $this);

            $app->BlogJoin(array(
                "blogid" => $this->blogid
            ));
        } else {
            BlogQuery_BlogAction::BlogUpdate($app->db, $this);

            $this->blogid = $args->blogid;
        }

        $app->CacheClear();

        $this->AddCode(BlogSave::CODE_OK);
    }
}


/**
 * Interface BlogSubscribeUpdate
 *
 * @property int $blogid
 */
interface BlogSubscribeUpdateArgs extends Ab_IAttrsData {
}

/**
 * Class BlogSubscribeUpdate
 *
 * @property BlogSubscribeUpdateArgs $args
 * @property int $blogid
 *
 * @method BlogApp GetApp()
 */
class BlogSubscribeUpdate extends Ab_Model {
    protected $_structModule = 'blog';
    protected $_structName = 'BlogSubscribeUpdate';

    const CODE_OK = 1;

    /**
     * @param BlogApp $app
     * @param bool $isJoin
     * @param mixed $data
     */
    public function Fill($app, $isJoin, $data){
        if (!$app->IsViewRole()
            || Abricos::$user->id === 0
        ){
            $this->SetError(Ab_Response::ERR_FORBIDDEN);
            return;
        }

        /** @var BlogSubscribeUpdateArgs $args */
        $args = $this->SetArgs($data);

        $blog = $app->Blog($args->blogid);
        if ($blog->IsError()){
            $this->SetError(Ab_Response::ERR_NOT_FOUND);
            return;
        }

        $userRole = $blog->userRole;
        if (empty($userRole->pubKey)){
            $userRole->pubKey = md5(TIMENOW.$blog->id.$userRole->userid);
        }
        $userRole->isMember = $isJoin;

        BlogQuery_BlogAction::SubscribeUpdate($app->db, $userRole);
        BlogQuery_BlogAction::MemberCountUpdate($app->db, $blog->id);

        $this->blogid = $blog->id;
    }
}