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

    NS.NextWidget = Y.Base.create('NextWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this._isProcess = false;
            this.renderButtons();
        },
        renderButtons: function(){
            var tp = this.template,
                loaded = this.get('loaded') | 0,
                total = this.get('total') | 0,
                limit = this.get('limit') | 0;

            limit = Math.max(Math.min(limit, total - loaded), 0);

            tp.setHTML('button', tp.replace('nextText', {
                limit: limit,
                total: total - loaded
            }));
            tp.toggleView(loaded < total, 'button');
        },
        nextLoad: function(){
            if (this._isProcess){
                return;
            }

            var nextCallback = this.get('nextCallback');
            if (!Y.Lang.isFunction(nextCallback)){
                return;
            }

            this._isProcess = true;
            this.set('waiting', true);
            this.set('page', this.get('page') + 1);


            var instance = this;

            nextCallback.call(this, this.get('page'), function(nCfg){
                instance.onLoaded(nCfg);
            });
        },
        onLoaded: function(nCfg){
            this._isProcess = false;
            this.set('waiting', false);

            this.set('loaded', nCfg.loaded);
            this.set('total', nCfg.total);

            this.renderButtons();
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'next,nextText'},
            page: {value: 1},
            loaded: {value: 0},
            total: {value: 0},
            limit: {value: 10},
            nextCallback: {value: null}
        }
    });
};