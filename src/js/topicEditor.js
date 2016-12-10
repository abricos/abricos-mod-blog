var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['write.js', 'topic.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var R = NS.roles,
        LNG = this.language;

    NS.TopicEditorWidget = Y.Base.create('TopicEditorWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt,
        SYS.WidgetEditorStatus
    ], {
        onInitAppWidget: function(err, appInstance){
            var topicid = this.get('topicid'),
                instance = this;

            this.set('waiting', true);
            NS.initManager(function(){
                if (topicid == 0){
                    instance.onLoadManager(new NS.Topic());
                } else {
                    NS.manager.topicLoad(topicid, function(topic){
                        instance.onLoadManager(topic);
                    });
                }
            });
        },
        onLoadManager: function(topic){
            this.set('waiting', false);
            this.set('topic', topic);

            var tp = this.template;

            this.addWidget('categorySelect', new NS.WriteCategorySelectWidget({
                srcNode: tp.one('catsel'),
                catid: topic.catid
            }));

            tp.setValue({
                title: topic.title,
                tags: topic.tagList.toString()
            });

            this.addWidget('introEditor', new SYS.Editor({
                srcNode: this.gel('intro'),
                content: topic.intro
            }));

            this.addWidget('bodyEditor', new SYS.Editor({
                srcNode: this.gel('body'),
                content: topic.body
            }));

            if (R.isAdmin){
                tp.show('admindex');
                tp.setValue('isindex', (topic.isIndex && !topic.isAutoIndex));
            }
        },
        toJSON: function(){
            var tp = this.template,
                catSelWidget = this.getWidget('categorySelect'),
                stags = tp.getValue('tags');

            return {
                id: this.get('topic').id,
                catid: catSelWidget.getValue(),
                tl: tp.getValue('title'),
                tags: NS.TagList.stringToAJAX(stags),
                intro: this.getWidget('introEditor').get('content'),
                body: this.getWidget('bodyEditor').get('content'),
                idx: tp.getValue('isindex') ? 1 : 0
            };
        },
        showPreview: function(){
            this.set('waiting', true);

            var instance = this,
                sd = this.toJSON();

            NS.manager.topicPreview(sd, function(topic){
                instance.set('waiting', true);
                new NS.TopicPreviewPanel({
                    topic: topic
                });
            });
        },
        saveDraft: function(){
            this.save(true);
        },
        save: function(isdraft){
            this.set('waiting', true);

            var instance = this,
                sd = this.toJSON();


            sd.dft = isdraft ? 1 : 0;
            NS.manager.topicSave(sd, function(topicid, error){
                instance.set('waiting', false);

                if (!error || topicid == 0){
                    error = !error ? 'null' : error;
                    var sError = LNG.get('write.topic.error.' + error);
                    Brick.mod.widget.notice.show(sError);
                } else {
                    instance.go('topic.view', topicid);
                }
            });
        },
        cancel: function(){
            var topicid = this.get('topicid');
            if (topicid == 0){
                this.go('ws');
            } else {
                this.go('topic.view', topicid);
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            topicid: {value: 0},
            topic: {},
            isEdit: {
                getter: function(){
                    return (this.get('topicid') | 0) > 0;
                }
            }
        },
        parseURLParam: function(args){
            return {
                topicid: args[0] | 0
            }
        }
    });

    NS.TopicPreviewPanel = Y.Base.create('topicPreviewPanel', SYS.Dialog, [], {
        initializer: function(){
            Y.after(this._syncUIGroupEditorDialog, this, 'syncUI');
        },
        _syncUIGroupEditorDialog: function(){
            var tp = this.template,
                topic = this.get('topic');

            var widget = this.viewWidget =
                new NS.TopicRowWidget(tp.gel('widget'), topic);

            widget.elSetHTML({
                body: topic.body
            });
            widget.elHide('readmore');
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'topicpreview'},
            topic: {value: 0},
        }
    });
};