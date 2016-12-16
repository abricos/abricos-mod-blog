<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class BlogApp
 *
 * @property BlogManager $manager
 */
class BlogApp extends AbricosApplication {

    protected function GetClasses(){
        return array(
            'Blog' => 'Blog',
            'BlogList' => 'BlogList',
            'BlogSave' => 'BlogSave',
            'BlogUserRole' => 'BlogUserRole',
            'BlogUserRoleList' => 'BlogUserRoleList',
            'Topic' => 'BlogTopic',
            'TopicList' => 'BlogTopicList',
            'TopicSave' => 'BlogTopicSave',
            'TopicListOptions' => 'BlogTopicListOptions',
            'Tag' => 'BlogTag',
            'TagList' => 'BlogTagList',
            'TagListOptions' => 'BlogTagListOptions',
            'TagInTopic' => 'BlogTagInTopic',
            'TagInTopicList' => 'BlogTagInTopicList',
            'Config' => 'BlogConfig',
        );
    }

    protected function GetStructures(){
        return 'Blog,BlogUserRole,Topic,Tag,TagInTopic,Config';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case "blog":
                return $this->BlogToJSON($d->blogid);
            case "blogList":
                return $this->BlogListToJSON();
            case "blogSave":
                return $this->BlogSaveToJSON($d->data);

            case "blogJoin":
                return $this->BlogJoinToJSON($d->blogid);
            case "blogLeave":
                return $this->BlogLeaveToJSON($d->blogid);

            case "topic":
                return $this->TopicToJSON($d->topicid);
            case "topicList":
                return $this->TopicListToJSON($d->options);
            case "topicSave":
                return $this->TopicSaveToJSON($d->data);

            case "tagList":
                return $this->TagListToJSON($d->options);

            case "config":
                return $this->ConfigToJSON();
            case "configSave":
                return $this->ConfigSaveToJSON($d->data);

            //////////////// old functions /////////////

            case "topicpreview":
                return $this->TopicPreview($d->savedata);
            case "topicsave":
                return $this->TopicSave($d->savedata);
            case "topiclist":
                return $this->TopicListToAJAX($d);
            case "categorylist":
                return $this->CategoryListToAJAX();
            case "categorysave":
                return $this->CategorySave($d);
            case "categoryjoin":
                return $this->CategoryJoin($d->catid);
            case "categoryremove":
                return $this->CategoryRemove($d->catid);
            case "author":
                return $this->AuthorToAJAX($d->authorid);
            case "authorlist":
                return $this->AuthorListToAJAX($d);
            case "commentlivelist":
                return $this->CommentLiveListToAJAX($d);
        }
        return null;
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

    /*********************************************************/
    /*                          Blog                         */
    /*********************************************************/

    public function BlogToJSON($blogid){
        $res = $this->Blog($blogid);
        return $this->ResultToJSON('blog', $res);
    }

    public function Blog($blogid){
        $blogid = intval($blogid);

        $blogList = $this->BlogList();
        if (AbricosResponse::IsError($blogList)){
            return $blogList;
        }

        $blog = $blogList->Get($blogid);
        if (empty($blog)){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        if (!$blog->isEasyData){
            return $blog;
        }

        $d = BlogQuery::Blog($this->db, $blogid);
        $blog->Update($d);

        return $blog;
    }

    public function BlogListToJSON(){
        $res = $this->BlogList();
        return $this->ResultToJSON('blogList', $res);
    }

    public function BlogList(){
        if ($this->CacheExists('BlogList')){
            return $this->Cache('BlogList');
        }

        if (!$this->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var BlogList $list */
        $list = $this->InstanceClass('BlogList');
        $rows = BlogQuery::BlogList($this->db);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Blog', $d));
        }

        $blogids = $list->ToArray('id');

        /** @var BlogUserRoleList $userRoleList */
        $userRoleList = $this->InstanceClass('BlogUserRoleList');
        $rows = BlogQuery::BlogUserRoleList($this->db, $blogids);
        while (($d = $this->db->fetch_array($rows))){
            $userRoleList->Add($this->InstanceClass('BlogUserRole', $d));
        }

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        if (!empty($uratingApp)){
            $votingList = $uratingApp->VotingList('blog', 'blog', $blogids);
        }

        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $blog = $list->GetByIndex($i);

            $userRole = $userRoleList->GetByBlogId($blog->id);
            if (empty($userRole)){
                $userRole = $this->InstanceClass('BlogUserRole', array(
                    'blogid' => $blog->id,
                    'userid' => Abricos::$user->id
                ));
            }

            $blog->userRole = $userRole;

            if (!empty($votingList)){
                $blog->voting = $votingList->GetByOwnerId($blog->id);
            }
        }

        $this->SetCache('BlogList', $list);

        return $list;
    }

    public function BlogSaveToJSON($d){
        $res = $this->BlogSave($d);
        if (AbricosResponse::IsError($res)){
            return $res;
        }
        return $this->ImplodeJSON(array(
            $this->ResultToJSON('blogSave', $res),
            $this->BlogToJSON($res->blogid)
        ));
    }

    /**
     * @param mixed $d
     * @return BlogSave
     */
    public function BlogSave($d){
        /** @var BlogSave $ret */
        $ret = $this->InstanceClass('BlogSave', $d);
        if (!$this->IsAdminRole()){
            return $ret->SetError(AbricosResponse::ERR_FORBIDDEN);
        }

        $vars = $ret->vars;
        if (empty($vars->title)){
            return $ret->SetError(
                AbricosResponse::ERR_BAD_REQUEST,
                BlogSave::CODE_EMPTY_TITLE
            );
        }

        $vars->type = 'public';

        if (empty($vars->slug)){
            $vars->slug = translateruen($vars->title);
        }

        if ($vars->blogid === 0){
            $ret->blogid = BlogQuery::BlogAppend($this->db, $ret);

            $this->BlogJoinLeaveUpdate($ret->blogid, $ret->blogid);
        } else {
            BlogQuery::BlogUpdate($this->db, $ret);
        }
        $this->CacheClear();
        return $ret;
    }

    /*********************************************************/
    /*                      Blog Subscribe                   */
    /*********************************************************/

    private function BlogJoinLeaveUpdate($blogid, $isJoin){
        if (!$this->IsViewRole()
            || Abricos::$user->id === 0
        ){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $blog = $this->Blog($blogid);
        if (AbricosResponse::IsError($blog)){
            return $blog;
        }

        $userRole = $blog->userRole;
        if (empty($userRole->pubKey)){
            $userRole->pubKey = md5(TIMENOW.$blogid.$userRole->userid);
        }
        $userRole->isMember = $isJoin;

        BlogQuery::BlogJoinLeaveUpdate($this->db, $userRole);
        BlogQuery::BlogMemberCountUpdate($this->db, $blogid);

        $this->CacheClear();

        $blog = $this->Blog($blogid);

        $ret = new stdClass();
        $ret->blogid = $blogid;
        $ret->isMember = $isJoin;
        $ret->memberCount = $blog->memberCount;

        return $ret;
    }

    public function BlogJoinToJSON($blogid){
        $res = $this->BlogJoinLeaveUpdate($blogid, true);
        return $this->ResultToJSON('blogJoin', $res);
    }

    public function BlogLeaveToJSON($blogid){
        $res = $this->BlogJoinLeaveUpdate($blogid, false);
        return $this->ResultToJSON('blogLeave', $res);
    }

    /*********************************************************/
    /*                         Topic                         */
    /*********************************************************/

    public function TopicToJSON($topicid){
        $res = $this->Topic($topicid);
        return $this->ResultToJSON('topic', $res);
    }

    public function Topic($topicid){
        if (!$this->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $d = BlogQuery::Topic($this->db, $topicid);
        if (empty($d)){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        /** @var BlogTopic $topic */
        $topic = $this->InstanceClass('Topic', $d);


        /** @var CommentApp $commentApp */
        $commentApp = Abricos::GetApp('comment');

        $topic->commentStatistic = $commentApp->Statistic(BlogTopic::GetCommentOwner($topicid));

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        if (!empty($uratingApp)){
            $topic->voting = $uratingApp->Voting('blog', 'topic', $topicid);
            $topic->voting->ownerDate = intval($topic->pubdate);
            $topic->voting->userid = intval($topic->userid);
        }

        /** @var BlogTagList $tagList */
        $tagList = $this->InstanceClass('TagList');
        $rows = BlogQuery::TagInTopicList($this->db, $topic->id);
        while (($d = $this->db->fetch_array($rows))){
            $tagList->Add($this->InstanceClass('Tag', $d));
        }
        $topic->tagList = $tagList;

        return $topic;
    }

    /**
     * @param mixed $options
     * @return BlogTopicListOptions
     */
    public function TopicListOptionsNormalize($options = array()){
        if (!($options instanceof BlogTopicListOptions)){
            /** @var BlogTopicListOptions $options */
            $options = $this->InstanceClass('TopicListOptions', $options);
        }

        $vars = $options->vars;
        $vars->limit = min(max($vars->limit, 1), 100);
        $vars->page = max($vars->page, 1);
        return $options;
    }

    public function TopicListToJSON($options){
        $res = $this->TopicList($options);
        return $this->ResultToJSON('topicList', $res);
    }

    public function TopicList($optionsData){
        if (!$this->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $options = $this->TopicListOptionsNormalize($optionsData);

        /** @var BlogTopicList $list */
        $list = $this->InstanceClass('TopicList');
        $rows = BlogQuery::TopicList($this->db, $options);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Topic', $d));
        }

        $topicids = $list->ToArray('id');
        $userids = $list->ToArray('userid');

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        $votingList = null;
        if (!empty($uratingApp)){
            $votingList = $uratingApp->VotingList('blog', 'topic', $topicids);
        }

        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $topic = $list->GetByIndex($i);

            // $topic->user = $userList->Get($topic->userid);

            if (!empty($votingList)){
                $topic->voting = $votingList->GetByOwnerId($topic->id);
                $topic->voting->ownerDate = intval($topic->pubdate);
                $topic->voting->userid = intval($topic->userid);
            }
        }

        /** @var CommentApp $commentApp */
        $commentApp = Abricos::GetApp('comment');

        $statList = $commentApp->StatisticList('blog', 'topic', $topicids);
        $count = $statList->Count();
        for ($i = 0; $i < $count; $i++){
            $stat = $statList->GetByIndex($i);
            $topic = $list->Get($stat->id);
            if (empty($topic)){
                continue; // what is it? %)
            }
            $topic->commentStatistic = $stat;
        }

        $vars = $options->vars;

        if (!($vars->idsUse && $vars->idsSort)){
            return $list;
        }

        /** @var BlogTopicList $retList */
        $retList = $this->InstanceClass('TopicList');

        $ids = explode(',', $vars->ids);
        $count = count($ids);
        for ($i = 0; $i < $count; $i++){
            $topic = $list->Get(intval($ids[$i]));
            if (!empty($topic)){
                $retList->Add($topic);
            }
        }

        return $retList;
    }

    /**
     * @param array|BlogTopicListOptions $options
     * @return BlogTopicList|int
     */
    public function CommentLiveList($options = array()){
        if (!$this->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $options = $this->TopicListOptionsNormalize($options);
        $vars = $options->vars;
        $vars->idsUse = true;
        $vars->idsSort = true;

        $arr = array();
        $rows = BlogQuery::CommentLiveList($this->db, $options);
        while (($d = $this->db->fetch_array($rows))){
            $arr[] = intval($d['topicid']);
        }

        $vars->ids = implode(',', $arr);

        return $this->TopicList($options);
    }


    /*********************************************************/
    /*                       Topic Tag                       */
    /*********************************************************/

    /**
     * @param null $options
     * @return BlogTagList|int
     */
    public function TagList($options = null){

        /** @var BlogTagListOptions $options */
        $options = $this->InstanceClass('TagListOptions', $options);

        if (!$this->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var BlogTagList $list */
        $list = $this->InstanceClass('TagList');
        $rows = BlogQuery::TagList($this->db, $options);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Tag', $d));
        }
        return $list;
    }

    /*********************************************************/
    /*                         Config                        */
    /*********************************************************/

    public function ConfigToJSON(){
        return $this->ResultToJSON('config', $this->Config());
    }

    /**
     * @return BlogConfig|int
     */
    public function Config(){
        if ($this->CacheExists('Config')){
            return $this->Cache('Config');
        }
        if (!$this->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $d = array();
        $phrases = Abricos::GetModule('blog')->GetPhrases();
        for ($i = 0; $i < $phrases->Count(); $i++){
            $ph = $phrases->GetByIndex($i);
            $d[$ph->id] = $ph->value;
        }

        $d['subscribeSendLimit'] = isset($d['subscribeSendLimit'])
            ? $d['subscribeSendLimit'] : 25;

        $d['topicIndexRating'] = isset($d['topicIndexRating'])
            ? $d['topicIndexRating'] : 5;

        $d['categoryCreateRating'] = isset($d['categoryCreateRating'])
            ? $d['categoryCreateRating'] : 5;

        /** @var BlogConfig $config */
        $config = $this->InstanceClass('Config', $d);

        $this->SetCache('Config', $config);

        return $config;
    }

    public function ConfigSaveToJSON($d){
        $this->ConfigSave($d);
        return $this->ConfigToJSON();
    }

    public function ConfigSave($d){
        if (!$this->IsAdminRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $phs = Abricos::GetModule('blog')->GetPhrases();

        if (isset($d->subscribeSendLimit)){
            $phs->Set('subscribeSendLimit', $d->subscribeSendLimit);
        }
        if (isset($d->topicIndexRating)){
            $phs->Set('topicIndexRating', $d->topicIndexRating);
        }
        if (isset($d->categoryCreateRating)){
            $phs->Set('categoryCreateRating', $d->categoryCreateRating);
        }
        Abricos::$phrases->Save();
        $this->CacheClear();
    }


    /*********************************************************/
    /*                       Old Function                    */
    /*********************************************************/

    public function ParamToObject($o){
        if (is_array($o)){
            $ret = new stdClass();
            foreach ($o as $key => $value){
                $ret->$key = $value;
            }
            return $ret;
        } else if (!is_object($o)){
            return new stdClass();
        }
        return $o;
    }

    public function ToArray($rows, &$ids1 = "", $fnids1 = 'uid', &$ids2 = "", $fnids2 = '', &$ids3 = "", $fnids3 = ''){
        $ret = array();
        while (($row = $this->db->fetch_array($rows))){
            array_push($ret, $row);
            if (is_array($ids1)){
                $ids1[$row[$fnids1]] = $row[$fnids1];
            }
            if (is_array($ids2)){
                $ids2[$row[$fnids2]] = $row[$fnids2];
            }
            if (is_array($ids3)){
                $ids3[$row[$fnids3]] = $row[$fnids3];
            }
        }
        return $ret;
    }

    public function ToArrayId($rows, $field = "id"){
        $ret = array();
        while (($row = $this->db->fetch_array($rows))){
            $ret[$row[$field]] = $row;
        }
        return $ret;
    }

    /**
     * Список записей блога
     *
     * @param object $cfg параметры списка
     * @return BlogTopicList
     */
    public function old_TopicList($cfg = array()){

        $cfg = $this->ParamToObject($cfg);
        $cfg->limit = isset($cfg->limit) ? intval($cfg->limit) : 10;
        $cfg->page = isset($cfg->page) ? intval($cfg->page) : 1;
        $cfg->filter = isset($cfg->filter) ? $cfg->filter : '';

        $page = $cfg->page = max(intval($cfg->page), 1);
        $limit = $cfg->limit = max(1, min(25, intval($cfg->limit)));

        if (!is_string($cfg->filter)){
            $cfg->filter = "";
        }

        $fa = explode("/", $cfg->filter);
        $fType = isset($fa[0]) ? $fa[0] : '';
        $fPrm = isset($fa[1]) ? $fa[1] : '';
        if (isset($fa[2])){
            $fPrm .= "/".$fa[2];
        }
        $total = 0;
        $totalNew = 0;

        if (empty($fType)){
            $fType = 'index';
        } else if ($fType == 'new'){
            $fType = 'index';
            $fPrm = 'new';
        }

        switch ($fType){
            case "draft":
                $rows = BlogTopicQuery::TopicDraftList($this->db, Abricos::$user->id, $page, $limit);
                break;

            case "author":
                $rows = BlogTopicQuery::TopicListByAuthor($this->db, $fPrm, $page, $limit);
                break;

            case "index":    // список на главной (интересные/новые)
            case "pub":        // коллективные (интересные/новые)
            case "pers":    // персональные (хорошие/новые)
                $rows = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm);
                $total = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm, true);

                if ($fPrm == "new"){
                    $totalNew = $total;
                } else {
                    $totalNew = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, "new", true);
                }
                break;

            case "tag":
                $rows = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm);
                $total = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm, true);
                break;
            case "cat":
                $rows = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm);
                $total = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fPrm, true);

                if (isset($fa[2]) && $fa[2] == "new"){
                    $totalNew = $total;
                } else {
                    $totalNew = BlogTopicQuery::TopicList($this->db, $page, $limit, $fType, $fa[1]."/new", true);
                }
                break;

            default:
                $rows = BlogTopicQuery::TopicList($this->db, $page, $limit);
                $total = BlogTopicQuery::TopicList($this->db, $page, $limit, "", "", true);
                $totalNew = BlogTopicQuery::TopicList($this->db, $page, $limit, "", "new", true);
                break;
        }

        $topics = array();
        $topicids = array();
        $userids = array();

        while (($row = $this->db->fetch_array($rows))){
            $topic = new BlogTopicInfo($row);
            array_push($topics, $topic);
            $topicids[] = $topic->id;
            $userids[] = $topic->userid;
        }

        $this->TopicSetTags($topics);

        $list = new BlogTopicList($topics, $total, $totalNew);

        return $list;
    }


    public function TopicToAJAX($topicid){
        $topic = $this->Topic($topicid);
        if (is_null($topic)){
            return null;
        }

        // проверить рассылку (подобие крона)
        $this->SubscribeTopicCheck();

        $ret = new stdClass();
        $ret->topic = $topic->ToAJAX();
        return $ret;
    }

    public function TopicPreview($d){
        if (!$this->IsWriteRole()){
            return null;
        }

        $utm = Abricos::TextParser();
        $utmf = Abricos::TextParser(true);

        $d->tl = $utmf->Parser($d->tl);
        $d->intro = $utm->Parser($d->intro);
        $d->body = $utm->Parser($d->body);

        $topic = new BlogTopic(array(
            "catid" => $d->catid,
            "tl" => $d->tl,
            "intro" => $d->intro,
            "bd" => $d->body,
            "bdlen" => strlen($d->body),
            "uid" => Abricos::$user->id,
            "unm" => Abricos::$user->username
        ));

        // список тегов. не более 25
        for ($i = 0; $i < min(count($d->tags), 25); $i++){
            $tag = $utmf->Parser($d->tags[$i]);

            if (function_exists('mb_strtolower')){
                $tag = mb_strtolower($tag, 'UTF-8');
            }

            if (empty($tag)){
                continue;
            }
            array_push($topic->tags, new BlogTopicTag(array(
                "id" => $i + 1,
                "tl" => $tag,
                "nm" => $tag
            )));
        }

        $ret = new stdClass();
        $ret->topic = $topic->ToAJAX();
        return $ret;
    }

    /**
     * Сохранение топика
     *
     * Коды ошибок:
     *    null - нет прав,
     *    1 - заголовок не может быть пустым,
     *    2 - должны быть указаны метки,
     *  11 - черновиков не более 25 на профиль,
     *  12 - публиковать не более 3-х в сутки,
     *    20 - недостаточно репутации для публикации в любой категории,
     *  21 - недостаточно репутации для публикации именно в этй категории,
     *  99 - неизвестная ошибка
     *
     * @param object $d
     */
    public function TopicSave($d){
        if (!$this->IsWriteRole()){
            return null;
        }

        $ret = new stdClass();
        $ret->error = 0;
        $ret->topicid = 0;

        $d->id = isset($d->id) ? intval($d->id) : 0;
        $d->dft = isset($d->dft) ? intval($d->dft) : 0;
        $d->idx = isset($d->idx) ? intval($d->idx) : 0;
        $d->aidx = isset($d->aidx) ? intval($d->aidx) : 0;
        $d->catid = isset($d->catid) ? intval($d->catid) : 0;

        $d->tl = isset($d->tl) ? strval($d->tl) : '';
        $d->nm = isset($d->nm) ? strval($d->nm) : '';

        // проверка категории на возможность публиковать в ней
        $cat = null; // null - персональный блог
        if ($d->catid > 0){
            $cat = $this->Category($d->catid);

            if (empty($cat)){
                return null;
            } // hacker?

            if (!$cat->IsTopicWrite()){
                return null; // только участник может публиковать в блог
            }
        }

        // проверка топика
        $topic = null; // текущий топик в базе, если null - создается новый
        if ($d->id > 0){
            $topic = $this->Topic($d->id);
            if (empty($topic)){
                return null;
            } // hacker?

            if (!$this->IsAdminRole()){
                // автор ли топика правит его?
                if ($topic->user->id != Abricos::$user->id){
                    return null;
                } // hacker?
            }
            $d->pdt = $topic->publicDate;

            if ($topic->publicDate == 0 && $d->dft == 0){ // публикация черновика
                $d->pdt = TIMENOW;
            }
        }

        $isNewPublic = false;
        $isNewDraft = false;

        // проверка на добавление в базу нового топика
        if ($d->id == 0){
            if ($d->dft == 1){ // будет добавлен черновик
                $isNewDraft = true;
                $d->pdt = 0;
            } else { // будет опубликован новый топик
                $isNewPublic = true;
                $d->pdt = TIMENOW;
            }
        } else { // сохранение существующего
            if ($topic->isDraft && $d->dft != 0){ // черновик станет публикацией
                $isNewPublic = true;
                if ($topic->publicDate == 0){ // публикация в первый раз
                    $d->pdt = TIMENOW;
                }
            } else if (!$topic->isDraft && $d->dft == 0){ // публикация станет черновиком

            } else { // просто сохранен без смены статуса черновика

            }
        }

        if (!$this->IsAdminRole()){
            // ограничения по количеству
            if ($isNewDraft){ // не более 25 черновиков на профиль
                $row = BlogTopicQuery::TopicDraftCountByUser($this->db, Abricos::$user->id);
                if (!empty($row) && $row['cnt'] >= 25){
                    $ret->error = 11;
                    return $ret;
                }
            } else if ($isNewPublic){ // проверки по публикации
                $row = BlogTopicQuery::TopicPublicCountByUser($this->db, Abricos::$user->id);
                $pubCount = intval($row['cnt']);

                if ($pubCount > 3){ // не более 3 публикаций в день
                    $ret->error = 12;
                    return $ret;
                }

                // ограничения по репутации
                if (false /*BlogManager::$isURating*/){ // работает система репутации пользователя

                    $urep = $this->GetURatingManager()->UserReputation();

                    // ограничения по репутации категории
                    if (!empty($cat) && $urep->reputation < $cat->reputation){
                        $ret->error = 21;
                        return $ret;
                    }

                    if ($urep->reputation == 0 && empty($cat)){ // публикует с нулевой репутацией в персональный блог
                        // ограничения для персонального блога не более 3-х публикаций в сутки

                    } else if ($urep->reputation < 1){ // для публикации в коллективном блоге необходима репутация > 0
                        $ret->error = 20;
                        return $ret;
                    }

                }
            }
        }

        $utm = Abricos::TextParser();
        $utmf = Abricos::TextParser(true);

        // список тегов. не более 25
        $tags = array();
        for ($i = 0; $i < min(count($d->tags), 25); $i++){
            $tag = $utmf->Parser($d->tags[$i]);

            if (function_exists('mb_strtolower')){
                $tag = mb_strtolower($tag, 'UTF-8');
            }

            if (empty($tag)){
                continue;
            }
            array_push($tags, $tag);
        }

        if (count($tags) == 0){ // хотябы одно ключевое слово должно быть заполнено
            $ret->error = 2;
            return $ret;
        }

        $d->tl = $utmf->Parser($d->tl);
        if (empty($d->tl)){
            $ret->error = 1;
            return $ret;
        }
        $d->nm = $utmf->Parser($d->nm);
        if (empty($d->nm)){
            $d->nm = translateruen($d->tl);
        }

        $d->intro = $utm->Parser($d->intro);
        $d->body = $utm->Parser($d->body);

        // META заголовки
        $d->mtks = implode(", ", $tags); // keywords
        $d->mtdsc = $utmf->Parser($d->intro); // descript
        $d->mtdsc = substr($d->mtdsc, 0, 245).(strlen($d->mtdsc) > 245 ? " ..." : "");

        // все проверки выполнены, добавление/сохранение топика
        if ($d->id == 0){
            $d->id = BlogTopicQuery::TopicAppend($this->db, Abricos::$user->id, $d);
            if ($d->id == 0){
                $ret->error = 99;
                return $ret;
            }
        } else {
            BlogTopicQuery::TopicUpdate($this->db, $d->id, $d);
        }

        // обновление тегов
        BlogTopicQuery::TagUpdate($this->db, $tags);
        BlogTopicQuery::TopicTagUpdate($this->db, $d->id, $tags);
        BlogTopicQuery::TopicTagCountUpdate($this->db, $tags);
        BlogTopicQuery::CategoryTopicCountUpdate($this->db);

        $ret->topicid = $d->id;

        if ($this->IsAdminRole()){
            $topic = $this->Topic($d->id);

            $isIndex = $d->idx > 0;

            if ($isIndex){
                // включить принудительный вывод на главную
                BlogTopicQuery::TopicIndexUpdateByAdmin($this->db, $d->id, $isIndex, !$isIndex);
            } else if (!$isIndex){
                // отключить принудительный вывод на главную
                BlogTopicQuery::TopicIndexUpdateByAdmin($this->db, $d->id, $isIndex, !$isIndex);
            }
        }

        return $ret;
    }

    /**
     * Генерация META заголовков для топиков, созданных предыдущей версии модуля
     *
     * @param BlogTopic $topic
     */
    public function TopicMetaTagBuild(BlogTopic $topic){
        if (empty($topic)){
            return;
        }
        if (!empty($topic->metadesc) || !empty($topic->metakeys)){
            return;
        }

        $utmf = Abricos::TextParser(true);

        $atags = array();
        for ($ti = 0; $ti < count($topic->tags); $ti++){
            array_push($atags, $topic->tags[$ti]->title);
        }

        $topic->metakeys = implode(", ", $atags); // keywords
        $topic->metadesc = $utmf->Parser($topic->intro); // descript
        $topic->metadesc = substr($topic->metadesc, 0, 245).(strlen($topic->metadesc) > 245 ? " ..." : "");

        BlogTopicQuery::TopicMetaTagUpdate($this->db, $topic->id, $topic->metakeys, $topic->metadesc);
    }


    /**
     * Сохранение категории (блога)
     *
     * Коды ошибок:
     *    null - нет прав,
     *    1 - заголовок не может быть пустым,
     *    5 - пользователю можно создавать категорию не более одной в сутки,
     *    10 - недостаточно репутации,
     *  99 - неизвестная ошибка
     */
    public function CategorySave($d){
        if (!$this->IsWriteRole()){
            return null;
        }

        $ret = new stdClass();
        $ret->error = 0;
        $ret->catid = 0;

        $utm = Abricos::TextParser();
        $utmf = Abricos::TextParser(true);

        $d->tl = $utmf->Parser($d->tl);
        if (empty($d->tl)){
            $ret->error = 1;
            return $ret;
        }
        $d->nm = $utmf->Parser($d->nm);
        if (empty($d->nm)){
            $d->nm = translateruen($d->tl);
        }

        $d->dsc = isset($d->dsc) ? $utm->Parser($d->dsc) : '';
        $d->rep = isset($d->rep) ? intval($d->rep) : 0;
        $d->prv = isset($d->prv) ? intval($d->prv) : 0;

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');

        if (!$this->IsAdminRole()){

            /** @var UProfileApp $uprofileApp */
            $uprofileApp = Abricos::GetApp('uprofile');
            $profile = $uprofileApp->Profile(Abricos::$user->id);

            if (false /*BlogManager::$isURating/**/){ // работает система репутации пользователя
                $rep = $this->GetURatingManager()->UserReputation();
                if ($rep->reputation < BlogConfig::$instance->categoryCreateRating){ // для создании/редактировании категории необходима репутация >= 5
                    $ret->error = 10;
                    return $ret;
                }
            }
        }

        if ($d->id == 0){ // создание новой категории

            if (!$this->IsAdminRole()){

                // категорию создает не админ
                // значит нужно наложить ограничения
                // не более 1 категории в день (пока так)
                $dbCat = BlogTopicQuery::CategoryLastCreated($this->db, Abricos::$user->id);
                if (!empty($dbCat) && $dbCat['dl'] + 60 * 60 * 24 > TIMENOW){
                    $ret->error = 5;
                    return $ret;
                }
            }

            $d->id = BlogTopicQuery::CategoryAppend($this->db, Abricos::$user->id, $d);
            if ($d->id == 0){
                $ret->error = 99;
                return $ret;
            }

            $this->CategoryJoin($d->id);
        } else {
            // А есть ли права админа на правку категории
            $cat = $this->Category($d->id);
            if (empty($cat)){
                return null;
            }

            if (!$cat->IsAdmin()){
                return null;
            }

            BlogTopicQuery::CategoryUpdate($this->db, $d->id, $d);
        }

        $ret->catid = $d->id;

        $cats = $this->CategoryListToAJAX();
        $ret->categories = $cats->categories;

        return $ret;
    }

    /**
     * Вступить/выйти из блога текущему пользователю
     */
    public function CategoryJoin($catid){
        if (!$this->IsViewRole() || Abricos::$user->id == 0){
            return null;
        }

        $cat = $this->Category($catid);
        if (is_null($cat)){
            return null;
        }

        $pubkey = md5(TIMENOW.$catid."+".Abricos::$user->id);

        BlogTopicQuery::CategoryUserSetMember($this->db, $catid, Abricos::$user->id, !$cat->isMemberFlag, $pubkey);

        BlogTopicQuery::CategoryMemberCountUpdate($this->db, $catid);

        $cat = $this->Category($catid);

        $ret = new stdClass();
        $ret->category = $cat->ToAJAX();
        return $ret;
    }

    public function CategoryRemove($catid){
        if (!$this->IsAdminRole()){
            return null;
        }

        BlogTopicQuery::CategoryRemove($this->db, $catid);

        $ret = new stdClass();
        $cats = $this->CategoryListToAJAX();
        $ret->categories = $cats->categories;
        return $ret;
    }

    public function AuthorList($cfg){
        if (!$this->IsViewRole()){
            return null;
        }

        $cfg = $this->ParamToObject($cfg);
        $cfg->page = max(intval($cfg->page), 1);

        if (empty($cfg->limit)){
            $cfg->limit = 5;
        }
        $cfg->limit = max(min($cfg->limit, 25), 1);


        $list = array();

        $rows = BlogTopicQuery::AuthorList($this->db, $cfg->page, $cfg->limit);
        while (($row = $this->db->fetch_array($rows))){
            array_push($list, new BlogAuthor($row));
        }
        return new BlogAuthorList($list);
    }

    public function AuthorListToAJAX($cfg){
        $list = $this->AuthorList($cfg);
        if (is_null($list)){
            return null;
        }

        return $list->ToAJAX();
    }

    public function Author($authorid){
        if (!$this->IsViewRole()){
            return null;
        }

        $row = BlogTopicQuery::Author($this->db, $authorid);
        if (empty($row)){
            return null;
        }
        return new BlogAuthor($row);
    }

    public function AuthorByUserName($username){
        if (!$this->IsViewRole()){
            return null;
        }

        $row = BlogTopicQuery::AuthorByUserName($this->db, $username);

        if (empty($row)){
            return null;
        }
        return new BlogAuthor($row);
    }

    public function AuthorToAJAX($authorid){
        $author = $this->Author($authorid);
        if (is_null($author)){
            return null;
        }
        $ret = new stdClass();
        $ret->author = $author->ToAJAX();
        return $ret;
    }



    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /*                      URating                        */
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param URatingOwner $owner
     * @param URatingVoting $voting
     */
    public function URating_IsToVote($owner, $voting){
        if ($this->IsAdminRole()){ // админу можно голосовать всегда
            return true;
        }

        $type = $owner->type;
        $ownerid = $owner->ownerid;

        if ($type === 'topic-comment'){
            /** @var CommentApp $commentApp */
            $commentApp = Abricos::GetApp('comment');
            $topicid = $commentApp->OwnerIdByCommentId('blog', 'topic', $ownerid);
            if (empty($topicid)){
                return false;
            }
            $type = 'topic';
            $ownerid = $topicid;
        }

        if ($type === 'topic'){
            $topic = $this->Topic($ownerid);
            if (empty($topic)){
                return false;
            }
            return true;
        }

        if ($owner->type === 'blog'){
            $category = $this->Category($ownerid);
            return !empty($category);
        }
        return false;
    }

    /**
     * @param URatingOwner $owner
     */
    public function URating_GetOwnerDate($owner){
        if ($owner->type === 'blog'){
        } else if ($owner->type === 'topic'){
            $topic = $this->Topic($owner->ownerid);
            return !empty($topic) ? $topic->publicDate : 0;
        } else if ($owner->type === 'comment'){
        }
        return 0;
    }


    /**
     * Занести результат расчета рейтинга элемента (топика, блога)
     *
     * Метод вызывается из модуля urating
     *
     * @param string $eltype
     * @param integer $elid
     * @param array $vote
     *//*
    public function URating_OnElementVoting($eltype, $elid, $info){

        if ($eltype == 'cat'){
            BlogTopicQuery::CategoryRatingUpdate($this->db, $elid, $info['cnt'], $info['up'], $info['down']);
        } else if ($eltype == 'topic'){
            $topicid = $elid;
            $rating = $info['up'] - $info['down'];

            BlogTopicQuery::TopicRatingUpdate($this->db, $topicid, $info['cnt'], $info['up'], $info['down']);

            $isIndex = $rating >= $this->Config()->topicIndexRating;
            BlogTopicQuery::TopicIndexUpdateByRating($this->db, $topicid, $isIndex);
        }
    }/**/

    /**
     * Расчет рейтинга пользователя
     *
     * Метод запрашивает модуль URating
     *
     * +10 - за каждый положительный голос в репутацию
     * -10 - за каждый отрицательный голос в репутацию
     *
     * @param integer $userid
     *//*
    public function URating_UserCalculate($userid){

        // $rep = $this->UserReputation($userid);

        $ret = new stdClass();
        // $ret->skill = $rep->reputation * 10;
        return $ret;
    }/**/

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /*                     Comments                        */
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function Comment_IsWrite($type, $ownerid){
        if (!$this->IsWriteRole() || $type !== 'topic'){
            return false;
        }

        $topic = $this->Topic($ownerid);
        return !empty($topic);
    }

    public function Comment_IsList($type, $ownerid){
        if (!$this->IsViewRole() || $type !== 'topic'){
            return false;
        }

        $topic = $this->Topic($ownerid);
        return !empty($topic);
    }

    /**
     * @param string $type
     * @param Comment $comment
     * @param Comment $parentComment
     */
    public function Comment_SendNotify($type, $ownerid, $comment, $parentComment){
        $topic = $this->Topic($ownerid);
        if (empty($topic)){
            return;
        }

        $emails = array();

        /** @var NotifyApp $notifyApp */
        $notifyApp = Abricos::GetApp('notify');

        $host = Ab_URI::Site();
        $tpLink = $host.$topic->URL();

        // уведомление "комментарий на комментарий"
        if (!empty($parentComment) && $parentComment->userid != Abricos::$user->id){

            $brick = Brick::$builder->LoadBrickS('blog', 'notifyCommentAnswer', null, null);
            $v = $brick->param->var;

            $email = $parentComment->user->email;
            if (!empty($email)){
                $emails[$email] = true;

                $mail = $notifyApp->MailByFields(
                    $email,
                    Brick::ReplaceVarByData($v['subject'], array(
                        "title" => $topic->title
                    )),
                    Brick::ReplaceVarByData($brick->content, array(
                        "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name'),
                        "email" => $email,
                        "unm" => $comment->user->GetViewName(),
                        "title" => $topic->title,
                        "tpclnk" => $tpLink,
                        "parentComment" => $parentComment->body." ",
                        "comment" => $comment->body." ",
                    ))
                );
                $notifyApp->MailSend($mail);
            }

        }

        // уведомление автору
        if ($topic->user->id == Abricos::$user->id){
            // свой комментарий в уведомление не нуждается
            return;
        }

        $author = $topic->user;
        $email = $author->email;
        if (empty($email) || isset($emails[$email])){
            return;
        }

        $brick = Brick::$builder->LoadBrickS('blog', 'notifyComment', null, null);
        $v = $brick->param->var;

        $mail = $notifyApp->MailByFields(
            $email,
            Brick::ReplaceVarByData($v['subject'], array(
                "title" => $topic->title
            )),
            Brick::ReplaceVarByData($brick->content, array(
                "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name'),
                "email" => $email,
                "unm" => $comment->user->GetViewName(),
                "title" => $topic->title,
                "tpclnk" => $tpLink,
                "comment" => $comment->body." ",
            ))
        );
        $notifyApp->MailSend($mail);
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /*                       Subscribe                     */
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Проверить наличие новых топиков и отправить по ним письма-уведомления
     *
     * @param integer $sendlimit лимит отправки за раз
     */
    public function SubscribeTopicCheck(){

        $sendlimit = $this->Config()->subscribeSendLimit;

        // Топик по которому есть рассылка
        $row = BlogTopicQuery::SubscribeTopic($this->db);
        if (empty($row)){
            return;
        }

        $topic = $this->Topic($row['id']);
        if (empty($topic)){
            return;
        }

        $users = array();
        $lastid = 0;
        $rows = BlogTopicQuery::SubscribeUserList($this->db, $topic->catid, $row['sluid'], $sendlimit);
        while (($u = $this->db->fetch_array($rows))){
            $lastid = max($u['id'], $lastid);

            if ($u['id'] == $topic->user->id || empty($u['eml']) || $u['scboff'] == 1 || $u['scboffall'] == 1){
                continue;
            }
            array_push($users, $u);
        }

        if ($lastid == 0){ // нет пользователей для рассылки
            BlogTopicQuery::SubscribeTopicComplete($this->db, $topic->id);
        } else {
            BlogTopicQuery::SubscribeTopicUpdate($this->db, $topic->id, $lastid);
        }

        // осуществить рассылку
        for ($i = 0; $i < count($users); $i++){
            $this->SubscribeTopicSend($topic, $users[$i]);
        }
    }

    private function SubscribeTopicSend(BlogTopic $topic, $user){
        $email = $user['eml'];
        if (empty($email)){
            return;
        }

        $brick = $this->Cache('brick', 'notifyNewTopic');
        if (!$brick){
            $brick = Brick::$builder->LoadBrickS('blog', 'notifyNewTopic');
            $this->SetCache('brick', 'notifyNewTopic', $brick);
        }

        /** @var NotifyApp $notifyApp */
        $notifyApp = Abricos::GetApp('notify');

        $v = $brick->param->var;
        $host = Ab_URI::Site();

        $cat = $topic->Category();

        $tLnk = $host.$topic->URL();
        $unLnkBlog = $host."/blog/_unsubscribe/".$user['id']."/".$user['pubkey']."/".$topic->catid."/";
        $unLnkAll = $host."/blog/_unsubscribe/".$user['id']."/".$user['pubkey']."/all/";

        $mail = $notifyApp->MailByFields(
            $email,
            Brick::ReplaceVarByData($v['subject'], array(
                "title" => $topic->title
            )),
            Brick::ReplaceVarByData($brick->content, array(
                "email" => $email,
                "unm" => $topic->user->GetUserName(),
                "blog" => $cat->title,
                "topic" => $topic->title,
                "tlnk" => $tLnk,
                "unlnkall" => $unLnkAll,
                "unlnkallblog" => $unLnkBlog,
                "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name'),
                "topicintro" => $topic->intro
            ))
        );
        $notifyApp->MailSend($mail);
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /*                         RSS                         */
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function RSS_GetItemList($inBosUI = false){
        $ret = array();

        $url = Ab_URI::Site();
        if ($inBosUI){
            $url .= "/bos/#app=blog/wspace/ws/topic/TopicViewWidget/";
        } else {
            $url .= "/blog/";
        }

        $i18n = $this->manager->module->I18n();

        $topics = $this->TopicList();

        for ($i = 0; $i < $topics->Count(); $i++){
            $topic = $topics->GetByIndex($i);
            $cat = $topic->Category();

            $title = $topic->title." / ".$cat->title;

            if ($inBosUI){
                $link = $url.$topic->id."/";
            } else {
                if ($cat instanceof BlogPersonalCategory){
                    $link = $url.'author/'.$cat->user->username."/".$topic->id."/";
                } else {
                    $link = $url.$cat->name."/".$topic->id."/";
                }
            }
            $item = new RSSItem($title, $link, $topic->intro, $topic->publicDate);
            $item->modTitle = $i18n->Translate('modtitle');
            $ret[] = $item;
        }

        return $ret;
    }
}
