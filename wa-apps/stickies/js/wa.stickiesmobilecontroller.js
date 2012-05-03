(function($) {

	$(function(){
		$.mobile.ajaxEnabled = false;
		$.mobile.hashListeningEnabled = false;
		$.mobile.ajaxLinksEnabled = false;
//		$.mobile.urlHistory.ignoreNextHashChange = true;
	});

	var urlHistory = {
		back : false,
		stack: [],
		addUrl: function(url) {
			if ( url == urlHistory.stack[1] ) {
				urlHistory.stack.shift();
				urlHistory.back = true;
			}
			else {
				urlHistory.stack.unshift(url);
				urlHistory.back = false;
			}

			if (urlHistory.stack.length > 30) {
				urlHistory.stack.pop();
			}
		},
		isBack:function() {
			return urlHistory.back;
		},
		is_first: true,
		isFirst: function() {
			if (this.is_first) {
				this.is_first = false;
				return true;
			}
			else {
				return false;
			}
		}
	};

	$.wa.stickiesmobilecontroller = {
			options: {
				'separator':'/'
			},
			init: function (options) {
				var self = this;
				this.trace('init');
				this.options.escaped_separator = this.options.separator.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/])/g,'\\$1') ;

				$(window).unload( function () { self.checkChanges(); } );

				//prepare templates
				$(document).ready( function() {
					$( function() {
						self.onDomReady();
					});

				});

			},

			dispatch: function (hash) {
				this.trace('dispatch hash',hash);
				if (hash) {
					hash = hash.replace(/^.*#/, '').replace(/\-+/,this.options.separator).split(this.options.separator);
					this.trace('splited hash', hash);
					if (hash[0]) {
						var actionName = "";
						var attrMarker = hash.length;
						for (var i in hash) {
							var h = hash[i];
							if (i < 2) {
								if (i == 0){
									actionName = h;
								} else if(h.match(/[a-z]+/i)) {
									actionName += h.substr(0,1).toUpperCase() + h.substr(1);
								} else {
									attrMarker = i;
									break;
								}
							} else {
								attrMarker = i;
								break;
							}
						}
						var attr = hash.slice(attrMarker);
						this.execute(actionName, attr);

					} else {
						this.execute();
					}
				} else {
					this.execute();
				}
				return false;
			},

			execute: function (actionName, attr) {
				actionName = actionName||'default';
				this.trace('execute action '+actionName,attr);
				if (this[actionName + 'Action']) {
					this.currentAction = actionName;
					this.currentActionAttr = attr;
					try{
						return this[actionName + 'Action'](attr);
					} catch(ex) {
						this.log('Exception', ex.message);
					}
				} else {
					this.log('Invalid action name', actionName+'Action');
				}
			},

			checkChanges: function() {
				var self = this;
				$('.stick-status.nosaved').each( function (i) {
					var id=parseInt($(this).attr('id').match(/\d+$/));
					self.log('force save', id);
					$('#sticky_content_'+id).change();
				});
			},
			defaultAction: function () {
				this.execute('sheets');
			},

			goPage: function(page) {
				if (urlHistory.isFirst()) {
					$.mobile.changePage(page, 'none');
				}
				else {
					$.mobile.changePage(page,'slide', urlHistory.isBack());
				}
			},

			sheetsAction: function () {
				var url = '?module=sheet&action=list';
				var page_id = '#sheets';
				$('body').removeClass();
				if($('#sheets').length){
//					$.mobile.changePage($(page_id),'slide');//, true, true, true);
					this.goPage($(page_id));
					// (to, transition, reverse, changeHash, fromHashChange)
				}else{
//					$.mobile.pageLoading();
					var self = this;
					this.sendRequest(
						url,
						null,
						function (data) {//success
							$(page_id).remove();
							$.tmpl('sheet-list',data).insertAfter('#loading');
//							$.mobile.changePage($(page_id),'fade');//,undefined, true,true);

							setTimeout(function(){
								self.goPage($(page_id));
							}, 500);
							$.mobile.pageLoading(true);
						},
						function() {//fail
							$.mobile.pageLoading(true);
						}

					);
				}
			},

			sheetAction: function(params) {
				this.trace('sheetAction',params);
				var url = '?module=sheet&action=view';
				var sheet_id = parseInt(params[0]);
				var page_id = '#sheet'+this.options.escaped_separator+sheet_id;
				if(params[1]&&(params[1]=='refresh')){
					$(page_id).remove();
				}
				if($(page_id).length){
					this.trace('just change page on',page_id);
//					$.mobile.changePage($(page_id),'slide');//, true, true, true);
					this.goPage($(page_id));
					$('body').addClass($(page_id).data('bg'));
					//transition, reverse, changeHash, fromHashChange
				}else{
//					$.mobile.pageLoading();
					var self = this;
					this.sendRequest(
						url,
						{'sheet_id':sheet_id},
						function (data) {

							$(page_id).remove();
							$.tmpl('sheet',data).insertAfter('#loading');

							$('body').addClass(data.current_sheet.background_id);
							$(page_id).data('bg', data.current_sheet.background_id);

//							self.onload(page_id);
//							$.mobile.changePage($(page_id),'slide');//,undefined, true,true);

							setTimeout(function(){
								self.goPage($(page_id));
							}, 500);

							$.mobile.pageLoading(true);
						},
						function() {
							$.mobile.pageLoading(true);
							//$.mobile.refresh();
						}

					);
				}

			},

			stickyAction: function(params) {
				var url = '?module=sticky&action=view';
				var id = parseInt(params[0]);
				var page_id = '#sticky'+this.options.escaped_separator+id;

				if($(page_id).length){
					this.trace('just change page on',page_id);
//					$.mobile.changePage($(page_id),'slide');//, true, true, true);
					this.goPage($(page_id));
				}else{
//					$.mobile.pageLoading();
					var self = this;
					this.sendRequest(
						url,
						{'id':id},
						function (data) {

							$(page_id).remove();

							$.tmpl('sticky',data).insertAfter('#loading');

							$('body').addClass(data.sheet.background_id);
							$(page_id).data('bg', data.sheet.background_id);

							self.onload(page_id);
							var content = $('#sticky-content-'+id);
							if(content.length&&content.html()){
								content.html(content.html().replace(/\n/g,'<br>'));
							}
//							$.mobile.changePage($(page_id),'slide');//,undefined, true,true);

							setTimeout(function(){
								self.goPage($(page_id));
							}, 500);

//							$.mobile.pageLoading(true);
						},
						function() {
							$.mobile.pageLoading(true);
						}

					);
				}

			},

			sendRequest: function(url,request_data,success_handler,error_handler) {
				var self = this;
				$.ajax({
					'url':url,
					'data':request_data||{},
					'type':'POST',
					'success': function (data, textStatus, XMLHttpRequest) {
						try{
							data = $.parseJSON(data);
						}catch(e){
							self.log('Invalid server JSON responce', e.description);
							if(typeof(error_handler) == 'function'){
								error_handler();
							}
							self.displayNotice('Invalid server responce'.translate()+'<br>'+e, 'error');
						}
						if(data){
							switch(data.status){
								case 'fail':{
									self.displayNotice(data.errors.error||data.errors, 'error');
									if(typeof(error_handler) == 'function'){
										error_handler(data);
									}
									break;
								}
								case 'ok':{
									if(typeof(success_handler) == 'function'){
										success_handler(data.data);
									}
									break;
								}
								default: {
									self.log('unknown status responce', data.status);
									if(typeof(error_handler) == 'function'){
										error_handler(data);
									}
									break;
								}
							}
						}else{
							self.log('empty responce', textStatus);
							if(typeof(error_handler) == 'function'){
								error_handler();
							}
							self.displayNotice('Empty server responce'.translate(), 'warning');
						}

					},
					'error': function (XMLHttpRequest, textStatus, errorThrown) {
						self.log('AJAX request error', textStatus + errorThrown);
						if(typeof(error_handler) == 'function'){
							error_handler();
						}
						self.displayNotice('AJAX request error'.translate(), 'warning');
					}
				});
			},

			onDomReady: function() {

				var pattern = /<[\\]+\/(\w+)/g;
				var replace = '</$1';

				$("script[type$='x-jquery-tmpl']").each(function() {
					var id = $(this).attr('id').replace(/-template-js$/, '');
					try {
						var template = $(this).html().replace(pattern, replace);
						$.template(id, template);
					} catch (e) {
						if (typeof(console) == 'object') {
							console.log(e);
						}
					}
				});


				$(window).bind( "hashchange", function( e, triggered ) {
					var h = (parent ? parent.window.location.hash : location.hash)||'#sheets';
					$.wa.stickiesmobilecontroller.trace('hashchange', h);
					urlHistory.addUrl(h);
					$.wa.stickiesmobilecontroller.dispatch(h);

//					$.mobile.hashListeningEnabled = false;
//					$.mobile.urlHistory.ignoreNextHashChange = true;
					e.preventDefault();
					e.stopPropagation();
					//$.mobile.hashListeningEnabled = true;
					//$.mobile.urlHistory.ignoreNextHashChange = false;
				});

				var h = parent ? parent.window.location.hash : location.hash;
//				var sheet_id = Math.max(0,parseInt($.cookie('stickies.current_sheet')));
				if (h.length < 2) {
//					if(sheet_id>0){
//						$.wa.setHash('#sheet'+this.options.separator+sheet_id);
//					}else{
						$.wa.setHash('#sheets');
//					}
				} else {
					$.wa.stickiesmobilecontroller.dispatch(h);
					urlHistory.addUrl('#sheets');
				}
			},

			onload: function(page_id) {
//				if(page_id){
//					$( "a[data-ajax='json'][data-href='"+page_id+"']" ).each(function(index,value){
//						$(this).attr('href',$(this).attr('data-href'));
//						$(this).removeAttr('data-href');
//						$(this).removeAttr('data-ajax');
//					});
//				}
//				$("a[data-ajax='json']").each(function(index,value){
//					if(!$(this).attr('data-href')){
//						$(this).attr('data-href',$(this).attr('href'));
//						$(this).attr('href','#');
//					}
//				});
			},

			displayNotice: function (message,type) {
				var container = $('#wa-system-notice');
				if(container){
					//TODO remove js from message?
					var delay = 1500;
					switch(type){
						case 'error':{
							delay = 6000;
							break;
						}
					}
					container.html(message.replace( /<script[\s\S]*?\/script>/gm,''));
					container.slideDown().delay(delay).slideUp();

				}else{
					alert(message);
				}

			},

			log: function (message, params) {
				if(console){
					console.log(message,params);
				}
			},
			trace: function (message, params) {
				if(console && true){
					console.log(message,params);
				}
			}

	};

})(jQuery, this);
(function($, window, undefined) {
	$.wa.stickiesmobilecontroller.init();
})(jQuery, this);