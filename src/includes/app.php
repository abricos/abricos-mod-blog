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
            'Config' => 'BlogConfig'
        );
    }

    protected function GetStructures(){
        return 'Config';
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

    public function ResponseToJSON($d){
        switch ($d->do){
            case "config":
                return $this->ConfigToJSON();
            case "configSave":
                return $this->ConfigSaveToJSON($d->data);

            case "topic":
                return $this->TopicToAJAX($d->topicid);
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
            case "taglist":
                return $this->TagListToAJAX($d);
        }
        return null;
    }

    /*********************************************************/
    /*                           Config                      */
    /*********************************************************/

    public function ConfigToJSON(){
        return $this->ResultToJSON('config', $this->Config());
    }

    /**
     * @return BlogConfig
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

    private function TopicSetTags($topics){
        $tids = array();

        foreach ($topics as $topic){
            array_push($tids, $topic->id);
        }

        $rows = BlogTopicQuery::TopicTagList($this->db, $tids);
        $toptags = $this->ToArray($rows);

        $rows = BlogTopicQuery::TagListByTopicIds($this->db, $tids);
        $dbtags = $this->ToArrayId($rows);

        for ($i = 0; $i < count($topics); $i++){
            $topic = $topics[$i];
            $tags = array();
            for ($ii = 0; $ii < count($toptags); $ii++){
                $tt = $toptags[$ii];
                if ($tt['tid'] == $topic->id){
                    array_push($tags, new BlogTopicTag($dbtags[$tt['tgid']]));
                }
            }
            $topic->tags = $tags;
        }
    }

    /**
     * Список записей блога
     *
     * @param object $cfg параметры списка
     * @return BlogTopicList
     */
    public function TopicList($cfg = array()){
        if (!$this->IsViewRole()){
            return null;
        }

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

        /** @var UProfileApp $uprofileApp */
        $uprofileApp = Abricos::GetApp('uprofile');
        $userList = $uprofileApp->UserListByIds($userids);

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        $votingList = null;
        if (!empty($uratingApp)){
            $votingList = $uratingApp->VotingList('blog', 'topic', $topicids);
        }

        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $topic = $list->GetByIndex($i);

            $topic->user = $userList->Get($topic->userid);

            if (!empty($votingList)){
                $topic->voting = $votingList->GetByOwnerId($topic->id);
                $topic->voting->ownerDate = intval($topic->publicDate);
                $topic->voting->userid = intval($topic->userid);
            }
        }

        /** @var CommentApp $commentApp */
        $commentApp = Abricos::GetApp('comment');

        $statList = $commentApp->StatisticList('blog', 'topic', $topicids);
        $cnt = $statList->Count();
        for ($i = 0; $i < $cnt; $i++){
            $stat = $statList->GetByIndex($i);
            $topic = $list->Get($stat->id);
            if (empty($topic)){
                continue; // what is it? %)
            }
            $topic->commentStatistic = $stat;
        }

        return $list;
    }

    public function TopicListToAJAX($cfg){
        $topicList = $this->TopicList($cfg);
        if (is_null($topicList)){
            return null;
        }
        return $topicList->ToAJAX();
    }

    /**
     * @return BlogTopic
     */
    public function Topic($topicid){
        if (!$this->IsViewRole()){
            return null;
        }

        $row = BlogTopicQuery::TopicById($this->db, $topicid);
        if (empty($row)){
            return null;
        }
        $topic = new BlogTopic($row);

        /** @var UProfileApp $uprofileApp */
        $uprofileApp = Abricos::GetApp('uprofile');

        $topic->user = $uprofileApp->User($topic->userid);

        /** @var CommentApp $commentApp */
        $commentApp = Abricos::GetApp('comment');

        $topic->commentStatistic = $commentApp->Statistic($topic->GetCommentOwner());

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        if (!empty($uratingApp)){
            $topic->voting = $uratingApp->Voting('blog', 'topic', $topicid);
            $topic->voting->ownerDate = intval($topic->publicDate);
            $topic->voting->userid = intval($topic->userid);
        }

        $this->TopicSetTags(array($topic));

        return $topic;
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
     * @return BlogCategoryList
     */
    public function CategoryList(){
        if ($this->CacheExists('CategoryList')){
            return $this->Cache('CategoryList');
        }

        if (!$this->IsViewRole()){
            return null;
        }

        $cats = array();
        $catids = array();
        $rows = BlogTopicQuery::CategoryList($this->db);
        while (($row = $this->db->fetch_array($rows))){
            $category = new BlogCategory($row);
            $catids[] = $category->id;
            array_push($cats, $category);
        }
        $list = new BlogCategoryList($cats);

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        if (!empty($uratingApp)){
            $votingList = $uratingApp->VotingList('blog', 'blog', $catids);

            $count = $list->Count();
            for ($i = 0; $i < $count; $i++){
                $category = $list->GetByIndex($i);
                $category->voting = $votingList->GetByOwnerId($category->id);
            }
        }

        $this->SetCache('CategoryList', $list);

        return $list;
    }

    public function Category($catid){
        if (!$this->IsViewRole()){
            return null;
        }

        $row = BlogTopicQuery::Category($this->db, $catid);
        if (empty($row)){
            return null;
        }

        return new BlogCategory($row);
    }

    public function CategoryListToAJAX(){
        $catList = $this->CategoryList();
        if (is_null($catList)){
            return null;
        }
        return $catList->ToAJAX();
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

        if (!$this->IsAdminRole()){

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

        // повторно запросить категорию
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

    /**
     * Прямой эфир
     *
     * @param object $cfg
     * @return BlogCommentLiveList
     */
    public function CommentLiveList($cfg = null){
        if (!$this->IsViewRole()){
            return null;
        }

        $cfg = $this->ParamToObject($cfg);
        $cfg->limit = isset($cfg->limit) ? intval($cfg->limit) : 5;
        $cfg->page = isset($cfg->page) ? intval($cfg->page) : 1;

        $cfg->page = max(intval($cfg->page), 1);
        $cfg->limit = max(min($cfg->limit, 25), 1);

        $list = array();
        $tids = array();

        $rows = BlogTopicQuery::CommentLiveList($this->db, $cfg->page, $cfg->limit);
        while (($row = $this->db->fetch_array($rows))){
            $cmtLive = new BlogCommentLive($row);

            array_push($list, $cmtLive);
            array_push($tids, $cmtLive->topicid);
        }
        $topics = array();
        $topicsById = array();
        $userids = array();

        $rows = BlogTopicQuery::TopicListByIds($this->db, $tids);
        while (($row = $this->db->fetch_array($rows))){
            $topic = new BlogTopicInfo($row);
            $topicsById[$topic->id] = $topic;
            $topics[] = $topic;
            $userids[] = $topic->userid;

            for ($i = 0; $i < count($list); $i++){
                if ($list[$i]->topicid == $topic->id){
                    $list[$i]->topic = $topic;
                }
            }
        }

        /** @var UProfileApp $uprofileApp */
        $uprofileApp = Abricos::GetApp('uprofile');
        $userList = $uprofileApp->UserListByIds($userids);

        $count = count($topics);
        for ($i = 0; $i < $count; $i++){
            $topic = $topics[$i];
            $topic->user = $userList->Get($topic->userid);
        }

        $this->TopicSetTags($topics);

        /** @var CommentApp $commentApp */
        $commentApp = Abricos::GetApp('comment');

        $statList = $commentApp->StatisticList('blog', 'topic', $tids);
        $cnt = $statList->Count();
        for ($i = 0; $i < $cnt; $i++){
            $stat = $statList->GetByIndex($i);
            $topic = $topicsById[$stat->id];

            if (empty($topic)){
                continue; // what is it? %)
            }
            $topic->commentStatistic = $stat;
        }

        return new BlogCommentLiveList($list);
    }

    public function CommentLiveListToAJAX($cfg){
        $list = $this->CommentLiveList($cfg);
        if (is_null($list)){
            return null;
        }
        return $list->ToAJAX();
    }

    public function TagList($cfg){
        if (!$this->IsViewRole()){
            return null;
        }

        $cfg = $this->ParamToObject($cfg);

        $cfg->limit = isset($cfg->limit) ? intval($cfg->limit) : 25;
        $cfg->page = isset($cfg->page) ? intval($cfg->page) : 1;

        $cfg->limit = max(min($cfg->limit, 100), 1);

        $list = array();

        $rows = BlogTopicQuery::TagList($this->db, $cfg->page, $cfg->limit);

        while (($row = $this->db->fetch_array($rows))){
            array_push($list, new BlogTopicTag($row));
        }
        return new BlogTopicTagList($list);
    }

    public function TagListToAJAX($cfg){
        $list = $this->TagList($cfg);
        if (is_null($list)){
            return null;
        }

        return $list->ToAJAX();
    }

    /**
     * Список тегов по запросу (автозаполение)
     *
     * @param string $query
     */
    public function TagListByLikeQuery($query){
        if (empty($query) || !$this->IsViewRole()){
            return null;
        }

        $ret = array();

        $rows = BlogTopicQuery::TagListByLikeQuery($this->db, $query);
        while (($row = $this->db->fetch_array($rows))){
            array_push($ret, $row['tl']);
        }

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
     * Можно ли проголосовать текущему пользователю за комментарий
     *
     * Метод вызывается из модуля Comment
     *
     * Возвращает код ошибки:
     *  0 - все нормально, голосовать можно,
     *  5 - закончился период для голосования
     *
     * @param URatingUserReputation $uRep
     * @param unknown_type $act
     * @param unknown_type $commentid
     * @param unknown_type $contentid
     *//*
    public function Comment_IsVoting(URatingUserReputation $uRep, $act, $commentid, $contentid){

        $topic = $this->Topic(0, $contentid);
        if (empty($topic)){
            return 99;
        }

        if (!$topic->IsVotingPeriod()){
            return 5;
        }

        return 0;
    }/**/


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

        /** @var UProfileApp $uprofileApp */
        $uprofileApp = Abricos::GetApp('uprofile');

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

        $author = $uprofileApp->User($topic->user->id);
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
                    $link = $url.'author/'.$cat->user->userName."/".$topic->id."/";
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

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /*                       BosUI                         */
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function Bos_OnlineData(){
        $ret = $this->TopicListToAJAX(array("limit" => 1));
        return $ret->topics->total;
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
