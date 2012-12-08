/**
 * @link http://habrahabr.ru/blogs/javascript/116852/
 * @link https://github.com/theshock/console-cap
 */
(function () {
   var global = this;
   var original = global.console;
   var console = global.console = {};
   console.production = false;
   
   if (original && !original.time) {
	  original.time = function(name, reset){
		 if (!name) return;
		 var time = new Date().getTime();
		 if (!console.timeCounters) console.timeCounters = {};
		 
		 var key = "KEY" + name.toString();
		 if(!reset && console.timeCounters[key]) return;
		 console.timeCounters[key] = time;
	  };

	  original.timeEnd = function(name){
		 var time = new Date().getTime();
			
		 if(!console.timeCounters){
			 return;
		 }
		 
		 var key = "KEY" + name.toString();
		 var timeCounter = console.timeCounters[key];
		 
		 if (timeCounter) {
			var diff = time - timeCounter;
			var label = name + ": " + diff + "ms";
			console.info(label);
			delete console.timeCounters[key];
		 }
		 return diff;
	  };
   }
   
   var methods = ['assert', 'count', 'debug', 'dir', 'dirxml', 'error', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log', 'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd', 'trace', 'warn'];
   
   for (var i = methods.length; i--;) {
	  (function (methodName) {
		 console[methodName] = function () {
			if (original && (methodName in original) && !console.production) {
				try{
					original[methodName].apply(original, arguments);
				}catch(e){
					alert(arguments);
				}
			}
		 };
	  })(methods[i]);
   }
})();
;

(function($) {
	$.installer = {
			options: {
				'updateStateInterval':2000,/*ms*/
				'updateStateErrorInterval':6000,/*ms*/
				'queue':[],
				'install':false,
				'logMode':'raw',/*raw|apps*/
				'end':null
			},
			timeout:{
				'state':null
			},
			counter:0,
			timestamp:0,
			complete: null,
			init: function (options) {
				this.trace('init');
				this.options = $.extend({}, this.options, options||{});
				var self = this;
				
				/*prepare templates*/
				/*
				 * @todo move it into separate plugin
				 */
				var pattern = /<\\\/(\w+)/g;
				var replace_pattern = '<\/$1';
				
				$( "script[type$='x-jquery-tmpl']" ).each( function (i) {
					var template_id=$(this).attr('id').replace(/-template-js$/,'');
					self.trace('compile template '+template_id);
					try{
						$.template(template_id,$(this).html().replace(pattern,replace_pattern));
					}catch(e){
						console.error(e);
					}
				});
				if(this.options.queue.length){
					this.execute('update',this.options.queue);
				}else{
					this.execute('state', null);
				}
				$('body').addClass('i-fixed-body');
				this.onResize();
				$(window).resize(function(){self.onResize();});
			},
			
			onResize: function() {
				setInterval(function(){
					$('.content .i-app-update-screen').css('max-height',(parseInt($('#wa').css('height'))-110)+'px');
				},500);
			},		
		
			execute: function (actionName, attr) {
				actionName = actionName||'default';
				this.trace('execute action '+actionName,attr);
				if (this[actionName + 'Action']) {
					this.currentAction = actionName;
					this.currentActionAttr = attr;
					try{
						return this[actionName + 'Action'](attr);
					}catch(e){
						console.error('Exception while execute '+actionName+'Action',e);	
					}
				} else {
					console.error('Invalid action name', actionName+'Action');
				}
			},
			
			defaultAction: function () {
				;
			},
			
			stateAction: function() {
				var url = '?module=update&action=state';
				var self = this;
				try{
					this.sendRequest(url, {
						'mode':this.options.logMode
						}, 
						function(data){ 
							self.updateStateHandler(data);
						},
						function(data){
							self.updateStateErrorHandler(data);
					});
				} catch(e) {
					console.error('Exception while execute stateAction',e);	
					this.execute('state', null);
				}
			},
			
			updateAction: function(apps) {
				var url = '?module=update&action=execute';
				var params = {'app_id':apps,'mode':this.options.logMode,'install':this.options.install?'1':'0'};
				var self = this;
				this.sendRequest(url, 
						params, 
						function(data){try{self.updateExecuteHandler(data);}catch(e){console.error('Exception while execute updateExecuteHandler',e);}}, 
						function(data){try{self.updateExecuteErrorHandler(data);}catch(e){console.error('Exception while execute updateExecuteErrorHandler',e);}},
						function(jqXHR, settings){
							self.timeout.state = setTimeout(function(){
									self.execute('state', null);
								},Math.max(2000,self.options.updateStateInterval*4));
							}
				);
				setTimeout(function(){$("#wa-app-installer a span.indicator").remove();},500);
			},
			
			updateExecuteHandler: function(data) {
				this.trace('updateExecuteHandler', data);
				if(this.timeout.state){
					clearTimeout(this.timeout.state);
				}
				var complete = {
					'success':0,
					'success_plural':0,
					'fail':0,
					'fail_plural':0
				};
				var complete_result = {};
				var state = false;
				var subject = 'generic';
				var matches;
				if(!data) {
					return;
				}
				
				this.complete = true;
				if(data.sources) {
					for(id in data.sources) {
						if(data.sources[id].target.match(/^wa-apps/)){
							
							if(matches = data.sources[id].target.match(/^wa-apps\/\w+\/(\w+)/)){
								subject='app_'+matches[1];
								/*it's extras*/
							}else {
								subject='apps';
								/*it's apps*/
							}
							if(!complete_result[subject]){
								complete_result[subject] = {'success':0,'fail':0,'plural':null};
							}
								
							if(data.sources[id].skipped){
								++complete.fail;
								state = state||'no';
								++complete_result[subject].fail;
							}else{
								++complete.success;
								state = state||'yes';
								++complete_result[subject].success;
							}
						}
					}
				}
				var n;
				n = complete.success;
				complete.success_plural=((n%10==1 && n%100!=11) ? 0 : ((n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20)) ? 1 : 2));
				n = complete.fail;
				complete.fail_plural=((n%10==1 && n%100!=11) ? 0 : ((n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20)) ? 1 : 2)); 
				state = state||'no';

				this.drawStateInfo(data.state,state);
				setTimeout(function(){
					$.tmpl('application-update-result',{'current_state':data.current_state,'result':complete,'sources':data.sources}).appendTo('#update-raw');
					
					/*$('#menu-item-selected-state-icon').attr('class',$('#update-raw-state-icon').attr('class'));*/
					setTimeout(function(){
						var targetOffset = $('div.i-app-update-screen :last').offset().top;
						$('div.i-app-update-screen').scrollTop(targetOffset);
						$('#update-result-apps li').each(function(index){
							$(this).parent().show();
							var position = $(this).offset();
							
							var target = null;
							var insert_last = true;
							var item_edition = $('#wa-applist ul li[id^='+$(this).attr('id')+']');
							if(item_edition.length) {
								target = item_edition.offset();
							} else {
								if(insert_last) {
									target = $('#wa-applist ul li #wa-moreapps').offset();
									if(!target.left) {
										target = $('#wa-applist ul li[id^=wa-app-]:last').offset();
										target.left = target.left+75;
									}
								}else {
									target = $('#wa-applist ul li[id^=wa-app-]:first').offset();
								}
							}
							var animate_params = {
									'left':target.left,
									'top':target.top/*,
									'width':$(this).find('img').width()+'px',
									'height':$(this).find('img').height()+'px'*/
									
							};
							var css_params = {
									'top':position.top,
									'left':position.left,
									'position':'absolute',
									'display':'inline-block'/*
									'width':'0px',
									'height':'0px',
									'overflow':'hidden',
									'color':'transparent'*/
							};
							var css_params_complete = {
									'top':0,
									'left':0,
									'position':'relative',
									'display':'inline-block'/*,
									'width':$(this).width()+'px',
									'height':$(this).height()+'px'
									'color':$(this).css('color')*/
								};
							
							$(this).css(css_params);
							/*$(this).find('a').css('color','transparent');*/
							var element = $(this);
							$(this).animate(animate_params,700,function(){
	
								element.css(css_params_complete);
								/*element.find('a').css('color',null);*/
								if(item_edition.length){
									item_edition.replaceWith(element);
								} else {
									if(insert_last) {
										element.appendTo('#wa-applist ul');
									}else {
										element.prependTo('#wa-applist ul');/*.effect('highlight',{},10000);*/
									}
								}
								$(window).resize();
							});
						});
					},500);
				},500);
			},
			
			updateExecuteErrorHandler: function(data) {
				this.trace('updateExecuteErrorHandler', data);
				/*
				 * TODO handle errors and try to restart action if it possible
				 */
			},
			
			updateStateHandler: function(data) {
				this.trace('stateHandler', data);
				if(this.timeout.state||this.complete){
					clearTimeout(this.timeout.state);
				}
				
				if(!this.complete){
					/*update/add stage info*/
					
					if(data.current_state && (
							(data.current_state.stage_status == 'error') || 
							(data.current_state.stage_status=='complete' 
								&&data.current_state.stage_name=='update' 
								&&(parseInt(data.current_state.stage_elapsed_time)>3)
						))){
						var complete = 'yes';
						if(data.current_state.stage_status == 'error'){
							complete = 'no';
						}
						this.drawStateInfo(data.state,complete);
						$.tmpl('application-update-result',{'current_state':data.current_state,'result':null}).appendTo('#update-raw');
					}else if(data.current_state && data.state){
						this.drawStateInfo(data.state);
						var self = this;
						this.timeout.state = setTimeout(function(){if(!self.complete){self.execute('state', null);};},this.options.updateStateInterval);
					}
				}
			},
			
			updateStateErrorHandler: function(data) {
				this.trace('StateErrorHandler', data);
				if(this.timeout.state){
					clearTimeout(this.timeout.state);
				}
				var self = this;
				this.timeout.state = setTimeout(function(){if(!self.complete){self.execute('state', null);};},this.options.updateStateErrorInterval);
			},
			
			drawStateInfo: function(state,state_class) {
				/**
				 * @todo check timestamp
				 */
				var target = '#template-placeholder';
				state_class = state_class||'loading';
				switch(this.options.logMode){
					case 'raw':{
						if(state && state.length) {
							for(id in state){
								if(!state[id]['datetime']){
									state[id]['datetime'] = new Date(parseInt(state[id]['stage_start_time'])*1000);
								}
							}
							var html = $(target).html();
							try {
								$(target).empty();
								/*/$('#update-raw').remove();*/
								$.tmpl('application-update-raw',{'stages':state,'apps':this.options.queue,'state_class':state_class}).appendTo(target);
							}catch(e) {
								console.error('Error while parse template ', e);
								$(target).html(html);
							}
						}
						break;
					}
					case 'apps':
					default:{
						var html = $(target).html();
						try {
							$(target).empty();
							for(app_id in state){
								for(id in state[app_id]){
									if(!state[app_id][id]['datetime']){
										state[app_id][id]['datetime'] = new Date(parseInt(state[app_id][id]['stage_start_time'])*1000);
									}
								}
								var d = new Date(parseInt(state[app_id][1]['stage_start_time'])*1000);
								$.tmpl('application-update-apps',{'slug':app_id,'timestamp':d,'stages':state[app_id],'state_class':state_class}).appendTo(target);
							}
						}catch(e) {
							console.error('Error while parse template ', e);
							$(target).html(html);
						}
						break;
					}
					
				}

				setTimeout(function(){
					var targetOffset = $('div.i-app-update-screen :last').offset().top;
					$('div.i-app-update-screen').scrollTop(targetOffset);
					$("#wa-app-installer a span.indicator").remove();
					
				},100);
			},
			
			trace: function(stage, data){
				/*
				 * TODO
				 */
				;
			},
			
			sendRequest: function(url,request_data,success_handler,error_handler,before_send_handler) {
				var self = this;
				var timestamp = new Date();
				$.ajax({
					'url':url+'&timestamp='+timestamp.getTime(),
					'data':request_data,
					'type':'GET',
					'dataType':'json',
					'success': function (data, textStatus, XMLHttpRequest) {
						try{
							try{
								if(typeof(data) != 'object') {
									data = $.parseJSON(data);
								}
							}catch(e){
								console.error('Invalid server JSON response', e);
								if(typeof(error_handler) == 'function'){
									error_handler();
								}
								throw e;
							}
							if(data){
								switch(data.status){
									case 'fail':{
										self.displayMessage(data.errors.error||data.errors, 'error');
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
										console.error('unknown status response', data.status);
										if(typeof(error_handler) == 'function'){
											error_handler(data);
										}
										break;
									}
								}
							}else{
								console.error('empty response', textStatus);
								if(typeof(error_handler) == 'function'){
									error_handler();
								}
								self.displayMessage('Empty server response', 'warning');
							}
						}catch(e){
							console.error('Error handling server response ', e);
							if(typeof(error_handler) == 'function'){
								error_handler(data);
							}
							self.displayMessage('Invalid server response'+'<br>'+e.description, 'error');
						}
						
					},
					'error': function (XMLHttpRequest, textStatus, errorThrown) {
						console.error('AJAX request error', textStatus);
						if(typeof(error_handler) == 'function'){
							error_handler();
						}
						self.displayMessage('AJAX request error', 'warning');
					},
					'beforeSend':before_send_handler
				});
			},
			displayMessage: function(message,type) {
				;
			}
	}
})(jQuery, this);
