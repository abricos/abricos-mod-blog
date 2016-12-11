var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['topicList.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TagViewWidget = Y.Base.create('tagViewWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt
    ], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                tag = this.get('tag');

            tp.setValue('tag', tag);

            this.addWidget('topicList', new NS.TopicListWidget({
                srcNode: tp.one('topicList'),
                config: {
                    filter: 'tag/' + tag
                }
            }));

            tp.one('tag').on('keypress', function(e){
                if (e.keyCode != 13){
                    return false;
                }
                this.tagView();
            }, this);
        },
        tagView: function(){
            var tag = Y.Lang.trim(this.template.getValue('tag'));
            if (tag.length == 0){
                return;
            }
            this.go('tag.view', tag);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            tag: {value: ''}
        },
        parseURLParam: function(args){
            return {
                tag: args[0] || ''
            };
        }
    });
};