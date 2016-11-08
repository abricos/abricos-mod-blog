var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.ConfigWidget = Y.Base.create('configWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this.reloadConfig();
        },
        reloadConfig: function(){
            this.set('waiting', true);

            this.get('appInstance').config(function(err, result){
                this.set('waiting', false);
                if (!err){
                    this._renderConfig(result.config);
                }
            }, this);
        },
        _renderConfig: function(config){
            var tp = this.template;

            tp.setValue({
                subscribeSendLimit: config.get('subscribeSendLimit'),
                topicIndexRating: config.get('topicIndexRating'),
                categoryCreateRating: config.get('categoryCreateRating'),
            });
        },
        save: function(){
            this.set('waiting', true);

            var tp = this.template,
                sd = tp.getValue('subscribeSendLimit,topicIndexRating,categoryCreateRating');

            this.get('appInstance').configSave(sd, function(err, result){
                this.set('waiting', false);
            }, this);
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'}
        }
    });

};