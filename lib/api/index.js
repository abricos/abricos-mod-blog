'use strict';

var util = require('util');
var Module = require('abricos-rest').Module;

function BlogModule(){
    Module.apply(this, arguments);
}

util.inherits(BlogModule, Module);

BlogModule.prototype.moduleConfig = function(callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'config');

    api.get(uri, callback, context);
};

BlogModule.prototype.moduleConfigUpdate = function(data, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'configUpdate');

    api.post(uri, data, callback, context);
};

BlogModule.prototype.blog = function(blogid, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'blog', [blogid]);

    api.get(uri, callback, context);
};

BlogModule.prototype.blogBySlug = function(slug, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'blogBySlug', [slug]);

    api.get(uri, callback, context);
};

BlogModule.prototype.blogList = function(callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'blogList');

    api.get(uri, callback, context);
};

BlogModule.prototype.blogAppend = function(data, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'blogAppend');

    api.post(uri, data, callback, context);
};

BlogModule.prototype.blogUpdate = function(data, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'blogUpdate');

    api.post(uri, data, callback, context);
};

BlogModule.prototype.blogRemove = function(data, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'blogRemove');

    api.post(uri, data, callback, context);
};

BlogModule.prototype.blogJoin = function(data, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'blogJoin');

    api.post(uri, data, callback, context);
};

BlogModule.prototype.blogLeave = function(data, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'blogLeave');

    api.post(uri, data, callback, context);
};


BlogModule.prototype.topic = function(topicid, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'topic', [topicid]);

    api.get(uri, callback, context);
};

BlogModule.prototype.topicList = function(data, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'topicList');

    api.post(uri, data, callback, context);
};

BlogModule.prototype.topicAppend = function(data, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'topicAppend');

    api.post(uri, data, callback, context);
};

BlogModule.prototype.topicUpdate = function(data, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'topicUpdate');

    api.post(uri, data, callback, context);
};

BlogModule.prototype.topicRemove = function(data, callback, context){
    var api = this.api;
    var uri = api.getURI(this, 'topicRemove');

    api.post(uri, data, callback, context);
};

module.exports = BlogModule;