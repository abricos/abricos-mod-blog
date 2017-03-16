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

module.exports = BlogModule;