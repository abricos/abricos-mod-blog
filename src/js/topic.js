var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'urating', files: ['vote.js']},
        {name: 'socialist', files: ['line.js']},
        {name: 'comment', files: ['tree.js']},
        {name: '{C#MODNAME}', files: ['widget.js', 'lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var L = YAHOO.lang,
        buildTemplate = this.buildTemplate,
        NSSC = Brick.mod.socialist || {},
        LNG = this.language,
        R = NS.roles;

    var TopicManagerWidget = function(container, topic){
        TopicManagerWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'manbtns'
        }, topic);
    };
    YAHOO.extend(TopicManagerWidget, Brick.mod.widget.Widget, {
        init: function(topic){
            this.topic = topic;
        },
        buildTData: function(topic){
            return {
                'urledit': NS.navigator.topic.edit(topic.id)
            };
        }
    });
    NS.TopicManagerWidget = TopicManagerWidget;

    NS.TopicInfoLineWidget = Y.Base.create('TopicInfoLineWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt
    ], {
        buildTData: function(){
            var topic = this.get('topic'),
                user = topic.user,
                commentStat = topic.commentStatistic;
            return {
                date: !topic.date ? LNG.get('topic.draft') : Brick.dateExt.convert(topic.date),
                uid: user.get('id'),
                avatar: user.get('avatarSrc24'),
                unm: user.get('viewName'),
                cmt: commentStat.get('count')
            };
        },
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                topic = this.get('topic');

            if (topic.voting){
                this.addWidget('voting', new Brick.mod.urating.VotingWidget({
                    boundingBox: this.gel('topicvote'),
                    voting: topic.voting
                }));
                tp.show('topicvote');
            }
            if (NSSC.LineWidget){
                this.addWidget('socialist', new NSSC.LineWidget(this.gel('socialist'), {
                    'url': topic.surl(),
                    'title': topic.title
                }));
                tp.show('socialist');
            }
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'info'},
            topic: {value: null}
        },
    });

    NS.TagListWidget = Y.Base.create('TagListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                alst = [];

            this.get('tagList').foreach(function(tag){
                alst[alst.length] = tp.replace('tagrow', {
                    tl: tag.title,
                    url: tag.url()
                });
            });

            tp.setHTML('list', alst.join(tp.replace('tagrowcm')));
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'taglist,tagrow,tagrowcm'},
            tagList: {value: null}
        },
    });

    NS.TopicRowWidget = Y.Base.create('TopicRowWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt
    ], {
        buildTData: function(){
            var topic = this.get('topic'),
                cat = topic.category();
            return {
                id: topic.id,
                catid: topic.catid,
                title: topic.title,
                catTitle: cat ? cat.title : ''
            };
        },
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                topic = this.get('topic');

            this.addWidget('tagList', new NS.TagListWidget({
                srcNode: tp.one('taglist'),
                tagList: topic.tagList
            }));
            this.addWidget('info', new NS.TopicInfoLineWidget({
                srcNode: tp.one('info'),
                topic: topic
            }));

            tp.setHTML({
                intro: topic.intro
            });

            if (R.topic.isManager(topic)){
                this.manWidget = new NS.TopicManagerWidget(tp.gel('man'), topic);
            }
            tp.toggleView(topic.isBody, 'readmore');
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'row'},
            topic: {value: null}
        },
    });

    NS.TopicViewWidget = Y.Base.create('topicViewWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var instance = this,
                topicid = this.get('topicid');

            NS.initManager(function(){
                NS.manager.topicLoad(topicid, function(topic){
                    instance._renderTopic(topic);
                });
            });
        },
        destructor: function(){
            if (this.viewWidget){
                this.viewWidget.destroy();
            }
        },
        _renderTopic: function(topic){
            this.set('waiting', false);
            var tp = this.template;

            tp.toggleView(!topic, 'nullitem', 'view');

            var widget = this.viewWidget = new NS.TopicRowWidget(tp.gel('view'), topic);
            widget.elSetHTML({
                body: topic.body
            });
            widget.elHide('readmore');

            this._commentsWidget = new Brick.mod.comment.CommentTreeWidget({
                srcNode: widget.gel('comments'),
                commentOwner: {
                    module: 'blog',
                    type: 'topic',
                    ownerid: topic.id
                },
                readOnly: !NS.roles.isWrite
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'topicview'},
            topicid: {},
            topic: {}
        },
        parseURLParam: function(args){
            return {
                topicid: args[0] | 0
            };
        }
    });


    // TODO: remove old functions
    return; /////////////////////////////////////////////////

    var TopicHomeListWidget = function(container, f1, f2){
        if (f1 == 'new'){
            f1 = '';
            f2 = 'new';
        }
        var cfg = {'f1': f1 || '', 'f2': f2 || ''};

        TopicHomeListWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': ''
        }, cfg);
    };
    YAHOO.extend(TopicHomeListWidget, Brick.mod.widget.Widget, {
        init: function(cfg){
            this.cfg = cfg;

            // использует wspace.js
            this.wsMenuItem = cfg['f1'] == '' ? 'all' : cfg['f1'];
        },
    });
    NS.TopicHomeListWidget = TopicHomeListWidget;

};