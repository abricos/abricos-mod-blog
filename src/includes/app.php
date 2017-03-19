<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogApp
 *
 * @property BlogModule $module
 * @property BlogManager $manager
 * @property BlogRouter $router
 */
class BlogApp extends Ab_App {

    const BLOG_TYPE_PUBLIC = 'public';
    const BLOG_TYPE_PERSONAL = 'personal';

    protected $_aliases = array(
        "config" => array(
            'Config' => 'BlogConfig',
        ),
        "configAction" => array(
            'ConfigUpdate' => 'BlogConfigUpdate',
        ),
        "blog" => array(
            'Blog' => 'Blog',
            'BlogList' => 'BlogList',
            'BlogUserRole' => 'BlogUserRole',
            'BlogUserRoleList' => 'BlogUserRoleList',
        ),
        "blogAction" => array(
            'BlogSave' => 'BlogSave',
            'BlogRemove' => 'BlogRemove',
            'BlogSubscribeUpdate' => 'BlogSubscribeUpdate'
        ),
    );

    public function __get($name){
        if ($name === 'router'){
            return $this->manager->module->router;
        }
        return parent::__get($name);
    }

    public function IsAdminRole(){
        return $this->manager->IsAdminRole();
    }

    public function IsWriteRole(){
        return $this->manager->IsWriteRole();
    }

    public function IsViewRole(){
        return $this->manager->IsViewRole();
    }

    /*********************************************************/
    /*                         Config                        */
    /*********************************************************/

    /**
     * @return BlogConfig
     */
    public function Config(){
        if ($this->CacheExists('Config')){
            return $this->Cache('Config');
        }

        /** @var BlogConfig $config */
        $config = $this->CreateFilled('Config');

        $this->SetCache('Config', $config);

        return $config;
    }

    /**
     * @param mixed $data
     * @return BlogConfigUpdate
     */
    public function ConfigUpdate($data){
        /** @var BlogConfigUpdate $update */
        $update = $this->CreateFilled('ConfigUpdate', $data);

        $this->CacheClear();
        return $update;
    }

    /*********************************************************/
    /*                          Blog                         */
    /*********************************************************/

    /**
     * @return BlogList
     */
    public function BlogList(){
        if ($this->CacheExists('BlogList')){
            return $this->Cache('BlogList');
        }

        /** @var BlogList $list */
        $list = $this->CreateFilled('BlogList');

        $this->SetCache('BlogList', $list);

        return $list;
    }

    /**
     * @param int $blogid
     * @return Blog
     */
    public function Blog($blogid){
        $blogList = $this->BlogList();
        if ($blogList->IsError()){
            return $blogList->GetNotFound();
        }
        return $blogList->Get($blogid);
    }

    /**
     * @param string $slug
     * @return Blog
     */
    public function BlogBySlug($slug){
        $blogList = $this->BlogList();
        if ($blogList->IsError()){
            return $blogList->GetNotFound();
        }
        return $blogList->GetBySlug($slug);
    }

    /**
     * @param mixed $data
     * @return BlogSave
     */
    public function BlogAppend($data){
        $ret = $this->CreateFilled('BlogSave', true, $data);
        $this->CacheClear();
        return $ret;
    }

    /**
     * @param mixed $data
     * @return BlogSave
     */
    public function BlogUpdate($data){
        $ret = $this->CreateFilled('BlogSave', true, $data);
        $this->CacheClear();
        return $ret;
    }

    /**
     * @param mixed $data
     * @return BlogRemove
     */
    public function BlogRemove($data){
        $ret = $this->CreateFilled('BlogRemove', $data);
        $this->CacheClear();
        return $ret;
    }


    /*********************************************************/
    /*                      Blog Subscribe                   */
    /*********************************************************/

    /**
     * @param mixed $data
     * @return BlogSubscribeUpdate
     */
    public function BlogJoin($data){
        $ret = $this->CreateFilled('BlogSubscribeUpdate', true, $data);
        $this->CacheClear();
        return $ret;
    }

    /**
     * @param mixed $data
     * @return BlogSubscribeUpdate
     */
    public function BlogLeave($data){
        $ret = $this->CreateFilled('BlogSubscribeUpdate', false, $data);
        $this->CacheClear();
        return $ret;
    }

}
