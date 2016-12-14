var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'urating', files: ['vote.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.BlogListWidget = Y.Base.create('blogListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            appInstance.blogList(function(err, result){
                if (err){
                    return;
                }
                this.renderList();
            }, this);
        },
        destructor: function(){
            this.clearList();
        },
        clearList: function(){
            var ws = this.wsList || [];
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
            this.template.setHTML('list', '');
            return this.wsList = [];
        },
        renderList: function(){
            var tp = this.template,
                ws = this.clearList(),
                appInstance = this.get('appInstance');

            appInstance.get('blogList').each(function(blog){
                ws[ws.length] = new NS.BlogRowWidget({
                    boundingBox: tp.append('list', '<div class="list-group-item"></div>'),
                    blog: blog
                });
            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'blogList'},
        },
        parseURLParam: function(args){
            return {};
        }
    });

    NS.BlogRowWidget = Y.Base.create('BlogRowWidget', SYS.AppWidget, [], {
        buildTData: function(){
            var blog = this.get('blog');
            return {
                id: blog.get('id'),
                title: blog.get('title'),
                mbrs: blog.get('memberCount'),
                topics: blog.get('topicCount')
            };
        },
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                blog = this.get('blog'),
                voting = blog.get('voting');

            if (voting){
                tp.show('voting');
                this.votingWidget = new Brick.mod.urating.VotingWidget({
                    boundingBox: tp.one('voting'),
                    voting: voting
                });
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'row'},
            blog: {}
        },
    });
};