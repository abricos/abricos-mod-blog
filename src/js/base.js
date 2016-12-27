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
        blog: {
            getter: function(val, name){
                var data = this._state.data[name];
                if (data.value){
                    return data.value;
                }
                var blogList = this.appInstance.get('blogList');
                return data.value = blogList.getById(this.get('blogid'));
            }
        },
        user: {
            getter: function(val, name){
                var data = this._state.data[name];
                if (data.value){
                    return data.value;
                }
                var uprofileApp = this.appInstance.getApp('uprofile'),
                    userList = uprofileApp.get('userList');
                return data.value = userList.getById(this.get('userid'));
            }
        }
    };
};
