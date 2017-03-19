'use strict';

var should = require('should');
var fs = require('fs');

var profiles = require('./lib/profiles.js');
var srcData = require('./lib/data.js');

var checkResponse = function(err, result){
    should.not.exist(err);
    should.exist(result);
};

describe('Blogs', function(){

    var blog = srcData.get('blog');
    blog.id = 0;

    it('should be get blog list', function(done){
        profiles.admin.modules.blog.blogList(function(err, result){
            checkResponse(err, result);
            result.list.should.be.an.instanceOf(Array);

            for (var i = 0, dbBlog; i < result.list.length; i++){
                dbBlog = result.list[i];
                if (blog.slug === dbBlog.slug){
                    blog.id = dbBlog;
                }
            }
            done();
        });
    });

    it('should be added blog if not exist', function(done){
        if (blog.id === 0){
            profiles.admin.modules.blog.blogAppend(blog, function(err, result){
                checkResponse(err, result);

                result.should.have.property('blogid');
                result.blogid.should.be.above(0);

                blog.id = result.blogid;

                done();
            });
        } else {
            done();
        }
    });
});
