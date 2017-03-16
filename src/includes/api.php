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
     * @apiName BlogInfo
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
        );
    }

    protected function GetStructures(){
        return array(
            'Config'
        );
    }


}