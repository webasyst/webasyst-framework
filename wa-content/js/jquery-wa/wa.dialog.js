jQuery.fn.waDialog = function (options) {
	options = options || {}
	options = jQuery.extend({
		esc: true,
		content: '<h1>Loading... <i class="icon16 loading"></i></h1>',
		buttons: null,
		url: null,
		
		onLoad: null,
		onCancel: null,
		onSubmit: null
	}, options);
	
	var d = jQuery(this);
	
	if (d.attr('id') && !d.parent().length) {
		$("#" + d.attr('id')).remove();
	}
		
	if (!d.hasClass('dialog')) {
		d = jQuery(
				'<div ' + (d.attr('id') ? 'id = "' + d.attr('id') + '"' : '') + ' class="dialog ' + d.attr('class') + '" style="display: none">'+
				'<div class="dialog-background"></div>'+
				'<div class="dialog-window">'+
					'<div class="dialog-content">'+
						'<div class="dialog-content-indent">'+
							// content goes here
						'</div>'+
					'</div>'+
					'<div class="dialog-buttons">'+
						'<div class="dialog-buttons-gradient">'+
							// buttons go here
						'</div>'+
					'</div>'+
				'</div>'+
			'</div>'
		).appendTo('body');
		d.find('.dialog-buttons-gradient').empty().append(options.buttons);
		d.find('.dialog-content-indent').empty().append(options.content);		
	}
	
	if (!d.parent().length) {
		d.appendTo('body');
	}
	
	d.find('.dialog-buttons .cancel').unbind('click').click(function () {
		if (options.onCancel) {
			options.onCancel.call(d.get(0));
		}
		d.hide();
		return false;
	});
	
	d.show();
	
	if (options.url) {
		jQuery.get(options.url, function (response) {
			d.find('.dialog-content-indent').html(response);
			d.trigger('resize');
			if (options.onLoad) {
				options.onLoad.call(d.get(0));
			}
		});
	}	

	if (options.onSubmit) {
		d.find('form').unbind('submit').submit(options.onSubmit);
	}
	
	d.bind('resize', function () {
		var el = jQuery(this).find('.dialog-window');
		var dw = el.width();
		var dh = el.height();
		
		jQuery("body").css('min-height', dh+'px');
		
		var ww = jQuery(window).width();
		var wh = jQuery(window).height()-60;
	
		//centralize dialog
		var w = (ww-dw)/2 / ww;
		var h = (wh-dh-60)/2 / wh; //60px is the height of .dialog-buttons div
		if (h < 0) h = 0;
		if (w < 0) w = 0;
		
		el.css({
			'left': Math.round(w*100)+'%',
			'top': Math.round(h*100)+'%'
		});
	}).trigger('resize');
	
	if (options.esc) {
		d.bind('esc', function () {
			jQuery(this).hide();
		});
	}
}

jQuery(window).resize(function () {
	jQuery(".dialog:visible").trigger('resize');
});

jQuery(document).keyup(function(e) {
	//all dialogs should be closed when Escape is pressed
	if (e.keyCode == 27) {
		jQuery(".dialog:visible").trigger('esc');
	} 
});