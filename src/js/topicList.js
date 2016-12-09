var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['topic.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TopicHomeListWidget = Y.Base.create('TopicHomeListWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt
    ], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                p1 = this.get('param1'),
                p2 = this.get('param2'),
                instance = this;

            this.addWidget('topicList', new NS.TopicListWidget({
                srcNode: tp.one('listWidget'),
                config: {filter: p1 + '/' + p2},
                onLoadCallback: function(list){
                    instance.onLoadTopics(list);
                }
            }));
        },
        renderMenuLine: function(topicList){
            var tp = this.template,
                p1 = this.get('param1'),
                p2 = this.get('param2'),
                isNew = p2 === 'new',
                mcur = "",
                mcurpub = "",
                mcurpers = "";

            switch (p1) {
                case 'pub':
                    mcurpub = 'active';
                    break;
                case "pers":
                    mcurpers = "active";
                    break;
                default:
                    mcur = "active";
                    break;
            }

            var tpl = tp.replace('menuLine', {
                submenu: tp.replace('submenu' + p1, {
                    newcnt: topicList.totalNew > 0 ? "+" + topicList.totalNew : "",
                    f1sel: !isNew ? "active" : "",
                    f2sel: !isNew ? "" : "active"
                }),
                curr: mcur,
                currpub: mcurpub,
                currpers: mcurpers
            });

            tp.setHTML('menuLine', tpl);

            this.appURLUpdate();
        },
        onLoadTopics: function(list){
            this.set('waiting', false);
            this.renderMenuLine(list);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,menuLine,submenuindex,submenupub,submenupers'},
            param1: {value: 'index'},
            param2: {value: ''}
        },
        CLICKS: {},
        parseURLParam: function(args){
            return {
                param1: args[0] || 'index',
                param2: args[1] || ''
            };
        }
    });

    NS.TopicListWidget = Y.Base.create('TopicListWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt
    ], {
        onInitAppWidget: function(err, appInstance){
            this.catid = 0;
            this.wsList = [];
            this.next = null;

            var instance = this,
                cfg = this.get('config');

            NS.initManager(function(){
                NS.manager.topicListLoad(cfg, function(list){
                    instance.onLoadManager(list);
                });
            });
        },
        destructor: function(){
            this.clearList();
        },
        clearList: function(){
            var tp = this.template,
                ws = this.wsList;

            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
            tp.setHTML('list', '');
        },
        onLoadManager: function(list){
            var onLoadCallback = this.get('onLoadCallback');
            if (Y.Lang.isFunction(onLoadCallback)){
                onLoadCallback.call(this, list);
            }

            this.renderList(list);
            if (!list){
                return;
            }

            var tp = this.template,
                instance = this,
                cfg = {};

            this.addWidget('nextButtons', new NS.NextWidget({
                srcNode: tp.one('next'),
                limit: list.limit,
                loaded: list.count(),
                total: list.total,
                nextCallback: function(page, callback){
                    cfg.page = page;
                    cfg.list = list;
                    NS.manager.topicListLoad(cfg, function(nlist){
                        callback.call(instance, {
                            loaded: nlist.count(),
                            total: nlist.total
                        });
                        instance.renderList(nlist);
                    });
                }
            }));
        },
        renderList: function(list, isClear){
            this.set('waiting', false);
            if (isClear){
                this.clearList();
            }
            var tp = this.template,
                ws = this.wsList;

            list.foreach(function(topic){
                for (var i = 0; i < ws.length; i++){
                    if (ws[i].get('topic').id == topic.id){
                        return;
                    }
                }

                ws[ws.length] = new NS.TopicRowWidget({
                    srcNode: tp.append('list', '<div></div>'),
                    topic: topic
                });
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'topicList'},
            config: {
                value: {},
                setter: function(val){
                    return Y.merge({
                        page: 1,
                        filter: '',
                        list: null
                    }, val || {});
                }
            },
            onLoadCallback: {value: null}
        },
    });
};