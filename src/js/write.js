var Component = new Brick.Component();
Component.requires = {
    yahoo: ['autocomplete', 'dragdrop'],
    mod: [
        {name: 'sys', files: ['editor.js', 'panel.js']},
        {name: '{C#MODNAME}', files: ['topic.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.WriteWidget = Y.Base.create('writeWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                wType = this.get('wType'),
                p1 = this.get('p1');

            switch (wType) {
                case 'category':
                    wType = 'category';
                    this.widget = new NS.CategoryEditorWidget(tp.gel('widget'), p1);
                    break;
                case 'draftlist':
                    wType = 'draftlist';
                    this.widget = new NS.TopicListWidget(tp.gel('widget'), {
                        'filter': 'draft'
                    });
                    break;
                default:
                    wType = 'topic';
                    this.widget = new NS.TopicEditorWidget(tp.gel('widget'), p1);
                    break;
            }
        },
        destructor: function(){
            if (this.widget){
                this.widget.destroy();
            }
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            wType: {value: ''},
            p1: {value: ''}
        },
        parseURLParam: function(args){
            return {
                wType: args[0] || '',
                p1: args[1] || '',
            };
        }
    });

    var L = YAHOO.lang,
        R = NS.roles,
        LNG = this.language,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var WriteCategorySelectWidget = function(container, catid){
        WriteCategorySelectWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'catsel,catselrow,catselmyrow'
        }, catid || 0);
    };
    YAHOO.extend(WriteCategorySelectWidget, BW, {
        buildTData: function(catid){
            var TM = this._TM, lst = TM.replace('catselmyrow');
            NS.manager.categoryList.foreach(function(cat){
                if (!R.category.isMember(cat)){
                    return;
                }
                lst += TM.replace('catselrow', {
                    'id': cat.id,
                    'tl': cat.title
                });
            });
            return {'rows': lst};
        },
        onLoad: function(catid){
            this.setValue(catid);
        },
        getValue: function(){
            return this.gel('id').value;
        },
        setValue: function(value){
            this.gel('id').value = value;
        }
    });
    NS.WriteCategorySelectWidget = WriteCategorySelectWidget;

    var TopicEditorWidget = function(container, topicid){
        TopicEditorWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'topic'
        }, topicid || 0);
    };
    YAHOO.extend(TopicEditorWidget, BW, {
        init: function(topicid){
            this.topicid = topicid;
            this.catSelWidget = null;
            this.introEditorWidget = null;
        },
        buildTData: function(topicid){
            return {
                'cledst': topicid > 0 ? 'edstedit' : 'edstnew'
            };
        },
        destroy: function(){
            if (this.introEditorWidget){
                this.introEditorWidget.destroy();
                this.bodyEditorWidget.destroy();
                this.catSelWidget.destroy();
                this.tagManager.destroy();
            }
            TopicEditorWidget.superclass.destroy.call(this);
        },
        onLoad: function(topicid){
            var __self = this;
            NS.initManager(function(){
                if (topicid == 0){
                    __self.onLoadManager(new NS.Topic());
                } else {
                    NS.manager.topicLoad(topicid, function(topic){
                        __self.onLoadManager(topic);
                    });
                }
            });
        },
        onLoadManager: function(topic){
            this.topic = topic;
            this.elHide('loading');
            this.elHide('wrap');

            this.catSelWidget = new NS.WriteCategorySelectWidget(this.gel('catsel'), topic.catid);

            this.tagManager = new TagsAutocomplete(this.gel('tags'), this.gel('tagscont'));

            this.elSetValue({
                'title': topic.title,
                'tags': topic.tagList.toString()
            });

            this.introEditorWidget = new SYS.Editor({
                srcNode: this.gel('intro'),
                content: topic.intro
            });

            this.bodyEditorWidget = new SYS.Editor({
                srcNode: this.gel('body'),
                content: topic.body
            });

            if (R['isAdmin']){
                this.elShow('admindex');

                this.gel('isindex').checked = (topic.isIndex && !topic.isAutoIndex) ? 'checked' : '';
            }
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bpreview']:
                    this.showPreview();
                    return true;
                case tp['bsavedraft']:
                    this.saveDraft();
                    return true;
                case tp['bcreate']:
                case tp['bsave']:
                    this.save();
                    return true;
                case tp['bcancel']:
                    this.cancel();
                    return true;
            }
            return false;
        },
        getSaveData: function(){
            var stags = this.gel('tags').value;

            return {
                'id': this.topic.id,
                'catid': this.catSelWidget.getValue(),
                'tl': this.gel('title').value,
                'tags': NS.TagList.stringToAJAX(stags),
                'intro': this.introEditorWidget.get('content'),
                'body': this.bodyEditorWidget.get('content'),
                'idx': this.gel('isindex').checked ? 1 : 0
            };
        },
        showPreview: function(){
            var __self = this, sd = this.getSaveData();

            this.elHide('btnsblock');
            this.elShow('bloading');

            NS.manager.topicPreview(sd, function(topic){
                __self.elShow('btnsblock');
                __self.elHide('bloading');
                new TopicPreviewPanel({
                    topic: topic
                });
            });
        },
        saveDraft: function(){
            this.save(true);
        },
        save: function(isdraft){
            isdraft = isdraft || false;
            var __self = this;
            var sd = this.getSaveData();

            this.elHide('btnsblock');
            this.elShow('bloading');
            sd['dft'] = isdraft ? 1 : 0;
            NS.manager.topicSave(sd, function(topicid, error){
                __self.elShow('btnsblock');
                __self.elHide('bloading');

                if (L.isNull(error) || topicid == 0){
                    error = L.isNull(error) ? 'null' : error;
                    var sError = LNG.get('write.topic.error.' + error);
                    Brick.mod.widget.notice.show(sError);
                } else {
                    Brick.Page.reload(NS.navigator.topic.view(topicid));
                }
            });
        },
        cancel: function(){
            if (this.topicid == 0){
                Brick.Page.reload(NS.navigator.home());
            } else {
                Brick.Page.reload(NS.navigator.topic.view(this.topicid));
            }
        }
    });
    NS.TopicEditorWidget = TopicEditorWidget;

    NS.TopicPreviewPanel = Y.Base.create('topicPreviewPanel', SYS.Dialog, [], {
        initializer: function(){
            this.publish('editorSaved');
            Y.after(this._syncUIGroupEditorDialog, this, 'syncUI');
        },
        _syncUIGroupEditorDialog: function(){
            var tp = this.template;

            var widget = this.viewWidget =
                new NS.TopicRowWidget(tp.gel('widget'), this.get('topic'));

            widget.elSetHTML({
                'body': topic.body
            });
            widget.elHide('readmore');
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'topicpreview'},
            topic: {value: 0},
        }
    });

    var TagsAutocomplete = function(input, container){
        var ds = new YAHOO.util.XHRDataSource('/ajax/blog/js_tags/');
        ds.connMethodPost = true;
        ds.responseSchema = {recordDelim: "\n", fieldDelim: "\t"};
        ds.responseType = YAHOO.util.XHRDataSource.TYPE_TEXT;
        ds.maxCacheEntries = 60;

        var oAC = new YAHOO.widget.AutoComplete(input, container, ds);
        oAC.delimChar = [",", ";"]; // Enable comma and semi-colon delimiters
    };

    var CategoryEditorWidget = function(container, catid){
        CategoryEditorWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'blog'
        }, catid || 0);
    };
    YAHOO.extend(CategoryEditorWidget, BW, {
        init: function(catid){
            this.catid = catid;
            this.editorWidget = null;
        },
        buildTData: function(catid){
            return {
                'cledst': catid > 0 ? 'edstedit' : 'edstnew'
            };
        },
        destroy: function(){
            if (this.editorWidget){
                this.editorWidget.destroy();
            }
            CategoryEditorWidget.superclass.destroy.call(this);
        },
        onLoad: function(catid){
            var __self = this;
            NS.initManager(function(){
                if (catid == 0){
                    __self.onLoadManager(new NS.Category());
                } else {
                    var cat = NS.manager.categoryList.get(catid);
                    __self.onLoadManager(cat);
                }
            });
        },
        onLoadManager: function(cat){
            this.cat = cat;
            this.elHide('loading');
            this.elHide('wrap');

            this.editorWidget = new SYS.Editor({
                srcNode: this.gel('text'),
                content: cat.descript,
                separateIntro: false
            });

            this.elSetValue({
                'title': cat.title,
                'name': cat.name,
                'rep': cat.reputation
            });

            if (NS.isURating){
                this.elShow('repblock');
            }
            if (R['isAdmin']){
                this.elShow('name');
            }
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bcancel']:
                    this.cancel();
                    return true;
                case tp['bcreate']:
                case tp['bsave']:
                    this.save();
                    return true;
            }
            return false;
        },
        getSaveData: function(){
            return {
                'id': this.cat.id,
                'tl': this.gel('title').value,
                'nm': this.gel('name').value,
                'dsc': this.editorWidget.get('content'),
                'rep': this.gel('rep').value
            };
        },
        cancel: function(){
            if (this.cat.id > 0){
                Brick.Page.reload(NS.navigator.category.view(this.cat.id));
            } else {
                Brick.Page.reload(NS.navigator.home());
            }
        },
        save: function(){
            var __self = this;
            this.elHide('btnsblock');
            this.elShow('bloading');
            var sd = this.getSaveData();
            NS.manager.categorySave(sd, function(catid, error){
                __self.elShow('btnsblock');
                __self.elHide('bloading');

                if (L.isNull(error) || catid == 0){
                    error = L.isNull(error) ? 'null' : error;
                    var sError = LNG.get('write.category.error.' + error);
                    Brick.mod.widget.notice.show(sError);
                } else {
                    Brick.Page.reload(NS.navigator.category.view(catid));
                }
            });
        }

    });
    NS.CategoryEditorWidget = CategoryEditorWidget;
};