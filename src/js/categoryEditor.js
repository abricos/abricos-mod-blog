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

    NS.CategoryEditorWidget = Y.Base.create('CategoryEditorWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt,
        SYS.WidgetEditorStatus
    ], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var catid = this.get('catid'),
                instance = this;

            NS.initManager(function(){
                if (catid == 0){
                    instance.onLoadManager(new NS.Category());
                } else {
                    var cat = NS.manager.categoryList.get(catid);
                    instance.onLoadManager(cat);
                }
            });
        },
        onLoadManager: function(cat){
            this.set('cat', cat);
            this.set('waiting', false);

            var tp = this.template;

            this.addWidget('descriptEditor', new SYS.Editor({
                srcNode: tp.one('descript'),
                content: cat.descript
            }));

            tp.setValue({
                title: cat.title,
                name: cat.name,
                rep: cat.reputation
            });

            tp.toggleView(NS.isURating, 'repblock');
            tp.toggleView(R.isAdmin, 'nameBlock');
        },
        toJSON: function(){
            var tp = this.template;
            return {
                id: this.get('cat').id,
                tl: tp.getValue('title'),
                'nm': tp.getValue('name'),
                'dsc': this.getWidget('descriptEditor').get('content'),
                'rep': tp.getValue('rep')
            };
        },
        cancel: function(){
            var catid = this.get('catid');
            if (catid > 0){
                this.go('category.view', catid);
            } else {
                this.go('ws');
            }
        },
        save: function(){
            this.set('waiting', true);

            var instance = this,
                sd = this.toJSON();

            NS.manager.categorySave(sd, function(catid, error){
                instance.set('waiting', false);

                if (!error || catid == 0){
                    error = !error ? 'null' : error;
                    var sError = LNG.get('write.category.error.' + error);
                    Brick.mod.widget.notice.show(sError);
                } else {
                    this.go('category.view', catid);
                }
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            catid: {value: 0},
            cat: {},
            isEdit: {
                getter: function(){
                    return (this.get('catid') | 0) > 0;
                }
            }
        },
        parseURLParam: function(args){
            return {
                catid: args[0] | 0
            }
        }
    });
};