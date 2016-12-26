<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogRouter
 *
 * @property BlogApp $app
 * @property BlogTopicListOptions $topicListOptions
 * @property string $topicListURL
 */
class BlogRouter {

    const PAGE_BLOG_VIEWER = 'blogViewer';

    const PAGE_TOPIC_LIST = 'topicList';
    const PAGE_TOPIC_VIEWER = 'topicViewer';

    const PAGE_UNSUBSCRIBE = 'unsubscribe';

    const PAGE_TAG_LIST = 'tagList';
    const PAGE_TAG_VIEWER = 'tagViewer';

    const PAGE_AUTHOR_LIST = 'authorList';
    const PAGE_AUTHOR_VIEWER = 'authorViewer';

    public $contentName;
    public $options;

    private $_varsData = array();

    public function __construct(){
        $this->_initRouter();
    }

    public function __get($name){
        if (isset($this->_varsData[$name])){
            return $this->_varsData[$name];
        }
        switch ($name){
            case 'app':
                return Abricos::GetApp('blog');
            case 'topicListOptions':
                return $this->_varsData[$name] = $this->app->TopicListOptionsNormalize($this->options);
            case 'topicListURL':
                $vars = $this->topicListOptions->vars;
                $url = "/blog/";
                if (!empty($vars->username) && $vars->userid > 0){
                    $url .= "author/".$vars->username."/";
                } else {
                    if (!empty($vars->blogSlug)){
                        $url .= $vars->blogSlug."/";
                    }
                    switch ($vars->type){
                        case Blog::TYPE_PUBLIC:
                            $url .= 'pub/';
                            break;
                        case Blog::TYPE_PERSONAL:
                            $url .= 'pers/';
                            break;
                    }

                    if ($vars->onlyNew){
                        $url .= "new/";
                    }
                }

                return $this->_varsData[$name] = $url;
        }
    }

    public function _initRouter(){
        $dir = array("blog");
        $oDir = Abricos::$adress->dir;

        for ($i = 1; $i < 7; $i++){
            $dir[] = isset($oDir[$i]) ? $oDir[$i] : "";
        }

        if (empty($dir[1])){
            $this->contentName = BlogRouter::PAGE_TOPIC_LIST;
            $this->options = array(
                'userid' => intval($dir[2]),
                'key' => $dir[3],
                'blogid' => intval($dir[4]),
            );

        } else if ($dir[1] === '_unsubscribe'){
            $this->contentName = BlogRouter::PAGE_UNSUBSCRIBE;
            $this->options = array(
                'userid' => intval($dir[2]),
                'key' => $dir[3],
                'blogid' => intval($dir[4]),
            );
        } else if (($page = $this->PageConvert($dir[1])) > 1){ //blog/pageN/
            $this->contentName = BlogRouter::PAGE_TOPIC_LIST;
            $this->options = array(
                'page' => $page
            );
        } else if ($dir[1] === 'new'){ //blog/new/...
            $this->contentName = BlogRouter::PAGE_TOPIC_LIST;
            $this->options = array(
                'onlyNew' => true,
                'page' => $this->PageConvert($dir[2])
            );
        } else if ($dir[1] === 'pub' || $dir[1] === 'pers'){ //blog/[pub|pers]/...
            $this->contentName = BlogRouter::PAGE_TOPIC_LIST;

            $this->options = array(
                'type' => $dir[1] === 'pub' ? 'public' : 'personal',
                'onlyNew' => $dir[2] === 'new',
                'page' => $this->PageConvert($dir[3])
            );

            if ($dir[2] !== 'new'){
                $this->options['page'] = $this->PageConvert($dir[2]);
            }
        } else if ($dir[1] === 'tag'){
            $page = $this->PageConvert($dir[2]);
            if (empty($dir[2]) || $page > 1){
                $this->contentName = BlogRouter::PAGE_TAG_LIST;
                $this->options = array(
                    'page' => $page
                );
            } else {
                $this->contentName = BlogRouter::PAGE_TAG_VIEWER;
                $this->options = array(
                    'tag' => urldecode($dir[2])
                );
            }
        } else if ($dir[1] == 'author'){
            $page = $this->PageConvert($dir[2]);

            if (!empty($dir[3]) && intval($dir[3]) > 0){ //blog/author/%username%/%topicid%/
                $this->contentName = BlogRouter::PAGE_TOPIC_VIEWER;
                $this->options = array(
                    'topicid' => intval($dir[3])
                );
            } else if (empty($dir[2]) || $page > 1){ //blog/author/pageN/
                $this->contentName = BlogRouter::PAGE_AUTHOR_LIST;
                $this->options = array(
                    'page' => $page
                );
            } else { //blog/author/%username%/
                $this->contentName = BlogRouter::PAGE_AUTHOR_VIEWER;
                $this->options = array(
                    'username' => $dir[2],
                    'page' => $this->PageConvert($dir[3])
                );
            }
        } else if (!empty($dir[1])){ //blog/%category_name%/

            $topicid = intval($dir[2]);
            if ($topicid > 0){
                $this->contentName = BlogRouter::PAGE_TOPIC_VIEWER;
                $this->options = array(
                    'blogSlug' => $dir[1],
                    'topicid' => $topicid
                );
            } else {
                $this->contentName = BlogRouter::PAGE_BLOG_VIEWER;
                $this->options = array(
                    'blogSlug' => $dir[1],
                    'page' => $this->PageConvert($dir[2])
                );
            }
        }
    }

    private function PageConvert($p){
        if (substr($p, 0, 4) === 'page'){
            $c = strlen($p);
            if ($c <= 4){
                return 1;
            }
            return max(intval(substr($p, 4, $c - 4)), 1);
        }
        return 1;
    }
}
