$(function () {
	$(window).resize(function() {
		var i = parseInt(($('#wa-applist ul').width() - 1) / 75);
		if (i-- < $('#wa-applist li[id!=""]').length) {
			if ($("#wa-moreapps i").hasClass('darr') && $('#wa-applist li:eq('+i+')').attr('id')) {
				$('#wa-moreapps').show().parent().insertAfter($('#wa-applist li[id!=""]:eq(' + (i - 1) + ')'));
			}
		} else if ($('#wa-applist li:last').attr('id')) {
			$('#wa-moreapps').hide().parent().insertAfter($('#wa-applist li:last'));
		} else {
			if ($('#wa-moreapps i').hasClass('uarr')) {
				$('#wa-header').css('height', '83px');
				$('#wa-moreapps i').removeClass('uarr').addClass('darr');
			}
			$('#wa-moreapps').hide();
		}
		
		/*
		if ($("#wa-applist ul>li").length * 75 > $('#wa-applist').width()) {
			$('#wa-moreapps').show();
		} else {
			$('#wa-moreapps').hide();
		}
		*/
	}).resize();
	
	var sortableApps = function () {
		$("#wa-applist ul").sortable({
			distance: 5,
			helper: 'clone',
			items: 'li[id!=""]',
			opacity: 0.75,
			tolerance: 'pointer', 
			stop: function () {
			var data = $(this).sortable("toArray");
			var apps = [];
			for (var i = 0; i < data.length; i++) {
				var id = data[i].replace(/wa-app-/, '');
				if (id) {
					apps.push(id);
				}
			}
			var url = backend_url + "?module=settings&action=save";
			$.post(url, {name: 'apps', value: apps}); 
		}});
	}
	
	if ($("#wa-applist ul").sortable) {
		sortableApps();
	} else {
		var urls = [];
		if (!$.ui) {
			urls.push('jquery.ui.core.min.js');
			urls.push('jquery.ui.widget.min.js');
			urls.push('jquery.ui.mouse.min.js');
		} else if (!$.ui.mouse) {
			urls.push('jquery.ui.mouse.min.js');
		}
		var path = $("#wa-header-js").attr('src').replace(/jquery-wa\/wa.header.js/, 'jquery-ui/');
		var before = $("#wa-header-js").next();
		for (i = 0; i < urls.length; i++) {
			$("#wa-header-js").clone().removeAttr('id').attr('src', path+urls[i]).insertBefore(before);
		}
		$.getScript(path + 'jquery.ui.sortable.min.js', function () {
			sortableApps();
		});
		
	}

	$('#wa-moreapps').click(function() {
		var i = $(this).children('i');
		if (i.hasClass('darr')) {
			if ($('#wa-applist li:last').attr('id')) {
				$('#wa-moreapps').parent().insertAfter($('#wa-applist li:last'));
			}			
			$('#wa-header').css('height', 'auto');
			i.removeClass('darr').addClass('uarr');
		} else {
			$('#wa-header').css('height', '83px');
			i.removeClass('uarr').addClass('darr');
			$(window).resize();
		}
	});
	
	$("a.wa-announcement-close").click(function () {
		var app_id = $(this).attr('rel');
		$(this).next('p').remove();
		$(this).remove();
		var url = backend_url + "?module=settings&action=save";
		$.post(url, {app_id: app_id, name: 'announcement_close', value: 'now()'}); 
		
	});
	
	var updateCount = function () {
		$.ajax({
			url: backend_url + "?action=count", 
			data: {'background_process': 1}, 
			success: function (response) {
				if (response.status == 'ok') {
					for (var app_id in response.data) {
						var n = response.data[app_id];
						if (n > 0) {
							var a = $("#wa-app-" + app_id + " a");
							if (a.find('span.indicator').length) {
								a.find('span.indicator').html(n);
							} else {
								a.append('<span class="indicator">' + n + '</span>')
							}
						} else {
							$("#wa-app-" + app_id + " a span.indicator").remove();
						}
					}
				}
				setTimeout(updateCount, 60000);
			},
			error: function () {
				setTimeout(updateCount, 60000);
			},
			dataType: "json"
		});
	}
	updateCount();
});