var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['boxes.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        L = YAHOO.lang,
        R = NS.roles;

    var buildTemplate = this.buildTemplate;

    var AccessDeniedWidget = function(container){
        this.init(container);
    };
    AccessDeniedWidget.prototype = {
        init: function(container){
            buildTemplate(this, 'accessdenied');
            container.innerHTML = this._TM.replace('accessdenied');
        },
        destroy: function(){
            var el = this._TM.getEl('accessdenied.id');
            el.parentNode.removeChild(el);
        }
    };
    NS.AccessDeniedWidget = AccessDeniedWidget;

    var GMID = {
        'TopicHomeListWidget': 'topics',
        'TopicViewWidget': 'topics',
        'CategoryListWidget': 'cats',
        'CategoryViewWidget': 'cats',
        'WriteWidget': 'write',
        'AboutWidget': 'about'
    };
    GMIDI = {
        'topics': ['all', 'pub', 'pers'],
        'write': ['topic', 'category', 'draftlist']
    };
    var DEFPAGE = {
        'component': 'topic',
        'wname': 'TopicHomeListWidget',
        'p1': '', 'p2': '', 'p3': '', 'p4': ''
    };

    var WSWidget = function(container, pgInfo){
        WSWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        }, pgInfo || []);
    };
    YAHOO.extend(WSWidget, Brick.mod.widget.Widget, {
        init: function(pgInfo){
            this.pgInfo = pgInfo;
            this.widget = null;
        },
        buildTData: function(pgInfo){
            var NG = NS.navigator;
            return {
                'urlhome': NG.home(),
                'urltopics': NG.topic.list(),
                'urltopicspub': NG.topic.list('pub'),
                'urltopicspers': NG.topic.list('pers'),
                'urlcats': NG.category.list(),
                'urlauthors': NG.author.list(),
                'urlwrite': NG.write.topic(),
                'urlwritetopic': NG.write.topic(),
                'urlwritecategory': NG.write.category(),
                'urlwritedraftlist': NG.write.draftlist()
            };
        },
        onLoad: function(pgInfo){
            var __self = this;
            NS.initManager(function(){
                __self.onLoadManager(pgInfo);
            });
        },
        onLoadManager: function(pgInfo){
            this.cmtLiveWidget = new NS.CommentLiveBoxWidget(this.gel('cmtlive'));
            // this.topicListWidget = new NS.TopicListBoxWidget(this.gel('toplist'));
            this.tagListWidget = new NS.TagListBoxWidget(this.gel('taglist'));
            this.catListWidget = new NS.CategoryListBoxWidget(this.gel('catlist'));
            this.showPage(pgInfo);

            if (R['isWrite']){
                this.elShow('mwrite');
            }
        },
        showPage: function(p){
            p = L.merge(DEFPAGE, p || {});

            this.elHide('board');
            this.elShow('loading');

            var __self = this;
            Brick.ff('{C#MODNAME}', p['component'], function(){
                __self._showPageMethod(p);
            });
        },
        _showPageMethod: function(p){

            var wName = p['wname'];
            if (!NS[wName]){
                return;
            }

            if (!L.isNull(this.widget)){
                this.widget.destroy();
                this.widget = null;
            }
            this.elSetHTML('board', "");

            this.widget = new NS[wName](this.gel('board'), p['p1'], p['p2'], p['p3'], p['p4']);

            var isUpdate = {};
            for (var n in GMID){

                var pfx = GMID[n],
                    miEl = this.gel('m' + pfx),
                    mtEl = this.gel('mt' + pfx);

                if (wName == n){
                    isUpdate[pfx] = true;

                    Dom.addClass(miEl, 'sel');
                    Dom.setStyle(mtEl, 'display', '');

                    var mia = GMIDI[pfx];
                    if (L.isArray(mia)){
                        for (var i = 0; i < mia.length; i++){
                            var mtiEl = this.gel('i' + pfx + mia[i]);
                            if (mia[i] == this.widget.wsMenuItem){
                                Dom.addClass(mtiEl, 'current');
                            } else {
                                Dom.removeClass(mtiEl, 'current');
                            }
                        }
                    }

                } else {
                    if (isUpdate[pfx]){
                        continue;
                    }

                    Dom.removeClass(miEl, 'sel');
                    Dom.setStyle(mtEl, 'display', 'none');
                }
            }
            this.elShow('board');
            this.elHide('loading');
        }
    });
    NS.WSWidget = WSWidget;

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.WorkspaceWidget = Y.Base.create('workspaceWidget', SYS.AppWidget, [
        SYS.AppWorkspace
    ], {
        onInitAppWorkspace: function(err, appInstance){
            var tp = this.template;
            this.cmtLiveWidget = new NS.CommentLiveBoxWidget(tp.gel('commentLive'));
            this.tagListWidget = new NS.TagListBoxWidget(tp.gel('tagList'));

            this.catListWidget = new NS.CategoryListBoxWidget(tp.gel('categoryList'));

        },
        destroy: function(){
            if (this.cmtLiveWidget){
                this.cmtLiveWidget.destroy();
                this.tagListWidget.destroy();
                this.catListWidget.destroy();
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            defaultPage: {
                value: {
                    component: 'topic',
                    widget: 'TopicHomeListWidget'
                }
            }
        }
    });

    NS.ws = SYS.AppWorkspace.build('{C#MODNAME}', NS.WorkspaceWidget);
};