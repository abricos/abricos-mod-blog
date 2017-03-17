<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


/**
 * Class BlogConfig
 *
 * @property int $subscribeSendLimit Number of emails sent at a time
 * @property int $topicIndexRating Topicality rating for getting home
 * @property int $blogCreateRating User rating required to create a blog
 */
class BlogConfig extends Ab_Model {
    protected $_structModule = 'blog';
    protected $_structName = 'Config';

    /**
     * @param BlogApp $app
     */
    public function Fill($app){
        if (!$app->IsViewRole()){
            $this->SetError(Ab_Response::ERR_FORBIDDEN);
            return;
        }

        $d = array();
        $phrases = $app->module->GetPhrases();
        for ($i = 0; $i < $phrases->Count(); $i++){
            $ph = $phrases->GetByIndex($i);
            $d[$ph->id] = $ph->value;
        }

        $d['subscribeSendLimit'] = isset($d['subscribeSendLimit'])
            ? $d['subscribeSendLimit'] : 25;

        $d['topicIndexRating'] = isset($d['topicIndexRating'])
            ? $d['topicIndexRating'] : 5;

        $d['blogCreateRating'] = isset($d['blogCreateRating'])
            ? $d['blogCreateRating'] : 5;

        $this->Update($d);
    }
}
