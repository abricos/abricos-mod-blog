/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: 'urating', files: ['vote.js']},
        {name: '{C#MODNAME}', files: ['widget.js', 'lib.js']}
	]
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom, L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		NSUR = Brick.mod.urating || {},
		LNG = this.language,
		R = NS.roles;
	
	var TopicManagerWidget = function(container, topic){
		TopicManagerWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'manbtns' 
		}, topic);
	};
	YAHOO.extend(TopicManagerWidget, Brick.mod.widget.Widget, {
		init: function(topic){
			this.topic = topic;
		},
		buildTData: function(topic){
			return {
				'urledit': NS.navigator.topic.edit(topic.id)
			};
		}
	});
	NS.TopicManagerWidget = TopicManagerWidget;

	var TopicInfoLineWidget = function(container, topic, cfg){
		TopicInfoLineWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'info' 
		}, topic, cfg);
	};
	YAHOO.extend(TopicInfoLineWidget, Brick.mod.widget.Widget, {
		init: function(topic, cfg){
			this.topic = topic;
		},
		buildTData: function(topic, cfg){
			var user = topic.user;
			return {
				'date': L.isNull(topic.date) ? LNG.get('topic.draft') : Brick.dateExt.convert(topic.date),
				'uid': user.id,
				'avatar': user.avatar24(),
				'unm': user.getUserName(),
				'cmt': topic.commentCount
			};
		},
		onLoad: function(topic, cfg){
			if (NSUR.VotingWidget){
				this.voteWidget = new NSUR.VotingWidget(this.gel('topicvote'), {
					'modname': '{C#MODNAME}',
					'elementType': 'topic',
					'elementId': topic.id,
					'value': topic.rating,
					'vote': topic.voteMy,
					'onVotingError': function(error, merror){
						
						var s = 'ERROR';
						if (merror > 0){
							s = LNG.get('topic.vote.error.m.'+merror);
						}else if (error == 1){
							s = LNG.get('topic.vote.error.'+error);
						}else{
							return;
						}
						Brick.mod.widget.notice.show(s);						
					}
				});
				this.elShow('topicvote');
			}
		}
	});
	NS.TopicInfoLineWidget = TopicInfoLineWidget;

	
	var TagListWidget = function(container, list){
		TagListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'taglist,tagrow,tagrowcm' 
		}, list);
	};
	YAHOO.extend(TagListWidget, Brick.mod.widget.Widget, {
		init: function(list){
			this.list = list;
		},
		onLoad: function(list){
			var TM = this._TM, alst = [];
			list.foreach(function(tag){
				alst[alst.length] = TM.replace('tagrow', {
					'tl': tag.title,
					'url': tag.url()
				});
			});
			
			this.elSetHTML('list', alst.join(TM.replace('tagrowcm')));
		}
	});
	NS.TagListWidget = TagListWidget;

	
	var TopicRowWidget = function(container, topic){
		TopicRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, topic);
	};
	YAHOO.extend(TopicRowWidget, Brick.mod.widget.Widget, {
		init: function(topic){
			this.topic = topic;
			this.manWidget = null;
		},
		buildTData: function(topic){
			var cat = topic.category();
			return {
				'urlview': topic.url(),
				'urlcat': cat.url()
			};
		},
		destroy: function(){
			this.tagsWidget.destroy();
			this.infoWidget.destroy();
			TopicRowWidget.superclass.destroy.call(this);
		},
		onLoad: function(topic){
			this.tagsWidget = new NS.TagListWidget(this.gel('taglist'), topic.tagList);
			this.infoWidget = new NS.TopicInfoLineWidget(this.gel('info'), topic);
			
			var cat = topic.category();

			this.elSetHTML({
				'intro': topic.intro,
				'tl': topic.title,
				'cattl': !L.isNull(cat) ? cat.title : ''
			});
			
			if (R.topic.isManager(topic)){
				this.manWidget = new NS.TopicManagerWidget(this.gel('man'), topic);
			}
			if (topic.isBody){
				this.elShow('readmore');
			}else{
				this.elHide('readmore');
			}
		}
	});
	NS.TopicRowWidget = TopicRowWidget;
	
	var TopicViewWidget = function(container, topicid){
		TopicViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'topicview' 
		}, topicid);
	};
	YAHOO.extend(TopicViewWidget, Brick.mod.widget.Widget, {
		init: function(topicid){
			this.topicid = topicid;
			this.topic = null;
			this.viewWidget = null;
		},
		destroy: function(){
			if (!L.isNull(this.viewWidget)){
				this.viewWidget.destroy();
			}
		},
		onLoad: function(topicid){
			var __self = this;
			NS.initManager(function(){
				NS.manager.topicLoad(topicid, function(topic){
					__self.renderTopic(topic);
				});
			});
		},
		renderTopic: function(topic){
			this.elHide('loading');
			
			if (L.isNull(topic)){
				this.elShow('nullitem');
				return;
			}

			var widget = this.viewWidget = new NS.TopicRowWidget(this.gel('view'), topic);
			widget.elSetHTML({
				'body': topic.body
			});
			widget.elHide('readmore');
			
			// Инициализировать менеджер комментариев
			Brick.ff('comment', 'comment', function(){
				Brick.mod.comment.API.buildCommentTree({
					'container': widget.gel('comments'),
					'dbContentId': topic.contentid,
					'config': {
						'onLoadComments': function(){
							// aTargetBlank(TM.getEl('panel.drawbody'));
							// aTargetBlank(TM.getEl('panel.comments'));
						}
						// ,
						// 'readOnly': project.w*1 == 0,
						// 'manBlock': L.isFunction(config['buildManBlock']) ? config.buildManBlock() : null
					},
					'instanceCallback': function(b){ }
				});
			});
		}
	});
	NS.TopicViewWidget = TopicViewWidget;
	
	var TopicListWidget = function(container){
		var args = arguments, cfg = {};

		if (L.isObject(args[1])){
			cfg = args[1];
		}else if (args.length > 1){
			var af = [];
			for (var i=1;i<args.length;i++){
				if (L.isString(args[i])){
					af[af.length] = args[i];
				}
			}
			cfg = {
				'filter': af.join("/")
			};
		}
		
		cfg = L.merge({
			'page': 1,
			'filter': '',
			'onLoadCallback': null,
			'list': null
		}, cfg || {});
		
		TopicListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'topiclist' 
		}, cfg);
	};
	YAHOO.extend(TopicListWidget, Brick.mod.widget.Widget, {
		init: function(cfg){
			this.cfg = cfg;
			this.catid = 0;
			this.wsList = [];
			this.next = null;
		},
		onLoad: function(cfg){
			var __self = this;
			NS.initManager(function(){
				NS.manager.topicListLoad(cfg, function(list){
					__self.onLoadManager(list);
				});
			});
		},
		destroy: function(){
			this.clearList();
			if (!L.isNull(this.next)){
				this.next.destroy();
			}
		},
		clearList: function(){
			var ws = this.wsList;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this.elSetHTML('list', '');
		},
		onLoadManager: function(list){
			NS.life(this.cfg['onLoadCallback'], list);
			
			this.renderList(list);
			if (L.isNull(list)){ return; }
			
			var __self = this, cfg = this.cfg;
			this.next = new NS.NextWidget(this.gel('next'), {
				'limit': list.limit,
				'loaded': list.count(),
				'total': list.total,
				'nextCallback': function(page, callback){
					cfg['page'] = page;
					cfg['list'] = list;
					NS.manager.topicListLoad(cfg, function(nlist){
						NS.life(callback, {
							'loaded': nlist.count(),
							'total': nlist.total,
						});
						__self.renderList(nlist);
					});
				}
			});
		},
		renderList: function(list, isClear){
			if (isClear){
				this.clearList();
			}
			this.elHide('loading');
			
			var elList = this.gel('list');
			var ws = this.wsList;

			list.foreach(function(topic){
				
				for (var i=0;i<ws.length;i++){
					if (ws[i].topic.id == topic.id){ return; }
				}
				
				var div = document.createElement('div');
				elList.appendChild(div);
				ws[ws.length] = new NS.TopicRowWidget(div, topic);
			});
			
		}
	});
	NS.TopicListWidget = TopicListWidget;
	
	var TopicHomeListWidget = function(container, f1, f2){
		if (f1 == 'new'){f1 = ''; f2 = 'new';}
		var cfg = {'f1': f1||'', 'f2': f2||''};
		
		TopicHomeListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'homelist' 
		}, cfg);
	};
	YAHOO.extend(TopicHomeListWidget, Brick.mod.widget.Widget, {
		init: function(cfg){
			this.cfg = cfg;

			// использует wspace.js
			this.wsMenuItem = cfg['f1'] == '' ? 'all' : cfg['f1'];
		},
		buildTData: function(cfg){
			var NGT = NS.navigator.topic;
			return {
				'url': NGT.list(),
				'urlnew': NGT.list('new'),
				'urlpub': NGT.list('pub'),
				'urlpubnew': NGT.list('pub', 'new'),
				'urlpers': NGT.list('pers'),
				'urlpersnew': NGT.list('pers', 'new')
			};
		},
		onLoad: function(cfg){
			var __self = this, f1 = cfg['f1'], f2 = cfg['f2'];
			var filter = f1+"/"+f2;
			if (f1 == '' && f2 == 'new'){
				filter = 'new';
			}
			
			this.listWidget = new NS.TopicListWidget(this.gel('list'), {
				'filter': filter,
				'onLoadCallback': function(list){
					__self.onLoadTopics(list);
				}
			});
		},
		onLoadTopics: function(list){
			this.elHide('loading');
			this.elShow('view');
			

			var cfg = this.cfg;
			
			this.elShow('sm'+cfg['f1']);
			
			Dom.addClass(this.gel('smi'+cfg['f1']+cfg['f2']), 'sel');
			
			var sn = "";
			if (list.totalNew>0){
				sn = "+"+list.totalNew;
			}

			this.elSetHTML('smi'+cfg['f1']+'newb', sn);
		}
	});
	NS.TopicHomeListWidget = TopicHomeListWidget;

};