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

    var NSUR = Brick.mod.urating || {},
        UID = Brick.env.user.id,
        LNG = this.language,
        R = NS.roles,
        buildTemplate = this.buildTemplate;


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
            this.wsMenuItem = 'all'; // использует wspace.js
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
            return {id: this.get('categoryid')}
        },
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var categoryid = this.get('categoryid'),
                category = NS.manager.categoryList.get(categoryid);

            this.renderCategory(category);
        },
        destroy: function(){
            if (this.voteWidget){
                this.voteWidget.destroy();
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

            if (NSUR.VotingWidget && Y.Lang.isNull(this.voteWidget)){
                this.voteWidget = new NSUR.VotingWidget(this.gel('rating'), {
                    'modname': '{C#MODNAME}',
                    'elementType': 'category',
                    'elementId': category.id,
                    'value': category.rating,
                    'vote': category.voteMy,
                    'hideButtons': UID == 0,
                    'onVotingError': function(error, merror){
                        var s = 'ERROR';
                        if (merror > 0){
                            s = LNG.get('category.vote.error.m.' + merror);
                        } else if (error == 1){
                            s = LNG.get('category.vote.error.' + error);
                        } else {
                            return;
                        }
                        Brick.mod.widget.notice.show(s);
                    }
                });
            }
            if (!this.topicListWidget){
                this.topicListWidget = new NS.TopicListWidget(tp.gel('toplist'), {
                    'filter': 'cat/' + category.id
                });
            }
            if (UID > 0){
                tp.show('jbtns');
                tp.toggleView(category.isMember, 'bleave', 'bjoin');
                tp.toggleView(R.isAdmin, 'bremove');
                tp.toggleView(R.category.isAdmin(category), 'bedit');
            }
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'bremove':
                    this.showRemovePanel();
                    return true;
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
        },
        showRemovePanel: function(){
            new CategoryRemovePanel(this.get('category'), function(){
                NS.navigator.go(NS.navigator.category.list());
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


    return;

    var CategoryRemovePanel = function(category, callback){
        this.callback = callback;
        CategoryRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
    };
    YAHOO.extend(CategoryRemovePanel, Brick.widget.Dialog, {
        initTemplate: function(){
            return buildTemplate(this, 'removepanel').replace('removepanel');
        },
        onClick: function(el){
            var tp = this._TId['removepanel'];
            switch (el.id) {
                case tp['bcancel']:
                    this.close();
                    return true;
                case tp['bremove']:
                    this.remove();
                    return true;
            }
            return false;
        },
        remove: function(){
            var TM = this._TM, gel = function(n){
                    return TM.getEl('removepanel.' + n);
                },
                instance = this;
            Dom.setStyle(gel('btns'), 'display', 'none');
            Dom.setStyle(gel('bloading'), 'display', '');
            NS.manager.categoryRemove(this.get('category').id, function(){
                instance.close();
                NS.life(instance.callback);
            });
        }
    });
    NS.CategoryRemovePanel = CategoryRemovePanel;
    /**/

};