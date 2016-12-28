var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['item.js', 'date.js']},
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        SYS = Brick.mod.sys,
        UID = Brick.env.user.id | 0;

    //////////////////// old functions /////////////////////

    var L = YAHOO.lang,
        R = NS.roles;

    var LNG = this.language;

    var Tag = function(d){
        d = L.merge({
            'id': 0,
            'tl': '',
            'nm': '',
            'cnt': 0
        }, d || {});
        Tag.superclass.constructor.call(this, d);
    };
    YAHOO.extend(Tag, SYS.Item, {
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
    YAHOO.extend(TagList, SYS.ItemList, {
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
    YAHOO.extend(TopicInfo, SYS.Item, {
        init: function(d){
            this.type = 'info';

            this.tagList = new TagList();
            this.user = null;

            TopicInfo.superclass.init.call(this, d);
        },
        update: function(d){
            this.title = d['tl'];				// заголовок

            var userList = NS.appInstance.getApp('uprofile').get('userList');
            this.user = userList.getById(d.userid);

            // дата публикации
            this.date = d['dl'] == 0 ? null : new Date(d['dl'] * 1000);
            this.isDraft = d['dft'] > 0;
            this.isIndex = d['idx'];
            this.isAutoIndex = d['aidx'];

            this.intro = d['intro'];

            this.tagList.update(d['tags']);

            this.isBody = d['bdlen'] > 0;

            this.catid = d['catid'] * 1;

            this.voting = null;
            if (d.voting){
                var uratingApp = NS.appInstance.getApp('urating'),
                    Voting = uratingApp.get('Voting');

                this.voting = new Voting(Y.merge({
                    appInstance: uratingApp
                }, d.voting || {}));
            }

            var commentApp = NS.appInstance.getApp('comment'),
                CommentStatistic = commentApp.get('Statistic');

            this.commentStatistic = new CommentStatistic(Y.merge({
                appInstance: commentApp
            }, d.commentStatistic || {}));
        },
        isEdit: function(){
            return this.user.id == UID || NS.roles.isAdmin;
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
    YAHOO.extend(TopicList, SYS.ItemList, {});
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

            'adm': 0, // текущий пользователь админ?
            'mdr': 0, // текущий пользователь модератор?
            'mbr': 0  // текущий пользователь участник?
        }, d || {});
        Category.superclass.constructor.call(this, d);
    };
    YAHOO.extend(Category, SYS.Item, {
        update: function(d){
            this.title = d['tl'];
            this.name = d['nm'];
            this.descript = d['dsc'];
            this.reputation = d['rep'] * 1;
            this.topicCount = d['tcnt'] * 1;
            this.memberCount = d['mcnt'] * 1;
            this.isPrivate = d['prv'] > 0;

            this.voting = null;
            if (d.voting){
                var uratingApp = NS.appInstance.getApp('urating'),
                    Voting = uratingApp.get('Voting');

                this.voting = new Voting(Y.merge({
                    appInstance: uratingApp
                }, d.voting || {}));
            }

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
        var userList = NS.appInstance.getApp('uprofile').get('userList'),
            user = userList.getById(userid);

        this.user = user;
        var d = L.merge({
            'id': 0,
            'tl': LNG.get('cat.my').replace('{v#unm}', user.get('viewName')),
            'nm': user.get('viewName')
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
    YAHOO.extend(CategoryList, SYS.ItemList, {
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
            avt: '',
            fnm: u.firstname,
            lnm: u.lastname,
            unm: u.name,

            tcnt: 0,// кол-во топиков

            rtg: null	// рейтинг

        }, d || {});

        Author.superclass.constructor.call(this, d);
    };
    YAHOO.extend(Author, SYS.Item, {
        update: function(d){

            if (this.id > 0){
                // UP.viewer.users.update([d]);
            }
            // this.user = UP.viewer.users.get(this.id);
            var userList = NS.appInstance.getApp('uprofile').get('userList');
            this.user = userList.getById(d.id);

            this.topicCount = d['tcnt'] * 1;
            this.rating = d['rtg'];
        }
    });
    NS.Author = Author;

    var AuthorList = function(d){
        AuthorList.superclass.constructor.call(this, d, Author);
    };
    YAHOO.extend(AuthorList, SYS.ItemList, {});
    NS.AuthorList = AuthorList;


    var CommentLive = function(d){
        d = L.merge({
            dl: '',
            bd: ''
        }, d || {});
        CommentLive.superclass.constructor.call(this, d);
    };
    YAHOO.extend(CommentLive, SYS.Item, {
        init: function(d){
            this.topic = null;
            this.user = null;

            CommentLive.superclass.init.call(this, d);
        },
        update: function(d){
            this.date = d['dl'] == 0 ? null : new Date(d['dl'] * 1000);
            this.body = d['bd'];
            this.userid = d['uid'] | 0;

            if (L.isNull(this.topic)){
                this.topic = new TopicInfo(d['topic']);
            } else {
                this.topic.update(d['topic']);
            }
            this.user = NS.appInstance.getApp('uprofile').get('userList').getById(d.user.id);
        }
    });
    NS.CommentLive = CommentLive;

    var CommentLiveList = function(d){
        CommentLiveList.superclass.constructor.call(this, d, CommentLive);
    };
    YAHOO.extend(CommentLiveList, SYS.ItemList, {});
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
            if (!(cat = this.get(cat))){
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

};