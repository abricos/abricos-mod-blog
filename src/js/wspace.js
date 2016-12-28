var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['boxes.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.WorkspaceWidget = Y.Base.create('workspaceWidget', SYS.AppWidget, [
        SYS.AppWorkspace,
        SYS.ContainerWidgetExt
    ], {
        onInitAppWorkspace: function(err, appInstance){
            this.set('waiting', true);
            appInstance.boxes({
                commentLive: {limit: 10},
                topicList: {limit: 5},
                tagList: {limit: 35}
            }, this._onLoadBoxes, this);
        },
        _onLoadBoxes: function(err, result){
            this.set('waiting', false);
            if (err){
                return;
            }

            var tp = this.template;

            this.addWidget('commentLiveList', new NS.CommentLiveBoxWidget({
                srcNode: tp.one('commentLive'),
                commentLiveList: result.commentLiveList,
            }));

            this.addWidget('tagList', new NS.TagListBoxWidget({
                srcNode: tp.one('tagList'),
                tagList: result.tagList
            }));

            this.addWidget('topicList', new NS.TopicListBoxWidget({
                srcNode: tp.one('topicList'),
                topicList: result.topicList
            }));

            this.addWidget('blogList', new NS.CategoryListBoxWidget({
                srcNode: tp.one('blogList')
            }));
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            defaultPage: {
                value: {
                    component: 'topicList',
                    widget: 'TopicHomeListWidget'
                }
            }
        }
    });

    NS.ws = SYS.AppWorkspace.build('{C#MODNAME}', NS.WorkspaceWidget);
};