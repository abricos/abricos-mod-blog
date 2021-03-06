var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    var COMPONENT = this,
        SYS = Brick.mod.sys;

    SYS.Application.build(COMPONENT, {}, {
        initializer: function(){
            NS.roles.load(function(){
                this.initCallbackFire();
            }, this);
        }
    }, [], {
        APPS: {
            uprofile: {},
            urating: {},
            comment: {},
            notify: {}
        },
        ATTRS: {
            isLoadAppStructure: {value: true},
            Config: {value: NS.Config},
        },
        REQS: {
            config: {
                attribute: true,
                type: 'model:Config'
            },
            configSave: {
                args: ['data']
            }
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
            config: function(){
                return this.getURL('ws') + 'config/ConfigWidget/';
            },
            topic: {
                list: function(){
                    return this.getURL('ws') + 'topicList/TopicHomeListWidget/';
                },
                listNew: function(){
                    return this.getURL('topic.list') + 'new/';
                },
                listPub: function(){
                    return this.getURL('topic.list') + 'pub/';
                },
                listPubNew: function(){
                    return this.getURL('topic.listPub') + 'new/';
                },
                listPers: function(){
                    return this.getURL('topic.list') + 'pers/';
                },
                listPersNew: function(){
                    return this.getURL('topic.listPers') + 'new/';
                },
                view: function(topicid){
                    return this.getURL('ws') + 'topic/TopicViewWidget/' + (topicid | 0) + '/';
                },
                create: function(catid){
                    return this.getURL('ws') + 'topicEditor/TopicEditorWidget/0/' + (catid | 0) + '/';
                },
                edit: function(topicid){
                    return this.getURL('ws') + 'topicEditor/TopicEditorWidget/' + (topicid | 0) + '/';
                }
            },
            tag: {
                view: function(tag){
                    return this.getURL('ws') + 'tag/TagViewWidget/' + tag + '/';
                }
            },
            category: {
                list: function(){
                    return this.getURL('ws') + 'category/CategoryListWidget/';
                },
                view: function(catid){
                    return this.getURL('ws') + 'category/CategoryViewWidget/' + catid + '/';
                },
                edit: function(catid){
                    return this.getURL('ws') + 'categoryEditor/CategoryEditorWidget/' + (catid | 0) + '/';
                }
            },
            author: {
                list: function(){
                    return this.getURL('ws') + 'author/AuthorListWidget/';
                },
                view: function(authorid){
                    return this.getURL('ws') + 'author/AuthorViewWidget/' + authorid + '/';
                }
            },
            write: {
                view: function(){
                    return this.getURL('ws') + 'write/WriteWidget/';
                },
                topic: function(id){
                    id = id || 0;
                    return this.getURL('write.view') + 'topic/' + (id > 0 ? id + "/" : "");
                },
                category: function(id){
                    return this.getURL('write.view') + 'category/' + (id | 0) + "/";
                },
                draftlist: function(){
                    return this.getURL('write.view') + 'draftlist/';
                }
            },
            about: function(){
                return this.getURL('ws') + 'about/AboutWidget/';
            },
        }
    });

    // TODO: remove old functions
    /* * * * * * * * * * * * * * * * Old functions * * * * * * * * * * * * * * */

    var L = YAHOO.lang,
        R = NS.roles;

    NS.lif = function(f){
        return Y.Lang.isFunction(f) ? f : function(){
        };
    };
    NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
        f = NS.lif(f);
        f(p1, p2, p3, p4, p5, p6, p7);
    };
    NS.Item = SYS.Item;
    NS.ItemList = SYS.ItemList;

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

    var Manager = function(callback){
        this.init(callback);
    };
    Manager.prototype = {
        init: function(callback){
            NS.manager = this;

            this.categoryList = new NS.CategoryList();

            var __self = this;

            NS.initApp({
                initCallback: function(err, appInstance){
                    R.category = new NS.CategoryUserRoleManager();
                    R.topic = new NS.TopicUserRoleManager();

                    __self.categoryListLoad(function(){
                        NS.life(callback, __self);
                    });
                }
            });
        },
        ajax: function(data, callback){
            data = data || {};

            Brick.ajax('{C#MODNAME}', {
                data: data,
                event: function(request){
                    NS.life(callback, request.data);
                }
            });
        },
        _updateCategoryList: function(d){
            if (!d || !Y.Lang.isArray(d['categories'])){
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

                if (d && d.topics && Y.Lang.isArray(d.topics.list)){
                    var userids = [];
                    for (var i = 0; i < d.topics.list.length; i++){
                        userids[userids.length] = d.topics.list[i].userid;
                    }
                    NS.appInstance.getApp('uprofile').userListByIds(userids, function(err, result){
                        rlist = new NS.TopicList(d.topics.list);
                        rlist.total = d.topics['total'] * 1;
                        rlist.totalNew = d.topics['totalNew'] * 1;

                        var list = cfg['list'];
                        if (L.isObject(list)){
                            rlist.foreach(function(item){
                                list.add(item);
                            });
                            rlist = list;
                        }

                        NS.life(callback, rlist);
                    });
                } else {
                    NS.life(callback, rlist);
                }
            });
        },
        topicLoad: function(topicid, callback){
            this.ajax({
                'do': 'topic',
                topicid: topicid
            }, function(d){
                var topic = null;
                if (d && d.topic){
                    NS.appInstance.getApp('uprofile').userListByIds([d.topic.userid], function(err, result){
                        topic = new NS.Topic(d.topic);
                        NS.life(callback, topic);
                    });
                } else {
                    NS.life(callback, topic);
                }
            });
        },
        topicPreview: function(sd, callback){
            this.ajax({
                'do': 'topicpreview',
                'savedata': sd
            }, function(d){
                var topic = null;
                if (d && !Y.Lang.isNull(d.topic)){
                    topic = new NS.Topic(d.topic);
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

                if (d && !Y.Lang.isNull(d['error'])){
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

                if (d && !Y.Lang.isNull(d['error'])){
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
                if (d && !Y.Lang.isNull(d['category'])){
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

                if (d && Y.Lang.isArray(d['authors'])){
                    var userids = [];
                    for (var i = 0; i < d.authors.length; i++){
                        userids[userids.length] = d.authors[i].id;
                    }
                    NS.appInstance.getApp('uprofile').userListByIds(userids, function(err, result){
                        list = new NS.AuthorList(d['authors']);
                        NS.life(callback, list);
                    });
                } else {
                    NS.life(callback, list);
                }
            });
        },
        authorLoad: function(authorid, callback){
            this.ajax({
                'do': 'author',
                'authorid': authorid
            }, function(d){
                var author = null;

                if (d && !Y.Lang.isNull(d['author'])){
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

                if (d && !Y.Lang.isNull(d['comments'])){
                    var userids = [];
                    for (var i = 0; i < d.comments.length; i++){
                        userids[userids.length] = d.comments[i].user.id;
                    }

                    NS.appInstance.getApp('uprofile').userListByIds(userids, function(err, result){
                        list = new NS.CommentLiveList(d['comments']);
                        NS.life(callback, list);
                    });
                } else {
                    NS.life(callback, list);
                }
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

                if (d && !Y.Lang.isNull(d['tags'])){
                    list = new NS.TagList(d['tags']);
                }

                NS.life(callback, list);
            });
        }
    };
    NS.manager = null;

    NS.initManager = function(callback){
        if (!NS.manager){
            NS.manager = new Manager(callback);
        } else {
            NS.life(callback, NS.manager);
        }
    };
};