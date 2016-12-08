var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'urating', files: ['vote.js']},
        {name: '{C#MODNAME}', files: ['topic.js']}
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

            if (list){
                return;
            }

            var tp = this.template,
                ws = this.clearList();
            list.foreach(function(author){
                ws[ws.length] = new NS.AuthorRowWidget({
                    srcNode: tp.append('list', '<div class="list-group-item"></div>'),
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
                uid: user.get('id'),
                avatar: user.get('avatarSrc90'),
                unm: user.get('viewName'),
                rep: author.reputation,
                topics: author.topicCount,
                urlview: NS.navigator.author.view(author.id)
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

    NS.AuthorViewWidget = Y.Base.create('authorViewWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.viewWidget = null;
            this.topicListWidget = null;

            NS.manager.authorLoad(this.get('authorid'), function(author){
                instance.renderAuthor(author);
            });

        },
        destructor: function(){
            if (this.viewWidget){
                this.viewWidget.destroy();
            }
            if (this.topicListWidget){
                this.topicListWidget.destroy();
            }
        },
        renderAuthor: function(author){
            this.author = author;

            this.elHide('loading');

            if (!author){
                this.elShow('nullitem');
                return;
            }
            this.elShow('view');

            if (!this.viewWidget){
                this.viewWidget = new NS.AuthorRowWidget(this.gel('author'), author);
            }

            if (!this.topicListWidget){
                this.topicListWidget = new NS.TopicListWidget(this.gel('toplist'), {
                    'filter': 'author/' + author.id
                });
            }
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