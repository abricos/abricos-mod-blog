var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['base.js', 'old_model.js']}
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

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });
};