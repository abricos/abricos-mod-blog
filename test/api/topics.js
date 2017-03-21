'use strict';

var should = require('should');
var fs = require('fs');

var profiles = require('./lib/profiles.js');
var srcData = require('./lib/data.js');

var checkResponse = function(err, result){
    should.not.exist(err);
    should.exist(result);
};

describe('Topics', function(){

    var topic = srcData.get('topic');

    it('should be added topic in blog (POST:/api/blog/v1/topicAppend)', function(done){
        done();
    });

    it('should be updated topic (POST:/api/blog/v1/topicUpdate)', function(done){
        done();
    });

    it('should be getting blog by ID (GET:/api/blog/v1/topic/:id)', function(done){
        done();
    });

    it('should be added temp topic (POST:/api/blog/v1/topicAppend)', function(done){
        done();
    });

    it('should be removed temp topic (POST:/api/blog/v1/topicRemove)', function(done){
        done();
    });
});
