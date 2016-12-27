var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['base.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        SYS = Brick.mod.sys,
        UID = Brick.env.user.id | 0;

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isWrite: 20,
        isView: 10
    });

    NS.Blog = Y.Base.create('blog', SYS.AppModel, [], {
        structureName: 'Blog'
    });

    NS.BlogList = Y.Base.create('blogList', SYS.AppModelList, [], {
        appItem: NS.Blog
    });

    NS.BlogUserRole = Y.Base.create('blogUserRole', SYS.AppModel, [], {
        structureName: 'BlogUserRole'
    });

    NS.BlogUserRoleList = Y.Base.create('blogUserRoleList', SYS.AppModelList, [], {
        appItem: NS.BlogUserRole
    });

    NS.Topic = Y.Base.create('topic', SYS.AppModel, [], {
        structureName: 'Topic'
    }, {
        ATTRS: {
            blog: NS.ATTRIBUTE.blog,
            user: NS.ATTRIBUTE.user
        }
    });

    NS.TopicList = Y.Base.create('topicList', SYS.AppModelList, [], {
        appItem: NS.Topic
    });

    NS.Tag = Y.Base.create('tag', SYS.AppModel, [], {
        structureName: 'Tag'
    });

    NS.TagList = Y.Base.create('tagList', SYS.AppModelList, [], {
        appItem: NS.Tag
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });
};