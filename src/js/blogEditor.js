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

    NS.BlogEditorWidget = Y.Base.create('BlogEditorWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt,
        SYS.WidgetEditorStatus
    ], {
        buildTData: function(){
            return {
                id: this.get('blogid')
            }
        },
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var tp = this.template,
                blogid = this.get('blogid');

            if (blogid > 0){
                appInstance.blog(blogid, function(err, result){
                    if (err){
                        return tp.show('notFoundBlock');
                    }
                    this.set('blog', result.blog);
                    this._onLoadBlog();
                }, this);
            } else {
                appInstance.appStructure(function(){
                    this.set('blog', new NS.Blog({
                        appInstance: appInstance
                    }));
                    this._onLoadBlog();
                }, this);
            }
        },
        _onLoadBlog: function(){
            this.set('waiting', false);

            var tp = this.template,
                blog = this.get('blog');

            this.addWidget('descriptEditor', new SYS.Editor({
                srcNode: tp.one('descript'),
                content: blog.get('descript')
            }));

            tp.setValue({
                title: blog.get('title'),
                slug: blog.get('slug'),
                newTopicUserRep: blog.get('newTopicUserRep')
            });

            tp.toggleView(Brick.mod.urating, 'newTopicUserRepBlock');
            tp.toggleView(R.isAdmin, 'slugBlock');
        },
        toJSON: function(){
            var tp = this.template;
            return {
                blogid: this.get('blogid'),
                title: tp.getValue('title'),
                slug: tp.getValue('slug'),
                descript: this.getWidget('descriptEditor').get('content'),
                newTopicUserRep: tp.getValue('newTopicUserRep')
            };
        },
        cancel: function(){
            var blogid = this.get('blogid');
            if (blogid > 0){
                this.go('category.view', blogid);
            } else {
                this.go('ws');
            }
        },
        save: function(){
            this.set('waiting', true);

            var data = this.toJSON();

            this.get('appInstance').blogSave(data, function(err, result){
                if (err){
                    return;
                }
                this.go('blog.view', result.blogSave.blogid);
            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            blogid: NS.ATTRIBUTE.number,
            blog: {value: null},
            isEdit: {
                getter: function(){
                    return this.get('blogid') > 0;
                }
            }
        },
        parseURLParam: function(args){
            return {
                blogid: args[0] | 0
            }
        }
    });
};