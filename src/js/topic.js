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

    var SOCIALIST = Brick.mod.socialist || {},
        LNG = this.language;

    NS.TopicInfoLineWidget = Y.Base.create('TopicInfoLineWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt
    ], {
        buildTData: function(){
            var topic = this.get('topic'),
                user = topic.user,
                commentStat = topic.commentStatistic;
            return {
                date: !topic.date ? LNG.get('topic.draft') : Brick.dateExt.convert(topic.date),
                userid: user.get('id'),
                avatar: user.get('avatarSrc24'),
                userViewName: user.get('viewName'),
                cmt: commentStat.get('count')
            };
        },
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                topic = this.get('topic');

            if (topic.voting){
                this.addWidget('voting', new Brick.mod.urating.VotingWidget({
                    boundingBox: this.gel('voting'),
                    voting: topic.voting
                }));
                tp.show('votingBlock');
            }
            if (SOCIALIST.LineWidget){
                this.addWidget('socialist', new SOCIALIST.LineWidget({
                    srcNode: tp.one('socialist'),
                    itemURL: topic.surl(),
                    itemTitle: topic.title
                }));
                tp.show('socialistBlock');
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
                alst[alst.length] = tp.replace('tagRow', {
                    tl: tag.title,
                    url: tag.url()
                });
            });

            tp.setHTML('tags', alst.join(tp.replace('tagRowDelim')));
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'tagList,tagRow,tagRowDelim'},
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
                boundingBox: tp.one('info'),
                topic: topic
            }));

            tp.setHTML({
                intro: topic.intro
            });

            tp.toggleView(topic.isEdit(), 'editButton');
            tp.toggleView(topic.isBody, 'readmore');
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'row'},
            topic: {value: null}
        },
    });

    NS.TopicViewWidget = Y.Base.create('topicViewWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt
    ], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var instance = this,
                topicid = this.get('topicid');

            NS.manager.topicLoad(topicid, function(topic){
                instance._renderTopic(topic);
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

            if (!topic){
                tp.show('nullItem');
                return;
            }

            var widget = this.addWidget('view', new NS.TopicRowWidget({
                srcNode: tp.gel('view'),
                topic: topic
            }));

            widget.template.setHTML({
                body: topic.body
            });
            widget.template.hide('readmore');

            this._commentsWidget = new Brick.mod.comment.CommentTreeWidget({
                srcNode: widget.template.one('comments'),
                commentOwner: {
                    module: 'blog',
                    type: 'topic',
                    ownerid: topic.id
                },
                readOnly: !NS.roles.isWrite
            });
            widget.template.show('commentsBlock');
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            topicid: {},
            topic: {}
        },
        parseURLParam: function(args){
            return {
                topicid: args[0] | 0
            };
        }
    });
};