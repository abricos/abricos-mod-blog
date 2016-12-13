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

    NS.CategoryRowWidget = Y.Base.create('categoryRowWidget', SYS.AppWidget, [], {
        buildTData: function(){
            var category = this.get('category');
            return {
                id: category.id,
                title: category.title,
                urlview: category.url(),
                mbrs: category.memberCount,
                topics: category.topicCount
            };
        },
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                category = this.get('category');

            if (category.voting){
                tp.show('voting');
                this.votingWidget = new Brick.mod.urating.VotingWidget({
                    boundingBox: tp.one('voting'),
                    voting: category.voting
                });
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'row'},
            category: {}
        },
    });

    NS.CategoryListWidget = Y.Base.create('categoryListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.renderList();
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
                ws = this.clearList();

            NS.manager.categoryList.foreach(function(category){
                ws[ws.length] = new NS.CategoryRowWidget({
                    boundingBox: tp.append('list', '<div class="list-group-item"></div>'),
                    category: category
                });
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'categoryList'},
        },
        parseURLParam: function(args){
            return {};
        }
    });

    NS.CategoryViewWidget = Y.Base.create('categoryViewWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt
    ], {
        buildTData: function(){
            return {
                id: this.get('categoryid')
            }
        },
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                categoryid = this.get('categoryid'),
                category = NS.manager.categoryList.get(categoryid);

            if (!category){
                return tp.show('notFoundBlock');
            }

            tp.setHTML({
                title: category.title
            });

            tp.show('headingBlock,topicListBlock,infoBlock');

            if (category.voting){
                tp.show('voting');
                this.addWidget('voting', new Brick.mod.urating.VotingWidget({
                    boundingBox: tp.one('voting'),
                    voting: category.voting
                }));
            }

            this.addWidget('topicList', new NS.TopicListWidget({
                srcNode: tp.gel('toplist'),
                config: {
                    filter: 'cat/' + category.id
                }
            }));

            this.renderCategory(category);
        },
        renderCategory: function(category){
            this.set('waiting', false);

            if (!category){
                return;
            }

            var tp = this.template;

            tp.setHTML({
                'mbrs': category.memberCount,
                'topics': category.topicCount
            });

            tp.toggleView(Brick.env.user.id > 0, 'subscribeButtons');
            tp.toggleView(category.isMember, 'unsubscribeButton', 'subscribeButton');
        },
        subscribe: function(){
            this.set('waiting', true);

            var instance = this;
            NS.manager.categoryJoin(this.get('category').id, function(){
                instance.set('waiting', false);
                instance.renderCategory(instance.get('category'));
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'categoryView'},
            categoryid: {},
            category: {}
        },
        parseURLParam: function(args){
            return {
                categoryid: args[0] | 0
            };
        }
    });
};