var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'uprofile', files: ['users.js']},
        {name: 'urating', files: ['vote.js']},
        {name: 'widget', files: ['notice.js', 'lib.js']}
    ]
};
Component.entryPoint = function(NS){

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isWrite: 20,
        isView: 10
    });

    var L = YAHOO.lang,
        R = NS.roles;

    var SysNS = Brick.mod.sys;
    var UP = Brick.mod.uprofile;
    var LNG = this.language;
    var UID = Brick.env.user.id;


    var buildTemplate = this.buildTemplate;
    buildTemplate({}, '');

    NS.lif = function(f){
        return L.isFunction(f) ? f : function(){
        };
    };
    NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
        f = NS.lif(f);
        f(p1, p2, p3, p4, p5, p6, p7);
    };
    NS.Item = SysNS.Item;
    NS.ItemList = SysNS.ItemList;

    NS.isURating = !!Brick.mod.urating.VotingWidget;

    var WS = "#app={C#MODNAMEURI}/wspace/ws/";

    var urlJoinArgs = function(url, args){
        for (var i = 0; i < args.length; i++){
            url += args[i] + "/";
        }
        return url;
    };

    NS.navigator = {
        'home': function(){
            return WS;
        },
        'topic': {
            'list': function(){
                return urlJoinArgs(WS + 'topic/TopicHomeListWidget/', arguments);
            },
            'view': function(topicid){
                return WS + 'topic/TopicViewWidget/' + topicid + '/';
            },
            'edit': function(topicid){
                return NS.navigator.write.topic(topicid);
            }
        },
        'tag': {
            'view': function(tag){
                return WS + 'tag/TagViewWidget/' + tag + '/';
            }
        },
        'category': {
            'list': function(){
                return WS + 'category/CategoryListWidget/';
            },
            'view': function(catid){
                return WS + 'category/CategoryViewWidget/' + catid + '/';
            },
            'edit': function(catid){
                return NS.navigator.write.category(catid);
            }
        },
        'author': {
            'list': function(){
                return WS + 'author/AuthorListWidget/';
            },
            'view': function(authorid){
                return WS + 'author/AuthorViewWidget/' + authorid + '/';
            }
        },
        'write': {
            'view': function(){
                return WS + 'write/WriteWidget/';
            },
            'topic': function(id){
                id = id || 0;
                return WS + 'write/WriteWidget/topic/' + (id > 0 ? id + "/" : "");
            },
            'category': function(id){
                id = id || 0;
                return WS + 'write/WriteWidget/category/' + (id > 0 ? id + "/" : "");
            },
            'draftlist': function(){
                return WS + 'write/WriteWidget/draftlist/';
            }
        },
        'about': function(){
            return WS + 'about/AboutWidget/';
        },
        'go': function(url){
            Brick.Page.reload(url);
        }
    };

    var Tag = function(d){
        d = L.merge({
            'id': 0,
            'tl': '',
            'nm': '',
            'cnt': 0
        }, d || {});
        Tag.superclass.constructor.call(this, d);
    };
    YAHOO.extend(Tag, SysNS.Item, {
        update: function(d){
            this.title = d['tl'];
            this.name = d['nm'];
            this.topicCount = d['cnt'];
        },
        toAJAX: function(){
            return {
                'tl': this.title
            };
        },
        url: function(){
            return NS.navigator.tag.view(this.title);
        }
    });
    NS.Tag = Tag;

    var TagList = function(d){
        TagList.superclass.constructor.call(this, d, Tag);
    };
    YAHOO.extend(TagList, SysNS.ItemList, {
        toString: function(){
            var a = [];
            this.foreach(function(tag){
                a[a.length] = tag.title
            });
            return a.join(", ");
        }
    });
    TagList.stringToAJAX = function(s){
        var ret = [];
        if (!L.isString(s)){
            return ret;
        }
        var a = s.replace(/\  /g, ' ').split(',');
        for (var i = 0; i < a.length; i++){
            ret[ret.length] = a[i];
        }
        return ret;
    };
    NS.TagList = TagList;

    // Информация о топике
    var TopicInfo = function(d){
        d = L.merge({
            'catid': 0,
            'tl': '',
            'dl': 0,
            'dft': 0,
            'idx': 0,
            'aidx': 0,
            'cmt': 0,
            'bdlen': 0,
            'intro': '',
            'tags': [],

            'rtg': null,	// рейтинг
            'vcnt': 0,	// кол-во голосов
            'vmy': null	// мой голос

        }, d || {});

        var u = Brick.env.user;
        d['user'] = L.merge({
            'id': u.id,
            'avt': '',
            'fnm': u.firstname,
            'lnm': u.lastname,
            'unm': u.name
        }, d['user'] || {});

        TopicInfo.superclass.constructor.call(this, d);
    };
    YAHOO.extend(TopicInfo, SysNS.Item, {
        init: function(d){
            this.type = 'info';

            this.tagList = new TagList();
            this.user = null;

            TopicInfo.superclass.init.call(this, d);
        },
        update: function(d){
            this.title = d['tl'];				// заголовок

            if (!L.isNull(d['user']) && this.id > 0){
                UP.viewer.users.update([d['user']]);
            }

            // дата публикации
            this.date = d['dl'] == 0 ? null : new Date(d['dl'] * 1000);
            this.isDraft = d['dft'] > 0;
            this.isIndex = d['idx'];
            this.isAutoIndex = d['aidx'];

            this.intro = d['intro'];
            this.commentCount = d['cmt'] * 1;
            this.contentid = d['ctid'] * 1;

            this.tagList.update(d['tags']);

            this.isBody = d['bdlen'] > 0;

            this.user = UP.viewer.users.get(d['user'].id);

            this.catid = d['catid'] * 1;

            this.rating = d['rtg'];
            this.voteCount = d['vcnt'] * 1;
            this.voteMy = d['vmy'];
        },
        url: function(){
            return NS.navigator.topic.view(this.id);
        },
        surl: function(){

            return '/blog/' + this.category().name + '/' + this.id + '/';
        },
        category: function(){
            var catid = this.catid;
            if (catid == 0){ // персональный блог
                return new CategoryPerson(this.user.id);
            } else {
                return NS.manager.categoryList.get(catid);
            }
        }
    });
    NS.TopicInfo = TopicInfo;

    var Topic = function(d){
        d = L.merge({
            'bd': ''
        }, d || {});

        Topic.superclass.constructor.call(this, d);
    };
    YAHOO.extend(Topic, TopicInfo, {
        init: function(d){
            this.type = 'full';
            Topic.superclass.init.call(this, d);
        },
        update: function(d){
            Topic.superclass.update.call(this, d);
            this.body = d['bd'];
        }
    });
    NS.Topic = Topic;

    var TopicList = function(d){

        this.limit = 10;	// лимит загрузки за раз
        this.total = 0;		// всего записей на сервере
        this.totalNew = 0;	// новых записей

        TopicList.superclass.constructor.call(this, d, TopicInfo);
    };
    YAHOO.extend(TopicList, SysNS.ItemList, {});
    NS.TopicList = TopicList;

    var Category = function(d){
        d = L.merge({
            'tl': '', // заголовок
            'nm': '', // имя (URL)
            'dsc': '',// описание
            'rep': 0, // кол-во репутации для нового топика
            'prv': 0, // приватный
            'tcnt': 0,// кол-во топиков
            'mcnt': 0,// кол-во подписчиков

            'rtg': 0, // рейтинг
            'vcnt': 0,// кол-во голосов
            'vmy': null,// мой голос

            'adm': 0, // текущий пользователь админ?
            'mdr': 0, // текущий пользователь модератор?
            'mbr': 0  // текущий пользователь участник?
        }, d || {});
        Category.superclass.constructor.call(this, d);
    };
    YAHOO.extend(Category, SysNS.Item, {
        update: function(d){
            this.title = d['tl'];
            this.name = d['nm'];
            this.descript = d['dsc'];
            this.reputation = d['rep'] * 1;
            this.topicCount = d['tcnt'] * 1;
            this.memberCount = d['mcnt'] * 1;
            this.isPrivate = d['prv'] > 0;

            this.rating = d['rtg'] * 1;
            this.voteCount = d['vcnt'] * 1;
            this.voteMy = d['vmy'];

            this.isAdmin = d['adm'] > 0;
            this.isModer = d['mdr'] > 0;
            this.isMember = d['mbr'] > 0;
        },
        url: function(){
            return NS.navigator.category.view(this.id);
        }
    });
    NS.Category = Category;

    var CategoryPerson = function(userid){ // персональный блог
        var user = UP.viewer.users.get(userid);
        this.user = user;
        var d = L.merge({
            'id': 0,
            'tl': LNG.get('cat.my').replace('{v#unm}', user.userName),
            'nm': user.userName
        }, d || {});
        CategoryPerson.superclass.constructor.call(this, d);
    };
    YAHOO.extend(CategoryPerson, Category, {
        update: function(d){
            this.title = d['tl'];
            this.name = d['nm'];
        },
        url: function(){
            return NS.navigator.author.view(this.user.id);
        }
    });
    NS.CategoryPerson = CategoryPerson;


    var CategoryList = function(d){
        CategoryList.superclass.constructor.call(this, d, Category);
    };
    YAHOO.extend(CategoryList, SysNS.ItemList, {
        getByName: function(catName){
            var category = null;

            this.foreach(function(cat){
                if (cat.name == catName){
                    category = cat;
                    return true;
                }
            });

            return category;
        }
    });
    NS.CategoryList = CategoryList;

    var Author = function(d){
        d = L.merge({
            'avt': '',
            'fnm': u.firstname,
            'lnm': u.lastname,
            'unm': u.name,

            'tcnt': 0,// кол-во топиков

            'rtg': null	// рейтинг

        }, d || {});

        Author.superclass.constructor.call(this, d);
    };
    YAHOO.extend(Author, SysNS.Item, {
        update: function(d){

            if (this.id > 0){
                UP.viewer.users.update([d]);
            }
            this.user = UP.viewer.users.get(this.id);

            this.topicCount = d['tcnt'] * 1;
            this.rating = d['rtg'];
        }
    });
    NS.Author = Author;

    var AuthorList = function(d){
        AuthorList.superclass.constructor.call(this, d, Author);
    };
    YAHOO.extend(AuthorList, SysNS.ItemList, {});
    NS.AuthorList = AuthorList;


    var CommentLive = function(d){
        d = L.merge({
            'dl': '',
            'bd': ''
        }, d || {});
        CommentLive.superclass.constructor.call(this, d);
    };
    YAHOO.extend(CommentLive, SysNS.Item, {
        init: function(d){
            this.topic = null;
            this.user = null;

            CommentLive.superclass.init.call(this, d);
        },
        update: function(d){
            this.date = d['dl'] == 0 ? null : new Date(d['dl'] * 1000);
            this.body = d['bd'];

            if (L.isNull(this.topic)){
                this.topic = new TopicInfo(d['topic']);
            } else {
                this.topic.update(d['topic']);
            }
            UP.viewer.users.update([d['user']]);
            this.user = UP.viewer.users.get(d['user'].id);
        }
    });
    NS.CommentLive = CommentLive;

    var CommentLiveList = function(d){
        CommentLiveList.superclass.constructor.call(this, d, CommentLive);
    };
    YAHOO.extend(CommentLiveList, SysNS.ItemList, {});
    NS.CommentLiveList = CommentLiveList;


    var CategoryUserRoleManager = function(){
        this.init();
    };
    CategoryUserRoleManager.prototype = {
        init: function(){
        },
        get: function(cat){
            if (L.isObject(cat)){
                return cat;
            }
            return NS.manager.categoryList.get(cat * 1);
        },
        isAdmin: function(cat){ // Админ категории
            if (L.isNull(cat = this.get(cat))){
                return false;
            }
            if (R['isAdmin']){
                return true;
            }

            return cat.isAdmin;
        },
        isMember: function(cat){ // Участник категории
            if (L.isNull(cat = this.get(cat))){
                return false;
            }

            return this.isAdmin(cat) || cat.isModer || cat.isMember;
        },
        isTopicCreate: function(cat){ // Доступ создание нового топика?
            if (L.isNull(cat = this.get(cat))){
                return false;
            }
            return R['isWrite'] && this.isMember();
        }
    };
    NS.CategoryUserRoleManager = CategoryUserRoleManager;

    var TopicUserRoleManager = function(){
        this.init();
    };
    TopicUserRoleManager.prototype = {
        init: function(){
        },
        isManager: function(topic){
            return this.isEdit(topic);
        },
        isEdit: function(topic){
            return topic.user.id == UID || R['isAdmin'];
        }
    };
    NS.TopicUserRoleManager = TopicUserRoleManager;

    var Manager = function(callback){
        this.init(callback);
    };
    Manager.prototype = {
        init: function(callback){
            NS.manager = this;

            this.users = Brick.mod.uprofile.viewer.users;
            this.categoryList = new NS.CategoryList();

            var __self = this;
            R.load(function(){

                R.category = new NS.CategoryUserRoleManager();
                R.topic = new NS.TopicUserRoleManager();

                __self.categoryListLoad(function(){
                    NS.life(callback, __self);
                });
            });
        },
        ajax: function(data, callback){
            data = data || {};

            Brick.ajax('{C#MODNAME}', {
                'data': data,
                'event': function(request){
                    NS.life(callback, request.data);
                }
            });
        },
        _updateCategoryList: function(d){
            if (L.isNull(d) || !L.isArray(d['categories'])){
                return;
            }
            this.categoryList.clear(d['categories']);
            this.categoryList.update(d['categories']);
        },
        categoryListLoad: function(callback){
            var __self = this;
            this.ajax({'do': 'categorylist'}, function(d){
                __self._updateCategoryList(d);
                NS.life(callback, __self.categoryList);
            });
        },
        topicListLoad: function(cfg, callback){
            cfg = L.merge({
                'list': null,
                'catid': 0,
                'page': 1,
                'limit': 10,
                'filter': ''
            }, cfg || {});

            this.ajax({
                'do': 'topiclist',
                'catid': cfg['catid'],
                'page': cfg['page'],
                'limit': cfg['limit'],
                'filter': cfg['filter']
            }, function(d){
                var rlist = null;

                if (!L.isNull(d) && d['topics'] && L.isArray(d['topics']['list'])){
                    rlist = new NS.TopicList(d['topics']['list']);
                    rlist.total = d['topics']['total'] * 1;
                    rlist.totalNew = d['topics']['totalNew'] * 1;

                    var list = cfg['list'];
                    if (L.isObject(list)){
                        rlist.foreach(function(item){
                            list.add(item);
                        });
                        rlist = list;
                    }
                }

                NS.life(callback, rlist);
            });
        },
        topicLoad: function(topicid, callback){
            this.ajax({
                'do': 'topic',
                'topicid': topicid
            }, function(d){
                var topic = null;
                if (!L.isNull(d) && !L.isNull(d['topic'])){
                    topic = new NS.Topic(d['topic']);
                }

                NS.life(callback, topic);
            });
        },
        topicPreview: function(sd, callback){
            this.ajax({
                'do': 'topicpreview',
                'savedata': sd
            }, function(d){
                var topic = null;
                if (!L.isNull(d) && !L.isNull(d['topic'])){
                    topic = new NS.Topic(d['topic']);
                }
                NS.life(callback, topic);
            });
        },
        topicSave: function(sd, callback){
            this.ajax({
                'do': 'topicsave',
                'savedata': sd
            }, function(d){
                var topicid = null, error = null;

                if (!L.isNull(d) && !L.isNull(d['error'])){
                    topicid = d['topicid'];
                    error = d['error'];
                }

                NS.life(callback, topicid, error);
            });
        },
        categorySave: function(sd, callback){
            var __self = this;
            sd['do'] = 'categorysave';
            this.ajax(sd, function(d){
                var catid = null, error = null;

                if (!L.isNull(d) && !L.isNull(d['error'])){
                    catid = d['catid'];
                    error = d['error'];
                    __self._updateCategoryList(d);
                }

                NS.life(callback, catid, error);
            });
        },
        categoryJoin: function(catid, callback){
            var cat = this.categoryList.get(catid);
            this.ajax({
                'do': 'categoryjoin',
                'catid': cat.id
            }, function(d){
                if (!L.isNull(d) && !L.isNull(d['category'])){
                    cat.update(d['category']);
                }
                NS.life(callback);
            });
        },
        categoryRemove: function(catid, callback){
            var __self = this;
            this.ajax({
                'do': 'categoryremove',
                'catid': catid
            }, function(d){
                __self._updateCategoryList(d);
                NS.life(callback);
            });
        },
        authorListLoad: function(callback, cfg){
            cfg = L.merge({
                'page': 1,
                'limit': 15,
                'filter': ''
            }, cfg || {});

            cfg['do'] = 'authorlist';
            this.ajax(cfg, function(d){
                var list = null;

                if (!L.isNull(d) && L.isArray(d['authors'])){
                    list = new NS.AuthorList(d['authors']);
                }

                NS.life(callback, list);
            });
        },
        authorLoad: function(authorid, callback){
            this.ajax({
                'do': 'author',
                'authorid': authorid
            }, function(d){
                var author = null;

                if (!L.isNull(d) && !L.isNull(d['author'])){
                    author = new NS.Author(d['author']);
                }

                NS.life(callback, author);
            });
        },
        commentLiveListLoad: function(cfg, callback){
            cfg = L.merge({
                'limit': 5
            }, cfg || {});

            this.ajax({
                'do': 'commentlivelist',
                'limit': cfg['limit']
            }, function(d){
                var list = null;

                if (!L.isNull(d) && !L.isNull(d['comments'])){
                    list = new NS.CommentLiveList(d['comments']);
                }

                NS.life(callback, list);
            });
        },
        tagListLoad: function(cfg, callback){
            cfg = L.merge({
                'limit': 25
            }, cfg || {});

            this.ajax({
                'do': 'taglist',
                'limit': cfg['limit']
            }, function(d){
                var list = null;

                if (!L.isNull(d) && !L.isNull(d['tags'])){
                    list = new NS.TagList(d['tags']);
                }

                NS.life(callback, list);
            });
        }
    };
    NS.manager = null;

    NS.initManager = function(callback){
        if (L.isNull(NS.manager)){
            NS.manager = new Manager(callback);
        } else {
            NS.life(callback, NS.manager);
        }
    };

};