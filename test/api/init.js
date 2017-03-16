'use strict';

var should = require('should');

var profiles = require('./lib/profiles.js');

describe('Users and App info', function(){

    var methods = [];
    var methodsGuest = methods.concat([]);
    var methodsUser = methods.concat([]);
    var methodsAdmin = methodsUser.concat([]);

    var checkResponse = function(err, result){
        should.not.exist(err);
        should.exist(result);
    };

    var checkUserAppInfo = function(api, methods){
        var module = api.modules.blog;

        should.exist(module.version);
        should.exist(module.structures);
        should.exist(module.methods);

        module.should.have.property('version', 'v1');
        module.methods.should.containDeep(methods);
    };

    it('should be initialize profiles', function(done){
        profiles.initialize(function(err){
            should.not.exist(err);

            checkUserAppInfo(profiles.admin, methodsAdmin);
            checkUserAppInfo(profiles.guest, methodsGuest);
            checkUserAppInfo(profiles.user, methodsUser);
            done();
        })
    });

    it('should be configure users', function(done){
        done();
    });
});
