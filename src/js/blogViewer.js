var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'urating', files: ['vote.js']},
        {name: '{C#MODNAME}', files: ['topicList.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.BlogViewerWidget = Y.Base.create('BlogViewerWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt
    ], {
        buildTData: function(){
            return {
                id: this.get('blogid')
            }
        },
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                blogid = this.get('blogid');

            appInstance.blog(blogid, function(err, result){
                if (err){
                    return tp.show('notFoundBlock');
                }
                this.set('blog', result.blog);
                this._onLoadBlog();
            }, this);
        },
        _onLoadBlog: function(){
            var tp = this.template,
                blogid = this.get('blogid'),
                blog = this.get('blog'),
                voting = blog.get('voting');

            tp.show('headingBlock,topicListBlock,infoBlock');

            if (voting){
                tp.show('voting');
                this.addWidget('voting', new Brick.mod.urating.VotingWidget({
                    boundingBox: tp.one('voting'),
                    voting: voting
                }));
            }

            this.addWidget('topicList', new NS.TopicListWidget({
                srcNode: tp.gel('toplist'),
                config: {
                    filter: 'cat/' + blogid
                }
            }));

            this.renderBlog();
        },
        renderBlog: function(){
            this.set('waiting', false);

            var tp = this.template,
                blog = this.get('blog');

            tp.setHTML({
                title: blog.get('title'),
                mbrs: blog.get('memberCount'),
                topics: blog.get('topicCount')
            });

            tp.toggleView(Brick.env.user.id > 0, 'subscribeButtons');
            tp.toggleView(blog.isMember, 'unsubscribeButton', 'subscribeButton');
        },
        subscribe: function(){
            this.set('waiting', true);

            var instance = this;
            NS.manager.blogJoin(this.get('blog').id, function(){
                instance.set('waiting', false);
                instance.renderBlog(instance.get('blog'));
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            blogid: NS.ATTRIBUTE.number,
            blog: {}
        },
        parseURLParam: function(args){
            return {
                blogid: args[0] | 0
            };
        }
    });
};