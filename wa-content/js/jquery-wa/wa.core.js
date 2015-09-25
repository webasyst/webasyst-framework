if ($.ui) {
	$.wa = $.extend(true, $.wa, $.ui);
} else {
	$.wa = {};
}

$.wa = $.extend(true, $.wa, {
	data: {},
	get: function(key, defaultValue) {
		if (key == undefined) {
			return this.data;
		}
		return this.data[name] || defaultValue || null;
	},
	set: function(key, val) {
		if (key == undefined) {
			return this.data;
		}
		if (typeof(key) == 'object') {
			$.extend(this.data, key);
		} else {
			this.data[key] = value;
		}
		return this.data;
	},
	encodeHTML: function(html) {
		return html && (''+html).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
	},
	decodeHTML: function(html) {
		return html.replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>');
	},
	setHash: function(hash){
		if (!(hash instanceof String) && hash.toString) {
			hash = hash.toString();
		}
		hash = hash.replace(/\/\//g, "/");
		hash = hash.replace(/^.*#/, '');
		if ($.browser && $.browser.safari) {
			// Work around bug in safari 5.0.5 and down that broke UTF8 hashes
			if (parent) {
				parent.window.location = parent.window.location.href.replace(/#.*/, '') + '#' + hash;
			} else {
				window.location = location.href.replace(/#.*/, '') + '#' + hash;
			}
		} else if (parent && (!$.browser || !$.browser.msie)) {
			parent.window.location.hash = hash;
		} else {
			location.hash = hash;
		}
		return true;
	},
	back: function (hash) {
		if (history.length > 2) {
			if (typeof(hash)=='number' && parseInt(hash) == hash) {
				history.go(-hash);
			} else {
				history.go(-1);
			}
		} else if ($.browser.msie && history.length > 0) {
			history.back();
		} else if (hash) {
			this.setHash(hash);
		}
		return false;
	},
	toggleHashParam: function(param){
		var hash = location.hash;
		if (hash.search(param) == -1){
			this.addToHash(param);
		} else {
			this.removeFromHash(param);
		}
	},
	addToHash: function(param){
		var hash = location.hash;
		if (hash.search(param) == -1){
			hash+='/'+param+'/';
		}
		this.setHash(hash);
	},
	removeFromHash: function(param){
		var hash = location.hash;
		if (hash.search(param) > -1){
			hash = hash.replace(param, "");
		}
		this.setHash(hash);
	},

	setTitle: function (title) {
		document.title = title;
	},
	array_search: function ( needle, haystack, strict ) {
		var strict = !!strict;

		for(var key in haystack){
			if( (strict && haystack[key] === needle) || (!strict && haystack[key] == needle) ){
				return key;
			}
		}
		return false;
	},

	/** Create dialog with given id (or use existing) and set it up according to properties.
		p = {
			content: // content for the dialog to show immediately. Default is a loading image.
			buttons: // html for button area. Defaut is a single 'cancel' link.

			url: ..., // if specified, content will be loaded from given url
			post: { // used with url; contains post parameters.
				var: value
			},
			onload: null // function to call when content is loaded (only when url is specified)
		}
	  */
	dialogCreate: function(id, p) {
		p = $.extend({
				content: '<h1>Loading... <i class="icon16 loading"></i></h1>',
				buttons: null,
				url: null,
				post: null,
				small: false,
				onload: null,
				oncancel: null
			}, p);

		p.content = $(p.content);
		if (!p.buttons) {
			p.buttons = $('<input type="submit" class="button gray" value="'+$_('Cancel')+'">').click(function() {
				if (p.oncancel) {
					p.oncancel.call(dialog[0]);
				}
				$.wa.dialogHide();
			});
		} else {
			p.buttons = $(p.buttons);
		}

		var dialog = $('#'+id);
		if (dialog.size() <= 0) {
			dialog = $(
				'<div class="dialog" id="'+id+'" style="display: none">'+
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
		}

		dialog.find('.dialog-buttons-gradient').empty().append(p.buttons);
		dialog.find('.dialog-content-indent').empty().append(p.content);
		dialog.show();

		if (p.small) {
			dialog.addClass('small');
		} else {
			dialog.removeClass('small');
		}

		if (p.url) {
			var f_callback = function (response) {
				dialog.find('.dialog-content-indent').html(response);
				$.wa.waCenterDialog(dialog);
				if (p.onload) {
					p.onload.call(dialog[0]);
				}
			};
			if (p.post) {
				$.post(p.url, p.post, f_callback);
			} else {
				$.get(p.url, f_callback);
			}
		}

		this.waCenterDialog(dialog);

		// close on escape key
		var onEsc = function(e) {
			if (!dialog.is(':visible')) {
				return;
			}

			if (e && e.keyCode == 27) { // escape
				if (p.oncancel && typeof p.oncancel == 'function') {
					p.oncancel.call(dialog[0]);
				}
				$.wa.dialogHide();
				return;
			}

			$(document).one('keyup', onEsc);
		};
		onEsc();
		$(document).one('hashchange', $.wa.dialogHide);
		return dialog;
	},

	/** Center the dialog initially or when its properties changed significantly
	  * (e.g. when .small class applied or removed) */
	waCenterDialog: function(dialog) {
		dialog = $(dialog);

		// Have to adjust width and height via JS because of min-width and min-height properties.
		var wdw = dialog.find('.dialog-window');

		var dw = wdw.outerWidth(true);
		var dh = wdw.outerHeight(true);

		var ww = $(window).width();
		var wh = $(window).height();

		var w = (ww-dw)/2 / ww;
		var h = (wh-dh)/2 / wh;

		wdw.css({
			'left': Math.round(w*100)+'%',
			'top': Math.round(h*100)+'%'
		});
	},

	/** Hide all dialogs */
	dialogHide: function() {
		$('.dialog').hide();
		return false;
	},

	/** Close all .dropdown menus */
	dropdownsClose: function() {
		var dd = $('.dropdown:not(.disabled)');
		dd.addClass('disabled');
		setTimeout(function() {
			dd.removeClass('disabled');
		}, 600);
	},

	/** Enable automatic close of .dropdowns when user clicks on item inside one. */
	dropdownsCloseEnable: function() {
		$(document).on('click', '.dropdown:not(.disabled)', this.dropdownsClickHandler);
	},

	/** Disable automatic close of .dropdowns when user clicks on item inside one. */
	dropdownsCloseDisable: function() {
		$(document).off('click', '.dropdown:not(.disabled)', this.dropdownsClickHandler);
	},

	/** Click handler used in dropdownsCloseDisable() and dropdownsCloseEnable(). */
	dropdownsClickHandler: function(e) {
		var self = $(this);
		if (self.hasClass('no-click-close')) {
			return;
		}
		self.addClass('disabled');
		setTimeout(function() {
			self.removeClass('disabled');
		}, 600);
	},

	 /** Set default value for an input field. If field becomes empty, it receives specified css class
		* and default value. On field focus, css class and value are removed. On blur, if field
		* is still empty, css class and value are restored. */
	defaultInputValue: function(input, defValue, cssClass) {
		if (!(input instanceof jQuery)) {
			input = $(input);
		}

		var onBlur = function() {
			var v = input.val();
			if (!v || v == defValue) {
				input.val(defValue);
				input.addClass(cssClass);
			}
		};
		onBlur();
		input.blur(onBlur);
		input.focus(function() {
			if (input.hasClass(cssClass)) {
				input.removeClass(cssClass);
				input.val('');
			}
		});
	},
	util: {
		formatFileSize: function(bytes) {
			var i = -1;
			do {
				bytes = bytes / 1024;
				i++;
			} while (bytes > 99);

			return Math.max(bytes, 0.01).toFixed(2) + ((i >=0)? (' ' + $_(['kB', 'MB', 'GB', 'TB', 'PB', 'EB'][i])):'');
		}
	}
});

$(document).ajaxError(function(e, xhr, settings, exception) {
	// Generic error page
	if (xhr.status !== 200 && xhr.responseText) {
		if (!$.wa.errorHandler || $.wa.errorHandler(xhr)) {
			if (xhr.responseText.indexOf('Exception') != -1) {
				$.wa.dialogCreate('ajax-error', {'content': "<div>" + xhr.responseText + '</div>'});
				return;
			}

			document.open("text/html");
			document.write(xhr.responseText); // !!! throws an "Access denied" exception in IE9
			document.close();
			$(window).one('hashchange', function() {
				window.location.reload();
			});
		}
	}
	// Session timeout, show login page
	else if (xhr.getResponseHeader('wa-session-expired')) {
		window.location.reload();
	}
	// Show an exception in development mode
	else if (typeof xhr.responseText !== 'undefined' && xhr.responseText.indexOf('Exception') != -1) {
		$.wa.dialogCreate('ajax-error', {'content': "<div>" + xhr.responseText + '</div>'});
	}
});

$.ajaxSetup({'cache': false});

$(document).ajaxSend(function (event, xhr, settings) {
	if (settings.type == 'POST') {
		var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)"));
		var csrf = matches ? decodeURIComponent(matches[1]) : '';
		if (settings.data === null ) {
			settings.data = '';
		}
		if (typeof(settings.data) == 'string') {
			if (settings.data.indexOf('_csrf=') == -1) {
				settings.data += (settings.data.length > 0 ? '&' : '') + '_csrf=' + csrf;
				xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			}
		} else if (typeof(settings.data) == 'object') {
			settings.data['_csrf'] = csrf;
		}
	}
});

if (!Array.prototype.indexOf)
{
	Array.prototype.indexOf = function(elt /*, from*/)
	{
	var len = this.length;

	var from = Number(arguments[1]) || 0;
	from = (from < 0)
		 ? Math.ceil(from)
		 : Math.floor(from);
	if (from < 0){from += len;}

	for (; from < len; from++)
	{
		if (from in this &&
			this[from] === elt) {
			return from;
		}
	}
	return -1;
	};
}

/** Localization */

// strings set up by apps
$.wa.locale = $.wa.locale || {};

/** One parameter: translate a string.
  * Two parameters, int and string: translate and get correct word form to use with number. */
$_ = function(p1, p2) {
	// Two parameters: number and string?
	if (p2) {
		if (!$.wa.locale[p2]) {
			if (console){
				console.log('Localization failed: '+p2); // !!!
			}
			return p2;
		}
		if (typeof $.wa.locale[p2] == 'string') {
			return $.wa.locale[p2];
		}

		var d = Math.floor(p1 / 10) % 10,
			e = p1 % 10;
		if (d == 1 || e > 4 || e == 0) {
			return $.wa.locale[p2][2];
		}
		if (e == 1) {
			return $.wa.locale[p2][0];
		}
		return $.wa.locale[p2][1];
	}

	// Just one parameter: a string
	if ($.wa.locale[p1]) {
		return typeof $.wa.locale[p1] == 'string' ? $.wa.locale[p1] : $.wa.locale[p1][0];
	}

	if (console){
		console.log('Localization failed: '+p1); // !!!
	}
	return p1;
};

// EOF