var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['model.js', 'old_lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var COMPONENT = this,
        SYS = Brick.mod.sys;

    SYS.Application.build(COMPONENT, {}, {
        initializer: function(){
            NS.roles.load(function(){
                this.initCallbackFire();
            }, this);
        }
    }, [], {
        APPS: {
            uprofile: {},
            urating: {},
            comment: {},
            notify: {}
        },
        ATTRS: {
            isLoadAppStructure: {value: true},
            Blog: {value: NS.Blog},
            BlogList: {value: NS.BlogList},
            Config: {value: NS.Config},
        },
        REQS: {
            blogList: {
                attribute: true,
                type: "modelList:BlogList"
            },
            config: {
                attribute: true,
                type: 'model:Config'
            },
            configSave: {
                args: ['data']
            }
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
            config: function(){
                return this.getURL('ws') + 'config/ConfigWidget/';
            },
            topic: {
                list: function(){
                    return this.getURL('ws') + 'topicList/TopicHomeListWidget/';
                },
                listNew: function(){
                    return this.getURL('topic.list') + 'new/';
                },
                listPub: function(){
                    return this.getURL('topic.list') + 'pub/';
                },
                listPubNew: function(){
                    return this.getURL('topic.listPub') + 'new/';
                },
                listPers: function(){
                    return this.getURL('topic.list') + 'pers/';
                },
                listPersNew: function(){
                    return this.getURL('topic.listPers') + 'new/';
                },
                view: function(topicid){
                    return this.getURL('ws') + 'topic/TopicViewWidget/' + (topicid | 0) + '/';
                },
                create: function(catid){
                    return this.getURL('ws') + 'topicEditor/TopicEditorWidget/0/' + (catid | 0) + '/';
                },
                edit: function(topicid){
                    return this.getURL('ws') + 'topicEditor/TopicEditorWidget/' + (topicid | 0) + '/';
                }
            },
            tag: {
                view: function(tag){
                    return this.getURL('ws') + 'tag/TagViewWidget/' + tag + '/';
                }
            },
            category: {
                list: function(){
                    return this.getURL('ws') + 'category/CategoryListWidget/';
                },
                view: function(catid){
                    return this.getURL('ws') + 'category/CategoryViewWidget/' + catid + '/';
                },
                edit: function(catid){
                    return this.getURL('ws') + 'categoryEditor/CategoryEditorWidget/' + (catid | 0) + '/';
                }
            },
            author: {
                list: function(){
                    return this.getURL('ws') + 'author/AuthorListWidget/';
                },
                view: function(authorid){
                    return this.getURL('ws') + 'author/AuthorViewWidget/' + authorid + '/';
                }
            },
            write: {
                view: function(){
                    return this.getURL('ws') + 'write/WriteWidget/';
                },
                topic: function(id){
                    id = id || 0;
                    return this.getURL('write.view') + 'topic/' + (id > 0 ? id + "/" : "");
                },
                category: function(id){
                    return this.getURL('write.view') + 'category/' + (id | 0) + "/";
                },
                draftlist: function(){
                    return this.getURL('write.view') + 'draftlist/';
                }
            },
            about: function(){
                return this.getURL('ws') + 'about/AboutWidget/';
            },
        }
    });

};