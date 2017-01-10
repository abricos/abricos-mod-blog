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

    NS.AuthorListWidget = Y.Base.create('authorListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);
            var instance = this;

            NS.manager.authorListLoad(function(list){
                instance.renderList(list);
            });
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
        renderList: function(list){
            this.set('waiting', false);

            if (!list){
                return;
            }

            var tp = this.template,
                ws = this.clearList();

            list.foreach(function(author){
                ws[ws.length] = new NS.AuthorRowWidget({
                    boundingBox: tp.append('list', '<div class="list-group-item"></div>'),
                    author: author
                });
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
        },
        parseURLParam: function(args){
            return {};
        }
    });

    NS.AuthorRowWidget = Y.Base.create('authorRowWidget', SYS.AppWidget, [], {
        buildTData: function(){
            var author = this.get('author'),
                user = author.user;

            return {
                userid: user.get('id'),
                avatar: user.get('avatarSrc90'),
                viewName: user.get('viewName'),
                topics: author.topicCount
            };
        },
        onInitAppWidget: function(err, appInstance){
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'row'},
            author: {}
        },
    });

    NS.AuthorViewWidget = Y.Base.create('authorViewWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt
    ], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);
            var instance = this;
            NS.manager.authorLoad(this.get('authorid'), function(author){
                instance.renderAuthor(author);
            });
        },
        renderAuthor: function(author){
            this.set('waiting', false);

            if (!author){
                return;
            }

            var tp = this.template;

            this.addWidget('author', new NS.AuthorRowWidget({
                srcNode: tp.one('author'),
                author: author
            }));

            this.addWidget('topicList', new NS.TopicListWidget({
                srcNode: tp.one('topicList'),
                config: {
                    filter: 'author/' + author.id
                }
            }));
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'view'},
            authorid: {value: 0}
        },
        parseURLParam: function(args){
            return {
                authorid: args[0] | 0
            };
        }
    });

};