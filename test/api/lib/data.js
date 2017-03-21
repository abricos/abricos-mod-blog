'use strict';

var fs = require('fs');
var path = require('path');
var randomInt = require('abricos-rest').helper.randomInt;

var Data = function(){
    this.data = {};
    this.srcDir = path.join(__dirname, '..', 'data');
};

Data.prototype.get = function(name){
    if (this.data[name]){
        return this.data[name];
    }
    var file = path.join(this.srcDir, name + '.json');
    if (!fs.existsSync(file)){
        return null;
    }

    var body = fs.readFileSync(file, 'utf8');
    var randNN = randomInt(10, 99);
    body = body.replace(/\{v\#rand_nn\}/g, randNN);

    var json = JSON.parse(body);

    return this.data[name] = json;
};
module.exports = new Data();