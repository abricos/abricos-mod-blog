var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js', 'appModel.js']},
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI;

    var number = {
        value: 0,
        setter: function(val){
            return val | 0;
        }
    };

    NS.ATTRIBUTE = {
        number: number,
        blogid: number,
    };
};
