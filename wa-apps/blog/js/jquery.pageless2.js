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
		paging_selector: null
	};

	var start = function() {
		$(settings.target + ' .pageless-wrapper').show();
		if(settings.paging_selector) {
			$(settings.paging_selector).hide();
		}
		$(settings.target + ' a.pageless-link').live('click',function(){watch(true);return false;});
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
		var handler = $(settings.target + ' .pageless-wrapper .pageless-link');
		var progress = $(settings.target + ' .pageless-wrapper .pageless-progress');
		if(progress.length) {
			handler.hide();
			progress.show();
		} else {
			handler.replaceWith('<i class="icon16 loading"><!-- icon --></i>'+handler.text());
		}
		loading = true;

		$.get(settings.url, {
			page : currentPage++
		}, function(response, textStatus, jqXHR) {
			$(settings.target + ' .pageless-wrapper').remove();
			$(settings.target).append(response.data?response.data.content:response);
			if (settings.scroll && (typeof (settings.scroll) == 'function')) {
				settings.scroll.apply(this, [ response, settings.target ]);
			}
			loading = false;
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
		var handler = $(settings.target + ' .pageless-wrapper');
		if(handler.length) {
			return $(window).height() - handler.position().top + $container.scrollTop();
		}
		return 0;
	};

	var watch = function(force) {
		if (currentPage >= parseInt(settings.count) + 1) {
			stop.apply(this, []);
		} else if(!loading) {
			if ((force === true) || (distanceToBottom() < settings.bottom_distance) || (distanceFromContent() > settings.content_distance)) {
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
			start.apply(this, []);
		}
	};
})(jQuery);