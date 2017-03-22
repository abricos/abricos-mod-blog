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

    it('should be getting blog list (GET:/api/blog/v1/blogList)', function(done){
        profiles.admin.modules.blog.blogList(function(err, result){
            checkResponse(err, result);
            result.list.should.be.an.instanceOf(Array);

            for (var i = 0, dbBlog; i < result.list.length; i++){
                dbBlog = result.list[i];
                if (blog.slug === dbBlog.slug){
                    blog.id = dbBlog.id;
                }
            }
            done();
        });
    });

    it('should be added blog if not exist (POST:/api/blog/v1/blogAppend)', function(done){
        if (blog.id > 0){
            done();
            return;
        }
        var data = {
            title: blog.title,
            slug: blog.slug,
            descript: blog.descript,
        };
        profiles.admin.modules.blog.blogAppend(data, function(err, result){
            checkResponse(err, result);

            result.should.have.property('blogid');
            result.blogid.should.be.above(0);

            blog.id = result.blogid;

            done();
        });
    });

    it('should be updated blog (POST:/api/blog/v1/blogUpdate)', function(done){
        blog.title += ' Changes';

        var data = {
            blogid: blog.id,
            title: blog.title,
            slug: blog.slug,
            descript: blog.descript,
        };
        profiles.admin.modules.blog.blogUpdate(data, function(err, result){
            checkResponse(err, result);
            result.should.have.property('blogid', blog.id);
            done();
        });
    });

    it('should be getting blog by ID (GET:/api/blog/v1/blog/:id)', function(done){
        profiles.admin.modules.blog.blog(blog.id, function(err, result){
            checkResponse(err, result);
            result.should.have.property('id', blog.id);
            result.should.have.property('title', blog.title);
            done();
        });
    });

    it('should be getting blog by Slug (GET:/api/blog/v1/blogBySlug/:slug)', function(done){
        profiles.admin.modules.blog.blogBySlug(blog.slug, function(err, result){
            checkResponse(err, result);
            result.should.have.property('id', blog.id);
            result.should.have.property('slug', blog.slug);
            done();
        });
    });

    var blogTemp = srcData.get('blogTemp');

    it('should be added temp blog (POST:/api/blog/v1/blogAppend)', function(done){
        var data = {
            title: blogTemp.title,
            slug: blogTemp.slug,
            descript: blogTemp.descript,
        };
        profiles.admin.modules.blog.blogAppend(data, function(err, result){
            checkResponse(err, result);

            result.should.have.property('blogid');
            result.blogid.should.be.above(0);

            blog.id = result.blogid;

            done();
        });
    });

    it('should be removed temp blog (POST:/api/blog/v1/blogRemove)', function(done){
        var data = {
            blogid: blogTemp.id
        };
        profiles.admin.modules.blog.blogRemove(data, function(err, result){
            checkResponse(err, result);
            result.should.have.property('blogid', blogTemp.id);
            done();
        });
    });
});
