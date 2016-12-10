var Component = new Brick.Component();
Component.requires = {
    yahoo: ['autocomplete', 'dragdrop'],
    mod: [
        {name: 'sys', files: ['editor.js', 'panel.js']},
        {name: '{C#MODNAME}', files: ['topic.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var R = NS.roles;

    NS.WriteCategorySelectWidget = Y.Base.create('WriteCategorySelectWidget', SYS.AppWidget, [], {
        buildTData: function(){
            var tp = this.template,
                catid = this.get('catid'),
                lst = tp.replace('catselmyrow');

            NS.manager.categoryList.foreach(function(cat){
                if (!R.category.isMember(cat)){
                    return;
                }
                lst += tp.replace('catselrow', {
                    id: cat.id,
                    tl: cat.title
                });
            });
            return {rows: lst};
        },
        onInitAppWidget: function(err, appInstance){
            this.setValue(this.get('catid'));
        },
        getValue: function(){
            return this.template.getValue('id');
        },
        setValue: function(value){
            this.template.setValue('id', value);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'catsel,catselrow,catselmyrow'},
            catid: {value: 0}
        },
    });
};