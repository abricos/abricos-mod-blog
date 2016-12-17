<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

Abricos::GetModule('comment');

/**
 * Модуль "Блог"
 *
 * @property BlogRouter $router
 *
 * @method BlogManager GetManager()
 */
class BlogModule extends Ab_Module {

    /**
     * @deprecated
     */
    public static $instance = null;

    public function __construct(){
        $this->version = "0.6.0";

        $this->name = "blog";
        $this->takelink = "blog";

        $this->permission = new BlogPermission($this);

        BlogModule::$instance = $this;
    }

    private $_router;

    public function __get($name){
        if ($name === 'router'){
            if (!empty($this->_router)){
                return $this->_router;
            }

            $this->ScriptRequireOnce('includes/router.php');
            return $this->_router = new BlogRouter();
        }
        return parent::__get($name);
    }

    public function GetContentName(){
        return $this->router->contentName;
    }

    public function GetLink(){
        return Ab_URI::Site()."/".$this->takelink."/";
    }

    public function RssMetaLink(){
        return Ab_URI::Site()."/rss/blog/";
    }

    /**
     * This module added menu item in BOS Panel
     *
     * @return bool
     */
    public function Bos_IsMenu(){
        return true;
    }

    public function URating_IsVoting(){
        return true;
    }

    public function old_ParserAddress(){
        $origDir = Abricos::$adress->dir;
        $level = Abricos::$adress->level;

        $dir = array("blog");

        $dir[1] = isset($origDir[1]) ? $origDir[1] : "";
        $dir[2] = isset($origDir[2]) ? $origDir[2] : "";
        $dir[3] = isset($origDir[3]) ? $origDir[3] : "";
        $dir[4] = isset($origDir[4]) ? $origDir[4] : "";

        $i18n = $this->I18n();

        if ($level == 1){ //blog/

            $pa->type = 'topiclist';
            $pa->topicListFilter = "index";
            $pa->pageTitle = $i18n->Translate('pagetitle.index');

        } else if ($dir[1] == '_unsubscribe'){

            $pa->type = 'unsubscribe';
            $pa->usbUserId = intval($dir[2]);
            $pa->usbKey = $dir[3];
            $pa->usbCatId = $dir[4];

        } else if (($page = $this->PageConvert($dir[1])) > 0){ //blog/pageN/

            $pa->type = 'topiclist';
            $pa->topicListFilter = "index";
            $pa->page = $page;
            $pa->pageTitle = str_replace("{v#page}", $page, $i18n->Translate('pagetitle.indexpage'));

        } else if ($dir[1] == 'new'){ //blog/new/...

            $pa->type = 'topiclist';
            $pa->topicListFilter = "index/new";
            $pa->pageTitle = $i18n->Translate('pagetitle.indexnew');

            if (($page = $this->PageConvert($dir[2])) > 0){ //blog/new/pageN/
                $pa->page = $page;
                $pa->pageTitle = str_replace("{v#page}", $page, $i18n->Translate('pagetitle.indexnewpage'));
            }

        } else if ($dir[1] == 'pub' || $dir[1] == 'pers'){ //blog/[pub|pers]/...

            $pa->type = 'topiclist';
            $pa->topicListFilter = $dir[1];
            $pa->pageTitle = $i18n->Translate('pagetitle'.($dir[1] == 'pub' ? 'pub' : 'pers'));

            if ($dir[2] == 'new'){ //blog/[pub|pers]/new/
                $pa->topicListFilter = $dir[1]."/new";

                $pa->pageTitle = $i18n->Translate('pagetitle'.($dir[1] == 'pub' ? 'pubnew' : 'persnew'));

                if (($page = $this->PageConvert($dir[3])) > 0){ //blog/[pub|pers]/new/pageN/
                    $pa->page = $page;
                    $pa->pageTitle = str_replace("{v#page}", $page,
                        $i18n->Translate('pagetitle'.($dir[1] == 'pub' ? 'pubpagepage' : 'perspagepage'))
                    );
                }
            } else if (($page = $this->PageConvert($dir[2])) > 0){ //blog/[pub|pers]/pageN/
                $pa->page = $page;

                $pa->pageTitle = str_replace("{v#page}", $page,
                    $i18n->Translate('pagetitle'.($dir[1] == 'pub' ? 'pubpage' : 'perspage'))
                );
            }

        } else if ($dir[1] == 'tag'){ //blog/[pub|pers]/...

            if ($level == 2){
                $pa->type = 'taglist';
            } else {
                $pa->type = 'tagview';
                $pa->topicListFilter = $dir[1]."/".urldecode($dir[2]);
                $pa->pageTitle = str_replace("{v#name}", urldecode($dir[2]),
                    $i18n->Translate('pagetitle.tag')
                );
            }

        } else if ($dir[1] == 'author'){
            $page = $this->PageConvert($dir[3]);

            $pa->pageTitle = $i18n->Translate('pagetitle.authors');

            if ($level == 2){//blog/author/
                // список авторов
                $pa->type = 'authorlist';
            } else if ($level == 4 && $page == 0){//blog/author/%username%/%topicid%/

                $topicid = intval($dir[3]);
                $topic = $app->Topic($topicid);

                if (empty($topic)){
                    $pa->err404 = true;
                } else {
                    $pa->type = 'topicview';
                    $pa->topicListFilter = '';
                    $pa->topic = $topic;
                }

            } else {//blog/author/%username%/
                $username = urldecode($dir[2]);

                $pa->author = $app->AuthorByUserName($username);

                if (empty($pa->author)){
                    $pa->err404 = true;
                } else {
                    $pa->type = 'authorview';
                    $pa->topicListFilter = "author/".$pa->author->id;
                    $pa->pageTitle = str_replace("{v#name}", $username,
                        $i18n->Translate('pagetitle.author')
                    );
                }
            }

        } else if (!empty($dir[1])){ //blog/%category_name%/
            $cats = $app->CategoryList();
            $pa->cat = $cats->GetByName($dir[1]);
            if (!empty($pa->cat)){
                $pa->type = 'catview';
                $pa->topicListFilter = "cat/".$pa->cat->id;

                $pa->pageTitle = str_replace("{v#name}", $pa->cat->title,
                    $i18n->Translate('pagetitle.cat')
                );

                if ($dir[2] == 'new'){ //blog/%category_name%/new/
                    $pa->topicListFilter .= "/new";

                    if (($page = $this->PageConvert($dir[3])) > 0){ //blog/%category_name%/new/pageN/
                        $pa->page = $page;
                    }
                } else if (($page = $this->PageConvert($dir[2])) > 0){ //blog/%category_name%/pageN/
                    $pa->page = $page;
                    $pa->pageTitle = str_replace("{v#name}", $pa->cat->title,
                        $i18n->Translate('pagetitle.catpage')
                    );
                    $pa->pageTitle = str_replace("{v#page}", $page,
                        $pa->pageTitle
                    );
                } else if ($level > 2){ //blog/%category_name%/%topicid%/

                    $topicid = intval($dir[2]);
                    $topic = $app->Topic($topicid);

                    if (empty($topic)){
                        $pa->err404 = true;
                    } else {
                        $pa->type = 'topicview';
                        $pa->topicListFilter = '';
                        $pa->topic = $topic;

                        // указать контентid для комментарий
                        // Brick::$contentId = $topic->contentid;
                    }
                }

            } else {
                $pa->err404 = true;
            }
        }

        if (!empty($pa->topicListFilter)){
            $pa->topicList = $app->TopicList(array(
                "limit" => BlogModule::TOPIC_PAGE_LIMIT,
                "filter" => $pa->topicListFilter,
                "page" => $pa->page
            ));
        }

        if (empty($pa->type) && !$pa->err404){
            $pa->type = 'topiclist';
        }

        $this->_cachepa = $pa;

        return $pa;
    }
}


class BlogAction {
    const VIEW = 10;
    const WRITE = 20;
    const ADMIN = 50;
}

class BlogPermission extends Ab_UserPermission {

    public function __construct(BlogModule $module){
        $defRoles = array(
            new Ab_UserRole(BlogAction::VIEW, Ab_UserGroup::GUEST),
            new Ab_UserRole(BlogAction::VIEW, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(BlogAction::VIEW, Ab_UserGroup::ADMIN),

            new Ab_UserRole(BlogAction::WRITE, Ab_UserGroup::ADMIN),

            new Ab_UserRole(BlogAction::ADMIN, Ab_UserGroup::ADMIN),
        );

        parent::__construct($module, $defRoles);
    }

    public function GetRoles(){
        return array(
            BlogAction::VIEW => $this->CheckAction(BlogAction::VIEW),
            BlogAction::WRITE => $this->CheckAction(BlogAction::WRITE),
            BlogAction::ADMIN => $this->CheckAction(BlogAction::ADMIN)
        );
    }
}

Abricos::ModuleRegister(new BlogModule());
