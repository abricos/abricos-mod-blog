<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Interface BlogConfigUpdateArgs
 *
 * @property int $subscribeSendLimit
 * @property int $topicIndexRating
 * @property int $blogCreateRating
 */
interface BlogConfigUpdateArgs extends Ab_IAttrsData {
}

/**
 * Class BlogConfigUpdate
 *
 * @property BlogConfigUpdateArgs $vars
 */
class BlogConfigUpdate extends Ab_Model {
    protected $_structModule = 'blog';
    protected $_structName = 'ConfigUpdate';

    const CODE_OK = 1;

    /**
     * @param BlogApp $app
     * @param mixed $data
     * @return void
     */
    public function Fill($app, $data){
        if (!$app->IsAdminRole()){
            $this->SetError(AbricosResponse::ERR_FORBIDDEN);
            return;
        }

        /** @var BlogConfigUpdateArgs $args */
        $args = $this->SetArgs($data);

        $phs = Abricos::GetModule('blog')->GetPhrases();

        $phs->Set('subscribeSendLimit', $args->subscribeSendLimit);
        $phs->Set('topicIndexRating', $args->topicIndexRating);
        $phs->Set('blogCreateRating', $args->blogCreateRating);

        Abricos::$phrases->Save();

        $this->AddCode(BlogConfigUpdate::CODE_OK);
    }
}