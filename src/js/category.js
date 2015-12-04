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

    var L = YAHOO.lang,
        NSUR = Brick.mod.urating || {},
        UID = Brick.env.user.id,
        LNG = this.language,
        R = NS.roles,
        buildTemplate = this.buildTemplate;

    var CategoryRowWidget = function(container, category){
        CategoryRowWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'row'
        }, category);
    };
    YAHOO.extend(CategoryRowWidget, Brick.mod.widget.Widget, {
        init: function(category){
            this.category = category;
        },
        buildTData: function(category){
            return {
                'urlview': category.url(),
                'rtg': category.rating,
                'mbrs': category.memberCount,
                'topics': category.topicCount
            };
        },
        onLoad: function(category){
            this.elSetHTML({
                'tl': category.title
            });
        }
    });
    NS.CategoryRowWidget = CategoryRowWidget;

    var CategoryListWidget = function(container){
        CategoryListWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'catlist'
        });
    };
    YAHOO.extend(CategoryListWidget, Brick.mod.widget.Widget, {
        init: function(){
            this.wsList = [];
            this.wsMenuItem = 'all'; // использует wspace.js
        },
        onLoad: function(categoryid){
            var instance = this;
            NS.initManager(function(){
                instance.renderList();
            });
        },
        destroy: function(){
            this.clearList();
        },
        clearList: function(){
            var ws = this.wsList;
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
            this.elSetHTML('list', '');
        },
        renderList: function(){
            this.clearList();
            this.elHide('loading');
            this.elShow('view');

            var elList = this.gel('list');
            var ws = this.wsList;

            NS.manager.categoryList.foreach(function(category){
                var div = document.createElement('div');
                elList.appendChild(div);
                ws[ws.length] = new NS.CategoryRowWidget(div, category);
            });
        }
    });
    NS.CategoryListWidget = CategoryListWidget;

    NS.CategoryViewWidget = Y.Base.create('categoryViewWidget', SYS.AppWidget, [], {
        buildTData: function(){
            return {id: this.get('categoryid')}
        },
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var instance = this,
                categoryid = this.get('categoryid');

            NS.initManager(function(){
                var category = NS.manager.categoryList.get(categoryid);
                instance.renderCategory(category);
            });

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
                    'filter': 'category/' + category.id
                });
            }
            if (UID > 0){
                tp.show('jbtns');
                tp.toggleView(category.isMember, 'bleave', 'bjoin');
                tp.toggleView(R.isAdmin, 'bremove');
                tp.toggleView(R.category.isAdmin(category), 'bedit');
            }
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bremove']:
                    this.showRemovePanel();
                    return true;
                case tp['bjoin']:
                case tp['bleave']:
                    this.memberStatusChange();
                    return true;
            }
        },
        memberStatusChange: function(){
            var instance = this;
            this.elHide('jbtnsa');
            this.elShow('jbloading');
            NS.manager.categoryJoin(this.category.id, function(){
                instance.elShow('jbtnsa');
                instance.elHide('jbloading');
                instance.renderCategory(instance.category);
            });
        },
        showRemovePanel: function(){
            new CategoryRemovePanel(this.category, function(){
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
        this.category = category;
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
            NS.manager.categoryRemove(this.category.id, function(){
                instance.close();
                NS.life(instance.callback);
            });
        }
    });
    NS.CategoryRemovePanel = CategoryRemovePanel;
    /**/

};