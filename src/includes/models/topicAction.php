<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'db/topicAction.php';

/**
 * Interface BlogTopicSaveVars
 *
 * @property int $topicid (optional) Topic ID for update method
 * @property int $blogid
 * @property string $intro
 * @property string $body
 * @property bool $isDraft
 * @property bool $autoIndex
 */
interface BlogTopicSaveArgs extends Ab_IAttrsData {
}

/**
 * Class BlogTopicSave
 *
 * @property int $topicid
 *
 * @method BlogApp GetApp()
 * @method BlogTopicSaveArgs GetArgs()
 */
class BlogTopicSave extends Ab_Model {
    const CODE_OK = 1;
    const CODE_EMPTY_TITLE = 2;

    protected $_structModule = 'blog';
    protected $_structName = 'TopicSave';

    public function SetArgs($data){
        /** @var BlogTopicSaveArgs $data */
        $args = parent::SetArgs($data);


        return $args;
    }

    protected function TopicAppend(){
        $app = $this->GetApp();
        $args = $this->GetArgs();

    }

    protected function TopicUpdate(){
        $app = $this->GetApp();
        $args = $this->GetArgs();

        $curTopic = $app->Topic($args->topicid);
        if ($curTopic->IsError()){
            $this->SetError(Ab_Response::ERR_NOT_FOUND);
            return;
        }

    }

    /**
     * @param BlogApp $app
     * @param bool $isAppend
     * @param mixed $data
     */
    public function Fill($app, $isAppend, $data){
        if (!$app->IsWriteRole()){
            $this->SetError(Ab_Response::ERR_FORBIDDEN);
            return;
        }
        $this->SetArgs($data);
        if ($this->IsError()){
            return;
        }

        if ($isAppend){
            $this->TopicAppend();
        } else {
            $this->TopicUpdate();
        }

    }
}