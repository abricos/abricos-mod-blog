<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2017 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'db/topic.php';

/**
 * Class BlogTopic
 *
 * @property BlogApp $app
 *
 * @property int $id Topic ID
 * @property int $blogid Blog ID
 * @property int $userid Author ID
 * @property string $title
 * @property string $intro
 * @property bool $isBody
 * @property string $body
 * @property string $metaDesc
 * @property string $metaKeys
 * @property bool $isDraft
 * @property bool $isIndex
 * @property bool $autoIndex
 * @property int $pubdate
 * @property int $views
 * @property int $deliveryUserId
 * @property bool $deliveryCompleted
 * @property URatingVoting $voting
 * @property CommentStatistic $commentStatistic
 * @property BlogTagList $tagList
 * @property int $dateline
 * @property int $upddate
 *
 * @property UProfileUser $user
 * @property Blog $blog
 * @property string $url
 */
class BlogTopic extends Ab_Model {
    protected $_structModule = 'blog';
    protected $_structName = 'Topic';

    public static function GetCommentOwner($topicid){
        /** @var CommentApp $commentApp */
        $commentApp = Abricos::GetApp('comment');

        return $commentApp->InstanceClass('Owner', array(
            "module" => "blog",
            "type" => "topic",
            "ownerid" => $topicid
        ));
    }

    public function old__get($name){
        if (isset($this->_data[$name])){
            return $this->_data[$name];
        }

        switch ($name){
            case 'user':
                /** @var UProfileApp $uprofileApp */
                $uprofileApp = Abricos::GetApp('uprofile');
                return $this->_data[$name]
                    = $uprofileApp->User($this->userid);
            case 'blog':
                return $this->_data[$name]
                    = $this->app->BlogList()->Get($this->blogid);
            case 'url':
                return $this->_data['url']
                    = $this->blog->url.$this->id.'/';
        }
        return parent::__get($name);
    }

    /**
     * @param BlogApp $app
     * @param int $topicid
     */
    public function Fill($app, $topicid){
        if (!$app->IsViewRole()){
            $this->SetError(Ab_Response::ERR_FORBIDDEN);
            return;
        }

        $topicid = intval($topicid);
        $d = BlogQuery_Topic::Topic($app->db, $topicid);
        if (empty($d)){
            $this->SetError(Ab_Response::ERR_NOT_FOUND);
            return;
        }

        $this->Update($d);

        /** @var CommentApp $commentApp */
        $commentApp = Abricos::GetApp('comment');

        $this->commentStatistic = $commentApp->Statistic(BlogTopic::GetCommentOwner($topicid));

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        if (!empty($uratingApp)){
            $this->voting = $uratingApp->Voting('blog', 'topic', $topicid);
            $this->voting->ownerDate = intval($this->pubdate);
            $this->voting->userid = intval($this->userid);
        }

        /** @var BlogTagList $tagList */
        $tagList = $this->CreateFiled('TagList');
        $rows = BlogQuery::TagInTopicList($app->db, $this->id);
        while (($d = $app->db->fetch_array($rows))){
            $tagList->Add($this->InstanceClass('Tag', $d));
        }
        $this->tagList = $tagList;
    }
}

/**
 * Interface BlogTopicListArgs
 *
 * @property int $limit
 * @property int $page
 * @property string $type
 * @property int $blogid
 * @property string $blogSlug
 * @property int $userid
 * @property string $username
 * @property string $tag
 * @property bool $onlyNew
 * @property string $ids
 * @property bool $idsUse
 * @property bool $idsSort
 */
interface BlogTopicListArgs extends Ab_IAttrsData {
}

/**
 * Class BlogTopicList
 *
 * @property int $total
 * @property int $totalNew
 *
 * @method BlogTopicListArgs GetArgs()
 * @method BlogTopic Get(int $id)
 * @method BlogTopic GetByIndex(int $i)
 */
class BlogTopicList extends Ab_ModelList {

    /**
     * @param mixed $data
     * @return BlogTopicListArgs
     */
    public function SetArgs($data){
        /** @var BlogTopicListArgs $args */
        $args = parent::SetArgs($data);

        if (!$args->IsEmptyValue('username')){
            /** @var UProfileApp $uprofileApp */
            $uprofileApp = Abricos::GetApp('uprofile');
            $user = $uprofileApp->Profile($args->username, true);
            if (AbricosResponse::IsError($user)){
                $args->username = '';
            } else {
                $args->userid = $user->id;
            }
        }

        return $args;
    }

    /**
     * @param BlogTopic $item
     */
    public function Add($item){
        /** @var UProfileApp $uprofileApp */
        $uprofileApp = Abricos::GetApp('uprofile');
        $uprofileApp->UserAddToPreload($item->userid);

        return parent::Add($item);
    }

    /**
     * @param BlogApp $app
     * @param mixed $data List Options
     */
    public function Fill($app, $data){
        if (!$app->IsViewRole()){
            $this->SetError(Ab_Response::ERR_FORBIDDEN);
            return;
        }

        $args = $this->SetArgs($data);

        $rows = BlogQuery_Topic::TopicList($app->db, $this);
        while (($d = $app->db->fetch_array($rows))){
            /** @var BlogTopic $topic */
            $topic = $app->Create('Topic', $d);
            $this->Add($topic);
        }

        $topicIds = $this->ToArray('id');

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        $votingList = null;
        if (!empty($uratingApp)){
            $votingList = $uratingApp->VotingList('blog', 'topic', $topicIds);
        }

        $count = $this->Count();
        for ($i = 0; $i < $count; $i++){
            $topic = $this->GetByIndex($i);

            if (!empty($votingList)){
                $topic->voting = $votingList->GetByOwnerId($topic->id);
                $topic->voting->ownerDate = intval($topic->pubdate);
                $topic->voting->userid = intval($topic->userid);
            }
        }

        /** @var CommentApp $commentApp */
        $commentApp = Abricos::GetApp('comment');

        $statList = $commentApp->StatisticList('blog', 'topic', $topicIds);
        $count = $statList->Count();
        for ($i = 0; $i < $count; $i++){
            $stat = $statList->GetByIndex($i);
            $topic = $this->Get($stat->id);
            if (empty($topic)){
                continue; // what is it? %)
            }
            $topic->commentStatistic = $stat;
        }

        $sum = BlogQuery_Topic::TopicList($app->db, $this, true);

        $this->total = $sum['total'];
        $this->totalNew = $sum['totalNew'];
    }
}
