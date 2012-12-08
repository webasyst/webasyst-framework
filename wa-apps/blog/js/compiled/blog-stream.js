(function($) {
	var currentPage = 2, container = window, $container = $(container);
	var loading = false;

	var settings = {
		url : '',
		target : '.b-stream',
		count : 10,
		scroll : null,
		stop : null,
		bottom_distance: 80,
		content_distance: 120,
		auto: true,
		beforeLoad: null,
        afterLoad: null,
        renderContent: null,
		paging_selector: null,
        pageless_wrapper: null
	};

	var start = function() {
	    var pageless_wrapper = settings.pageless_wrapper;
	    
	    pageless_wrapper.show();
		if(settings.paging_selector) {
			$(settings.paging_selector).hide();
		}
        pageless_wrapper.find('a.pageless-link').live('click', function() { watch(true); return false; });
		$container.bind('scroll.pageless resize.pageless', watch).trigger('scroll.pageless');

	};

	var stop = function() {
		$container.unbind('.pageless');
		if (settings.stop && (typeof (settings.stop) == 'function')) {
			settings.stop.apply(this, []);
		}

	};

	var scroll = function() {
		// show loader
        var pageless_wrapper = settings.pageless_wrapper;
		var handler  = pageless_wrapper.find('.pageless-link');
		var progress = pageless_wrapper.find('.pageless-progress');
		if(progress.length) {
			handler.hide();
			progress.show();
		} else {
			handler.replaceWith('<i class="icon16 loading"></i>'+handler.text());
		}
		loading = true;

		if (typeof settings.beforeLoad === 'function') {
		    settings.beforeLoad();
		}
		$.get(settings.url, {
			page : currentPage++
		}, function(response, textStatus, jqXHR) {
		    var html = response.data?response.data.content:response;
            if (typeof settings.renderContent === 'function') {
                settings.renderContent(html, $(settings.target));
            } else {
                pageless_wrapper.remove();
                $(settings.target).append(html);
                pageless_wrapper = settings.pageless_wrapper = $(settings.target + ' .pageless-wrapper');
            }
			if (settings.scroll && (typeof (settings.scroll) == 'function')) {
				settings.scroll.apply(this, [ response, settings.target ]);
			}
			loading = false;
            if (typeof settings.afterLoad === 'function') {
                settings.afterLoad();
            }
			watch();
		});// ,'html');
	};
	// distance to end of the container
	var distanceToBottom = function() {
		return (container === window) ? $(document).height()
				- $container.scrollTop() - $container.height()
				: $container[0].scrollHeight - $container.scrollTop()
						- $container.height();
	};

	var distanceFromContent = function() {
	    var handler = settings.pageless_wrapper;
		if(handler.length) {
			return $(window).height() - handler.position().top + $container.scrollTop();
		}
		return 0;
	};

	var watch = function(force) {
		if (currentPage >= parseInt(settings.count) + 1) {
			stop.apply(this, []);
		} else if(!loading) {
		    var apply = false;
		    if(force === true) {
		        apply = true;
		    } else if(settings.auto){
		        apply = (distanceToBottom() < settings.bottom_distance) || (distanceFromContent() > settings.content_distance);
		    }

			if (apply) {
				scroll.apply(this, []);
			}
		}
	};

	$.pageless = function(option) {
		if (option == 'start' || option == 'refresh'){
			start.apply(this, []);
		}
		if (option == 'url') {
			settings.url = arguments[1];
		}
		if ($.isPlainObject(option)) {
			$.extend(settings, option);
			if (settings.pageless_wrapper === null) {
			    settings.pageless_wrapper = $(settings.target + ' .pageless-wrapper');
			} else if (settings.pageless_wrapper === false) {
			    settings.pageless_wrapper = $();
			}
			start.apply(this, []);
		}
	};
})(jQuery);;
(function($) {
	$.wa_blog.stream = {
		management: false,
		options : {
			pageless:{}
		},
		init : function(options) {
			this.options = $.extend(this.options, options);
			var self = this;

			$('a[href=\#manage]').click(function(eventObject){
				return self.manageHandler.apply(self,[this,eventObject]);
			});
			$('.js-manage-done').click(function(eventObject){
				return self.manageCompleteHandler.apply(self,[this,eventObject]);
			});
			$('input.search').keydown(function(eventObject) {
			    if (eventObject.keyCode == 13) {
			        var query = $(this).val(),
			            match = location.search.match(/[&\?](text=.*?&|text=.*)/); 
			        if (match) {
			            var text = match[1];
			            if (query) {
			                var new_text = text.substr(-1) == '&' ? 'text='+encodeURIComponent(query)+'&' : 'text='+encodeURIComponent(query);
			                location.search = location.search.replace(text, new_text);
			            } else {
			                if (text.substr(-1) != '&') {
			                    text = '[&\?]' + text;
			                }
			                location.search = location.search.replace(new RegExp(text), '');
			            }
			        } else if (query) {
		                location.search += (location.search ? '&' : '?') + 'text=' + encodeURIComponent(query);
			        }
			        return false;
			    }
			});
			$('input.blog-post-checkbox').live('click',function(eventObject){
				return self.counterHandler.apply(self,[this,eventObject]);
			});

            $('#postdelete-dialog input[type=button]').click(function(eventObject){
                return self.deleteHandler.apply(self,[this,eventObject]);
            });

            $('#postmove-dialog input[type="button"]').click(function(eventObject){
                return self.moveHandler.apply(self,[this,eventObject]);
            });

			var pageless_options = {
				scroll:function() {
					$.wa_blog.common.onContentUpdate();
			}};
			pageless_options = $.extend(true,pageless_options,self.options.pageless);
			$.pageless(pageless_options);
		},
		manageHandler : function(element, event) {
			$('#blog-stream-primary-menu').hide();
			$('#blog-stream-manage-menu').show();
			this.management = true;
			this.onContentUpdate();
			return false;
		},
		manageCompleteHandler: function(element, event) {
			this.management = false;
			$('#blog-stream-manage-menu').hide();
			$('#blog-stream-primary-menu').show();
			$('.b-post.js-managed').each(function(){
				$(this).removeClass('js-managed');
				$(this).find('h3:hidden').show();
				$(this).find('h3:first').hide();
                $(this).find('.b-post-body:hidden, .profile.image20px:hidden').fadeIn();
			});
			return false;
		},
		counterHandler: function() {
			$('.js-blog-selected-posts-counter').text($('input.blog-post-checkbox:checked').length);
		},
		moveHandler : function(element, event) {
            var ids = new Array();
            $('input.blog-post-checkbox:checked').each(function(){
                ids.push($(this).val());
            });
            var blog_id = $('#postmove-dialog :input[name=blog_id]').val();
            if(ids.length) {
                $(element).attr('disabled', true).after('<i class="icon16 loading"></i>');
                $.ajax({
                    url : '?module=post&action=move',
                    data: {id:ids,blog:blog_id},
                    type: 'post',
                    dataType : 'json',
                    success : function(response) {
                        var moved = false;
                        if(response.status == 'ok') {
                            for (var i in response.data.moved) {
                                var post = $('#b-post-'+response.data.moved[i]);
                                if(post.length) {
                                    moved = true;
                                    post.animate({
                                            opacity: 0.1,
                                            height: 0
                                        }, 200,function(){post.remove();});
                                }
                            }
                        }
                        $.wa_blog.stream.counterHandler();
                        if(moved) {
                            window.location.reload();
                        } else {
                            $('#postmove-dialog .icon16.loading').remove();
                            $('#postmove-dialog input[type=button]:disabled').removeAttr('disabled');
                            $.wa_blog.dialogs.close('postmove');
                        }
                    },
                    error: function(response) {
                        $('#postmove-dialog .icon16.loading').remove();
                        $('#postmove-dialog input[type=button]:disabled').removeAttr('disabled');
                        $.wa_blog.dialogs.close('postmove');
                    }
                });
            } else {
                $.wa_blog.dialogs.close('postmove');
            }
		},
		deleteHandler : function(element, event) {
			var ids = new Array();
			$('input.blog-post-checkbox:checked').each(function(){
                ids.push($(this).val());
			});
			if(ids.length) {
				$(element).attr('disabled', true).after('<i class="icon16 loading"></i>');
    			$.ajax({
                    url : '?module=post&action=delete',
                    data: {id:ids},
                    type: 'post',
                    dataType : 'json',
                    success : function(response) {
                    	if(response.status == 'ok' && response.data.deleted) {
                    		window.location.reload();
                    	} else {
                    		$('#postdelete-dialog .icon16.loading').remove();
                            $('#postdelete-dialog input[type=button]:disabled').removeAttr('disabled');
                            $.wa_blog.dialogs.close('postdelete');
                            $.wa_blog.stream.counterHandler();
                    	}
                    },
                    error: function(response) {
                    	$('#postdelete-dialog .icon16.loading').remove();
                        $('#postdelete-dialog input[type=button]:disabled').removeAttr('disabled');
                        $.wa_blog.dialogs.close('postdelete');
                        $.wa_blog.stream.counterHandler();
                    }
                });
			} else {

                $.wa_blog.dialogs.close('postdelete');
			}
		},
		onContentUpdate: function() {
			if(this.management) {
				var self = this;
				var collapsed = false;
				$('.b-post-body:visible, .profile.image20px:visible').hide();
				$('.b-post').each(function() {
					if (!$(this).hasClass('js-managed')) {
						$(this).find('h3:visible').hide();
						$(this).find('h3:first').show();
						collapsed = true;
						$(this).addClass('js-managed');
					}
				});
				if(collapsed) {
					setTimeout(function(){
						$(self.options.pageless.target).trigger('scroll.pageless');
					},100);
				}
			}
		}

	};
})(jQuery);
;
