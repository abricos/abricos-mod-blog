'use strict';

var fs = require('fs');
var path = require('path');

var Data = function(){
    this.data = {};
    this.srcDir = path.join(__dirname, '..', 'data');
};

Data.prototype.get = function(name){
    if (this.data[name]){
        return this.data[name];
    }
    var file = path.join(this.srcDir, name + '.json');
    var json = JSON.parse(fs.readFileSync(file, 'utf8'));

    return this.data[name] = json;
};
module.exports = new Data();