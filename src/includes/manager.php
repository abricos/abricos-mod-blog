<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogManager
 *
 * @property BlogModule $module
 */
class BlogManager extends Ab_ModuleManager {

    /**
     * @var BlogManager
     */
    public static $instance = null;

    public static $isURating = false;

    public function __construct($module){
        parent::__construct($module);

        BlogManager::$instance = $this;

        $modURating = Abricos::GetModule("urating");
        BlogManager::$isURating = !empty($modURating);
    }

    public function IsAdminRole(){
        return $this->IsRoleEnable(BlogAction::ADMIN);
    }

    public function IsWriteRole(){
        if ($this->IsAdminRole()){
            return true;
        }
        return $this->IsRoleEnable(BlogAction::WRITE);
    }

    public function IsViewRole(){
        if ($this->IsWriteRole()){
            return true;
        }
        return $this->IsRoleEnable(BlogAction::VIEW);
    }

    private $_app = null;

    /**
     * @return BlogApp
     */
    public function GetApp(){
        if (!is_null($this->_app)){
            return $this->_app;
        }
        $this->module->ScriptRequire('includes/app.php');
        return $this->_app = new BlogApp($this);
    }

    public function AJAX($d){
        return $this->GetApp()->AJAX($d);
    }

    public function Bos_MenuData(){
        $i18n = $this->module->I18n();
        return array(
            array(
                "name" => "blog",
                "title" => $i18n->Translate('bosmenu.blog'),
                "role" => BlogAction::VIEW,
                "icon" => "/modules/blog/images/blog-24.png",
                "url" => "blog/wspace/ws"
            )
        );
    }
}

?>