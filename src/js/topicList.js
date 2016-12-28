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

            this.set('waiting', true);
            appInstance.topicList(cfg, function(err, result){
                this.set('waiting', false);
                if (err){
                    return;
                }
                this.set('topicList', result.topicList);
                this.onLoadTopicList();
            }, this);
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
        onLoadTopicList: function(){
            var appInstance = this.get('appInstance'),
                topicList = this.get('topicList'),
                options = topicList.get('options'),
                onLoadCallback = this.get('onLoadCallback');

            if (Y.Lang.isFunction(onLoadCallback)){
                onLoadCallback.call(this, topicList);
            }

            this.renderList();

            var tp = this.template,
                instance = this,
                cfg = {};

            this.addWidget('nextButtons', new NS.NextWidget({
                srcNode: tp.one('next'),
                limit: options.limit | 10,
                loaded: topicList.size(),
                total: topicList.get('total'),
                nextCallback: function(page, callback){
                    cfg.page = page;
                    cfg.list = list;

                    appInstance.topicList(cfg, function(err, result){
                        if (err){
                            return;
                        }
                        instance.set('topicList', result.topicList);
                        callback.call(instance, {
                            loaded: result.topicList.size(),
                            total: result.topicList.get('total')
                        });
                        instance.clearList();
                        instance.renderList();
                    }, instance);
                }
            }));
        },
        renderList: function(){
            this.set('waiting', false);

            var tp = this.template,
                ws = this.wsList;

            this.get('topicList').each(function(topic){
                for (var i = 0; i < ws.length; i++){
                    if (ws[i].get('topic').get('id') === topic.get('id')){
                        return;
                    }
                }
                ws[ws.length] = new NS.TopicRowWidget({
                    srcNode: tp.append('list', '<div></div>'),
                    topic: topic
                });
            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'topicList'},
            topicList: {value: null},
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