'use strict';

var should = require('should');
var fs = require('fs');

var profiles = require('./lib/profiles.js');
var srcData = require('./lib/data.js');

describe('Blogs', function(){

    it('should be get blog list', function(done){
        profiles.admin.modules.blog.blogList(function(err, result){
            console.log(result);
            done();
        });
    });

    it('should be add blog if not exists', function(done){
        var data = srcData.get('blog001');

        profiles.admin.modules.blog.blogAppend(data, function(err, result){
            console.log(result);
            done();
        });
    });
});
