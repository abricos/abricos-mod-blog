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
            var instance = this;
            NS.initManager(function(){
                instance.onInitOldManager.call(instance, NS.manager);
            });
        },
        onInitOldManager: function(){
            var tp = this.template;

            this.addWidget('commentLiveList', new NS.CommentLiveBoxWidget({
                srcNode: tp.one('commentLive')
            }));

            this.addWidget('tagList', new NS.TagListBoxWidget({
                srcNode: tp.one('tagList')
            }));

            this.addWidget('topicList', new NS.TopicListBoxWidget({
                srcNode: tp.one('topicList')
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