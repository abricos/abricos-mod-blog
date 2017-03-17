'use strict';

var abricosREST = require('abricos-rest');
var API = abricosREST.API;
var randomInt = abricosREST.helper.randomInt;
var async = abricosREST.helper.async;

var Profiles = function(){

    this.admin = null;
    this.guest = null;
    this.user = null;
};

Profiles.prototype.initialize = function(callback){
    var instance = this;
    async.series([
        function(asyncb){
            instance._initAdmin(asyncb);
        },
        function(asyncb){
            instance._initGuest(asyncb);
        },
        function(asyncb){
            instance._initUser(asyncb);
        }
    ], callback);
};

Profiles.prototype._initAdmin = function(callback){
    if (this.admin){
        return callback.call(context || this, null, this.admin);
    }
    this.admin = new API({login: 'admin', password: 'admin'});
    this.admin.module('blog', callback);
};

Profiles.prototype._initGuest = function(callback){
    if (this.guest){
        return callback.call(context || this, null, this.guest);
    }
    this.guest = new API();
    this.guest.module('blog', callback);
};

Profiles.prototype._initUser = function(callback){
    if (this.user){
        return callback.call(context || this, null, this.user);
    }

    var rnd1 = randomInt(100, 999);
    var rnd2 = randomInt(1000, 9999);
    var username = 'BlogU' + rnd1 + '' + rnd2;

    var account = {
        username: username,
        password: 'P' + randomInt(1000, 9999),
        email: username + '@abricos.local',
        userid: 0
    };

    this.admin.modules.user.signUp(account, function(err, result){
        if (err){
            return callback.call(this, err, null);
        }

        this.user = new API({
            login: account.username,
            password: account.password
        });
        this.user.module('blog', callback);
    }, this);
};

module.exports = new Profiles();