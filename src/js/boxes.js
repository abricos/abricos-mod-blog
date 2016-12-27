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

    NS.TopicListBoxWidget = Y.Base.create('TopicListBoxWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            if (this.get('topicList')){
                return this.renderList();
            }
            this.set('waiting', true);
            appInstance.topicList({limit: 5}, function(err, result){
                this.set('waiting', false);
                if (err){
                    return;
                }
                this.set('topicList', result.topicList);
                this.renderList();
            }, this);
        },
        renderList: function(){
            this.set('waiting', false);
            var list = this.get('topicList');
            if (!list){
                return;
            }
            var tp = this.template,
                lst = "";

            list.each(function(topic){
                var blog = topic.get('blog');

                lst += tp.replace('toprow', {
                    id: topic.get('id'),
                    blogTitle: blog.get('title'),
                    blogid: blog.get('id'),
                    title: topic.get('title'),
                });

            }, this);

            tp.setHTML('topicList', tp.replace('toplist', {
                rows: lst
            }));

            this.appURLUpdate();
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'topics,toplist,toprow'},
            topicList: {value: null}
        },
    });

    NS.CommentLiveBoxWidget = Y.Base.create('CommentLiveBoxWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            if (this.get('commentLiveList')){
                return this.renderList();
            }
            this.set('waiting', true);
            appInstance.commentLiveList({limit: 10}, function(err, result){
                this.set('waiting', false);
                if (err){
                    return;
                }
                this.set('commentLiveList', result.commentLiveList);
                this.renderList();
            }, this);
        },
        renderList: function(){
            this.set('waiting', false);
            var list = this.get('commentLiveList');
            if (!list){
                return;
            }
            var tp = this.template,
                lst = "";

            list.each(function(topic){
                var blog = topic.get('blog'),
                    user = topic.get('user');

                lst += tp.replace('cmtrow', {
                    topicid: topic.get('id'),
                    blogid: blog.get('id'),
                    blogTitle: blog.get('title'),
                    uid: user.get('id'),
                    login: user.get('username'),
                    unm: user.get('viewName'),
                    topicTitle: topic.get('title'),
                    comments: topic.get('commentStatistic').get('count'),
                });
            }, this);

            tp.setHTML('list', tp.replace('cmtlist', {
                rows: lst
            }));
            this.appURLUpdate();
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'comments,cmtlist,cmtrow'},
            commentLiveList: {value: null}
        },
    });

    NS.TagListBoxWidget = Y.Base.create('TagListBoxWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            if (this.get('tagList')){
                return this.renderList();
            }
            this.set('waiting', true);
            appInstance.tagList({limit: 35}, function(err, result){
                this.set('waiting', false);
                if (err){
                    return;
                }
                this.set('tagList', result.tagList);
                this.renderList();
            }, this);
        },
        renderList: function(list){
            this.set('waiting', false);
            var list = this.get('tagList');
            if (!list){
                return;
            }

            var arr = [],
                min = 999999,
                max = 0;

            list.each(function(tag){
                arr[arr.length] = tag;
                min = Math.min(min, tag.get('topicCount'));
                max = Math.max(max, tag.get('topicCount'));
            });

            var fmin = 0, fmax = 10;
            if (min == max){
                max++;
            }
            var g1 = Math.log(min + 1),
                g2 = Math.log(max + 1);

            var tp = this.template,
                lst = "",
                tag, cnt, n1, n2, v;

            for (var i = 0; i < arr.length; i++){
                tag = arr[i];
                cnt = tag.get('topicCount');
                n1 = (fmin + Math.log(cnt + 1) - g1) * fmax;
                n2 = g2 - g1;
                v = Math.ceil(n1 / n2);

                lst += tp.replace('tagrow', {
                    id: tag.get('id'),
                    slug: encodeURIComponent(tag.get('title')),
                    title: tag.get('title'),
                    sz: v
                });
                lst += ' ';
            }
            tp.setHTML('list', tp.replace('taglist', {
                rows: lst
            }));
            this.appURLUpdate();
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'tags,taglist,tagrow'},
            tagList: {value: null}
        },
    });

    NS.CategoryListBoxWidget = Y.Base.create('CategoryListBoxWidget', SYS.AppWidget, [], {
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
                limit = Math.min(list.count(), 10);

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