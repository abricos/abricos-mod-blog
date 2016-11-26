var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var OldManagerWidgetExt = function(){
    };
    OldManagerWidgetExt.prototype = {
        onInitAppWidget: function(err, appInstance){
            var instance = this;
            NS.initManager(function(){
                instance.onInitOldManager.call(instance, NS.manager);
            });
        },
        onInitOldManager: function(manager){
        }
    };

    NS.TopicListBoxWidget = Y.Base.create('TopicListBoxWidget', SYS.AppWidget, [
        OldManagerWidgetExt
    ], {
        onInitOldManager: function(manager){
            this.set('waiting', true);
            var instance = this;
            manager.topicListLoad({limit: 5}, function(list){
                instance.renderList.call(instance, list);
            });
        },
        renderList: function(list){
            this.set('waiting', false);
            if (!list){
                return;
            }
            var tp = this.template,
                lst = "";

            list.foreach(function(topic){
                var cat = topic.category();
                lst += tp.replace('toprow', {
                    cattl: cat.title,
                    urlcat: cat.url(),
                    toptl: topic.title,
                    urltop: topic.url()
                });
            });
            tp.setHTML('list', tp.replace('toplist', {
                rows: lst
            }));
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'topics,toplist,toprow'},
        },
    });

    NS.CommentLiveBoxWidget = Y.Base.create('CommentLiveBoxWidget', SYS.AppWidget, [
        OldManagerWidgetExt
    ], {
        onInitOldManager: function(manager){
            this.set('waiting', true);
            var instance = this;
            manager.commentLiveListLoad({limit: 10}, function(list){
                instance.renderList.call(instance, list);
            });
        },
        renderList: function(list){
            this.set('waiting', false);
            if (!list){
                return;
            }
            var tp = this.template,
                lst = "";

            list.foreach(function(cmt){
                var cat = cmt.topic.category(),
                    user = cmt.user;

                lst += tp.replace('cmtrow', {
                    uid: user.get('id'),
                    login: user.get('username'),
                    unm: user.get('viewName'),
                    cattl: cat.title,
                    urlcat: cat.url(),
                    toptl: cmt.topic.title,
                    urlcmt: cmt.topic.url(),
                    cmtcnt: cmt.topic.commentStatistic.get('count'),
                });
            });
            tp.setHTML('list', tp.replace('cmtlist', {
                rows: lst
            }));
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'comments,cmtlist,cmtrow'},
        },
    });

    NS.TagListBoxWidget = Y.Base.create('TagListBoxWidget', SYS.AppWidget, [
        OldManagerWidgetExt
    ], {
        onInitOldManager: function(manager){
            this.set('waiting', true);
            var instance = this;
            manager.tagListLoad({limit: 35}, function(list){
                instance.renderList.call(instance, list);
            });
        },
        renderList: function(list){
            this.set('waiting', false);
            if (!list){
                return;
            }

            var arr = [],
                min = 999999,
                max = 0;

            list.foreach(function(tag){
                arr[arr.length] = tag;
                min = Math.min(min, tag.topicCount);
                max = Math.max(max, tag.topicCount);
            });

            arr = arr.sort(function(t1, t2){
                if (t1.title < t2.title){
                    return -1;
                }
                if (t1.title > t2.title){
                    return 1;
                }
                return 0;
            });

            var fmin = 0, fmax = 10;
            if (min == max){
                max++;
            }
            var g1 = Math.log(min + 1),
                g2 = Math.log(max + 1);

            var lst = "",
                tp = this.template,
                tag, cnt, n1, n2, v;

            for (var i = 0; i < arr.length; i++){
                tag = arr[i];
                cnt = tag.topicCount;
                n1 = (fmin + Math.log(cnt + 1) - g1) * fmax;
                n2 = g2 - g1;
                v = Math.ceil(n1 / n2);

                lst += tp.replace('tagrow', {
                        tagtl: tag.title,
                        urltag: tag.url(),
                        sz: v
                    }) + ' ';
            }
            tp.setHTML('list', tp.replace('taglist', {
                rows: lst
            }));
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'tags,taglist,tagrow'},
        },
    });

    NS.CategoryListBoxWidget = Y.Base.create('CategoryListBoxWidget', SYS.AppWidget, [
        OldManagerWidgetExt
    ], {
        onInitOldManager: function(manager){
            this.set('waiting', true);
            this.renderList(manager.categoryList);
        },
        renderList: function(list){
            this.set('waiting', false);
            if (!list){
                return;
            }

            var tp = this.template,
                arr = [],
                lst = "",
                limit = 10;

            list.foreach(function(cat){
                arr[arr.length] = cat;
            });

            arr = arr.sort(function(item1, item2){
                if (item1.topicCount < item2.topicCount){
                    return 1;
                }
                if (item1.topicCount > item2.topicCount){
                    return -1;
                }
                return 0;
            });

            for (var i = 0, cat; i < limit; i++){
                cat = arr[i];
                lst += tp.replace('catrow', {
                    cattl: cat.title,
                    urlcat: cat.url(),
                    topicCount: cat.topicCount
                });
            }

            tp.setHTML('list', tp.replace('catlist', {
                rows: lst
            }));
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'cats,catlist,catrow'},
        },
    });
};