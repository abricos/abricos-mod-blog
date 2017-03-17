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
    protected $_aliases = array(
        "config" => array(
            'Config' => 'BlogConfig',
        ),
        "blog" => array(
            'Blog' => 'Blog',
            'BlogList' => 'BlogList',
            'BlogUserRole' => 'BlogUserRole',
            'BlogUserRoleList' => 'BlogUserRoleList',
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

    public function Blog($blogid){
        $blogList = $this->BlogList();
        if ($blogList->IsError()){
            return $blogList;
        }

        $blog = $blogList->Get($blogid);
        if ($blog->IsError()){
            return $blog;
        }
        $blog->Fill($this);

        return $blog;
    }

    public function BlogList(){
        if ($this->CacheExists('BlogList')){
            return $this->Cache('BlogList');
        }

        /** @var BlogList $list */
        $list = $this->CreateFilled('BlogList');

        $this->SetCache('BlogList', $list);

        return $list;
    }

}
