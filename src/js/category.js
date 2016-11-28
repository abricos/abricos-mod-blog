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


    NS.CategoryRowWidget = Y.Base.create('categoryRowWidget', SYS.AppWidget, [], {
        buildTData: function(){
            var category = this.get('category');
            return {
                urlview: category.url(),
                rtg: category.rating,
                mbrs: category.memberCount,
                topics: category.topicCount
            };
        },
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                category = this.get('category');

            tp.setHTML({
                tl: category.title
            });
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
            this.wsList = [];
            this.renderList();
        },
        destructor: function(){
            this.clearList();
        },
        clearList: function(){
            var ws = this.wsList;
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
            this.template.setHTML('list', '');
        },
        renderList: function(){
            this.clearList();
            var tp = this.template;

            tp.hide('loading');
            tp.show('list');

            var ws = this.wsList;

            NS.manager.categoryList.foreach(function(category){
                ws[ws.length] = new NS.CategoryRowWidget({
                    srcNode: tp.append('list', '<div></div>'),
                    category: category
                });
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'catlist'},
        },
        parseURLParam: function(args){
            return {};
        }
    });

    NS.CategoryViewWidget = Y.Base.create('categoryViewWidget', SYS.AppWidget, [], {
        buildTData: function(){
            var categoryid = this.get('categoryid'),
                category = NS.manager.categoryList.get(categoryid);

            return {
                id: categoryid,
                title: category.title
            }
        },
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var categoryid = this.get('categoryid'),
                category = NS.manager.categoryList.get(categoryid);

            this.renderCategory(category);
        },
        destructor: function(){
            if (this.votingWidget){
                this.votingWidget.destroy();
            }
            if (this.topicListWidget){
                this.topicListWidget.destroy();
            }
        },
        renderCategory: function(category){
            this.set('waiting', false);
            this.set('category', category);

            var tp = this.template;

            tp.toggleView(!category, 'nullitem', 'view');

            if (!category){
                return;
            }
            tp.setHTML({
                'tl': category.title,
                'mbrs': category.memberCount,
                'topics': category.topicCount
            });

            if (category.voting){
                tp.show('voting');
                this.votingWidget = new Brick.mod.urating.VotingWidget({
                    boundingBox: tp.one('voting'),
                    voting: category.voting
                });
            }

            if (!this.topicListWidget){
                this.topicListWidget = new NS.TopicListWidget(tp.gel('toplist'), {
                    'filter': 'cat/' + category.id
                });
            }

            tp.toggleView(Brick.env.user.id > 0, 'subscribeButtons');
            tp.toggleView(category.isMember, 'unsubscribeButton', 'subscribeButton');
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'bjoin':
                case 'bleave':
                    this.memberStatusChange();
                    return true;
            }
        },
        memberStatusChange: function(){
            var instance = this,
                tp = this.template;

            tp.hide('jbtnsa');
            tp.show('jbloading');

            NS.manager.categoryJoin(this.get('category').id, function(){
                tp.show('jbtnsa');
                tp.hide('jbloading');
                instance.renderCategory(instance.get('category'));
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'catview'},
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