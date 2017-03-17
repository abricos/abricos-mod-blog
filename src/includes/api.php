<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogAPI
 *
 * @property BlogApp $app
 */
class BlogAPI extends Ab_API {

    protected $_versions = array(
        'v1' => 'BlogAPIMethodsV1'
    );

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName BlogApp
     * @api {get} /api/blog/ App Structures
     *
     * @apiSuccess {String} version API version
     * @apiSuccess {String[]} methods Available API methods for current user
     * @apiSuccess {Object[]} structures App Structure list
     */
    protected function OnRequestRoot(){
        $ret = $this->ToJSON();
        return $ret;
    }
}

/**
 * Class BlogAPIMethodsV1
 *
 * @property BlogApp $app
 */
class BlogAPIMethodsV1 extends Ab_APIMethods {

    public function GetMethods(){
        return array(
            'config' => 'Config',
            'blogList' => 'BlogList'
        );
    }

    protected function GetStructures(){
        return array(
            'Config',
            'Blog',
            'BlogList',
            'BlogUserRole',
            'BlogUserRoleList'
        );
    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName GETConfig
     * @api {get} /api/blog/v1/config Config
     * @apiPermission Everyone
     *
     * @apiSuccess {Number} topicIndexRating
     * @apiSuccess {Number} blogCreateRating
     * @apiSuccess {Number} [subscribeSendLimit] for Admin
     * @apiSuccessExample {json} Success-Response for Admin:
     *  {
     *    "topicIndexRating": 5,
     *    "blogCreateRating": 5,
     *    "subscribeSendLimit": 25
     *  }
     */
    public function Config(){

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName POSTConfigUpdate
     * @api {post} /api/blog/v1/configUpdate Config Update
     * @apiPermission Admin
     *
     * @apiParam {Number} topicIndexRating
     * @apiParam {Number} blogCreateRating
     * @apiParam {Number} subscribeSendLimit
     *
     * @apiParamExample {json} Request-Example:
     *  {
     *    "topicIndexRating": 5,
     *    "blogCreateRating": 5,
     *    "subscribeSendLimit": 25
     *  }
     */
    public function ConfigUpdate(){

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName GETBlogList
     * @api {get} /api/blog/v1/blogList Blog List
     * @apiPermission Everyone
     */
    public function BlogList(){
        return $this->app->BlogList();
    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName GETBlog
     * @api {get} /api/blog/v1/blog/:blogid Blog
     * @apiPermission Everyone
     *
     * @apiParam {Number} blogid BlogID
     */
    public function Blog($blogid){

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName GETBlogBySlug
     * @api {get} /api/blog/v1/blogBySlug/:slug Blog by Slug
     * @apiPermission Everyone
     *
     * @apiParam {String} slug Slug
     * @apiParamExample {json} Request-Example:
     *  {
     *    "slug": "IoT"
     *  }
     */
    public function BlogBySlug($slug){

    }

    /**
     * @apiDefine BlogSaveParam
     *
     * @apiParam {String} title
     * @apiParam {String} slug
     * @apiParam {String} descript
     * @apiParam {Number} [newTopicUserRep] (for Admin) Reputation by a user writing on this blog
     *
     * @apiParamExample {json} Request-Example:
     *  {
     *    "title": "Internet of Things",
     *    "slug": "IoT",
     *    "descript": "&lt;p&gt;The Internet of things (IoT) is the inter-networking of physical devices&lt;p\&gt;",
     *    "newTopicUserRep": 3
     *  }
     */

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName POSTBlogAppend
     * @api {post} /api/blog/v1/blogAppend Blog Append
     * @apiPermission User
     *
     * @apiUse BlogSaveParam
     */
    public function BlogAppend(){

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName POSTBlogUpdate
     * @api {post} /api/blog/v1/blogUpdate/:blogid Blog Update
     * @apiPermission User
     *
     * @apiUse BlogSaveParam
     * @apiParam {Number} blogid
     */
    public function BlogUpdate($blogid){

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName POSTBlogRemove
     * @api {post} /api/blog/v1/blogRemove Blog Remove
     * @apiPermission User
     *
     * @apiParam {Number} blogid
     * @apiParamExample {json} Request-Example:
     *  {
     *    "blogid": 123
     *  }
     */
    public function BlogRemove(){

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName POSTBlogJoin
     * @api {post} /api/blog/v1/blogJoin Blog Join
     * @apiPermission User
     *
     * @apiParam {Number} blogid
     * @apiParamExample {json} Request-Example:
     *  {
     *    "blogid": 123
     *  }
     */
    public function BlogJoin(){

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName POSTBlogLeave
     * @api {post} /api/blog/v1/blogLeave Blog Leave
     * @apiPermission User
     *
     * @apiParam {Number} blogid
     * @apiParamExample {json} Request-Example:
     *  {
     *    "blogid": 123
     *  }
     */
    public function BlogLeave(){

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName POSTTopicList
     * @api {post} /api/blog/v1/topicList TopicList
     * @apiPermission Everyone
     */
    public function TopicList(){ // POST

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName GETTopic
     * @api {get} /api/blog/v1/topic/:topicid Topic
     * @apiPermission Everyone
     *
     * @apiParam {Number} topicid
     * @apiParamExample {json} Request-Example:
     *  {
     *    "topicid": 123
     *  }
     */
    public function Topic($topicid){

    }


    /**
     * @apiDefine TopicSaveParam
     *
     * @apiParam {Number} blogid
     * @apiParam {String} title
     * @apiParam {String} intro
     * @apiParam {String} body
     * @apiParam {Boolean} isDraft
     * @apiParam {Boolean} isIndex (for Admin)
     *
     * @apiParamExample {json} Request-Example:
     *  {
     *    "blogid": 123,
     *    "title": "My IoT Project",
     *    "slug": "IoT",
     *    "intro": "&lt;p&gt; Intro text &lt;p\&gt;",
     *    "body": "&lt;p&gt; Body text &lt;p\&gt;",
     *    "isDraft": false
     *  }
     */

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName POSTTopicAppend
     * @api {post} /api/blog/v1/topicAppend Topic Append
     * @apiPermission User
     *
     * @apiUse TopicSaveParam
     */
    public function TopicAppend(){

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName POSTTopicUpdate
     * @api {post} /api/blog/v1/topicUpdate Topic Update
     * @apiPermission User
     *
     * @apiParam {Number} topicid
     * @apiUse TopicSaveParam
     */
    public function TopicUpdate(){

    }

    /**
     * @apiGroup Blog
     * @apiVersion 1.0.0
     *
     * @apiName POSTTopicRemove
     * @api {post} /api/blog/v1/topicRemove Topic Remove
     * @apiPermission User
     *
     * @apiParam {Number} topicid
     * @apiParamExample {json} Request-Example:
     *  {
     *    "topicid": 123
     *  }
     */
    public function TopicRemove(){

    }


}