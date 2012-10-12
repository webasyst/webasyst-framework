$.storage = new $.store();
$.wa_blog_options = $.wa_blog_options ||{};
$.wa_blog = $.extend(true, $.wa_blog, {
	rights : {
		admin : false
	},
	common : {
		options : {},
		parent : null,
		init_stack : {},
		init : function(options) {
			var self = this;
			this.parent = $.wa_blog;
			this.options = $.extend(this.options, options);

			$(document).ready(function() {
				self.onDomReady(self.parent);
			});

		},
		onDomReady : function(blog) {
			blog = blog || $.wa_blog;
			$(window).scrollTop(0);
			for ( var i in blog) {
				if (i != 'common') {
					if (blog[i].init && (typeof (blog[i].init) == 'function')) {
						try {
							blog[i].init($.wa_blog_options[i]||{});
						} catch (e) {
							if (typeof (console) == 'object') {
								console.log(e);
							}
						}
					}
				}
			}
		},
		ajaxInit : function(blog) {
			blog = blog || $.wa_blog;
			var stack = [];
			$(window).scrollTop(0);
			for ( var i in blog) {
				try {
					if (i != 'common') {
						if (blog[i].ajaxInit && (typeof (blog[i].ajaxInit) == 'function')) {

							if (!this.init_stack[i]) {
								blog[i].ajaxInit();
								this.init_stack[i] = true;
								stack[i] = true;
							}

						}
					}
				} catch (e) {
					stack[i] = false;
					if (typeof (console) == 'object') {
						console.log(e);
					}
				}
			}
			return stack;
		},
		ajaxPurge : function(id) {
			if (this.init_stack[id]) {
				if ($.wa_blog[id]) {
					try {
						if ($.wa_blog[id].ajaxPurge && (typeof ($.wa_blog[id].ajaxPurge) == 'function')) {
							$.wa_blog[id].ajaxPurge();
						}
					} catch (e) {
						if (typeof (console) == 'object') {
							console.log(e);
						}
					}
					$.wa_blog[id] = {};
				}
				this.init_stack[id] = null;
			}
		},
		onContentUpdate : function(response, target) {
			var blog = this.parent;
			for ( var i in blog) {
				if (i != 'common') {
					if (blog[i].onContentUpdate
							&& (typeof (blog[i].onContentUpdate) == 'function')) {
						try {
							blog[i].onContentUpdate();
						} catch (e) {
							if (typeof (console) == 'object') {
								console.log(e);
							}
						}
					}
				}
			}
		}
	},
	plugins : {
	// placeholder for plugins js code
	},
	dialogs : {
		pull : {},
		init : function() {
			var self = this;
			$(".dialog-confirm").live('click', self.confirm);
			$(".js-confirm").live('click', self.jsConfirm);
		},
		close : function(id) {
			if ($.wa_blog.dialogs.pull[id]) {
				$.wa_blog.dialogs.pull[id].trigger('close');
			}
		},
		confirm : function() {
			var id = $(this).attr('id').replace(/-.*$/, '');
			$.wa_blog.dialogs.pull[id] = $("#" + id + "-dialog").waDialog({
				disableButtonsOnSubmit : true,
				onSubmit : function() {
					return false;
				}
			});
			return false;
		},
		jsConfirm : function() {
			var question = $(this).attr('title') || 'Are you sure?';
			if (!confirm(question)) {
				return false;
			}
		}

	},
	sidebar : {
		options : {
			key : 'blog/collapsible/'
		},
		init : function() {
			var self = this;
			$(".menu-collapsible .collapse-handler").each(function() {
				self.restore(this);
				$(this).click(function() {
					return self.toggle(this);
				});
			});
			if ($.wa_blog.rights.admin > 1) {
				$('#blogs').sortable({
					containment : 'parent',
					distance : 5,
					tolerance : 'pointer',
					stop : self.sortHandler
				});
			}
		},
		sortHandler : function(event, ui) {
			var url = "?module=blog&action=sort" + "&blog_id="
					+ $(ui.item).attr('id').replace('blog_li_item_', '') + "&sort="
					+ ($(ui.item).index() + 1);
			$.get(url, function(response) {
				if (response && response.status && response.status == "ok") {
				} else {
					return false;
				}
			}, "json");

		},
		toggle : function(Element) {
			var item = $(Element).find('.rarr');
			if (item.length) { // show
				this.show(Element);
			} else if (item = $(this).find('.darr')) {
				this.hide(Element);
			}
			return false;
		},
		show : function(Element) {
			Element = $(Element);
			var list = Element.parent().find('ul.collapsible');
			list.show();
			if (list.attr('id') == 'blog-drafts') {
				Element.find('.count').hide();
			}
			Element.find('.rarr').removeClass('rarr').addClass('darr');
			$.storage.set(this.options.key + list.attr('id'), null);
		},
		hide : function(Element) {
			Element = $(Element);
			var list = Element.parent().find('ul.collapsible');
			if (list.attr('id') == 'blog-drafts') {
				Element.find('.count').show();
			}
			list.hide();
			Element.find('.darr').removeClass('darr').addClass('rarr');
			$.storage.set(this.options.key + list.attr('id'), 2);

		},
		restore : function(Element) {
			var list = $(Element).parent().find('ul.collapsible');
			var id = list.attr('id');
			if (id) {
				try {
					if (parseInt($.storage.get(this.options.key + id)) == 2) {
						this.hide(Element);
					}
				} catch (e) {
					if (typeof (console) == 'object') {
						console.log(e);
					}
				}
			}
		}
	},
	helpers : {
		init : function() {
			this.compileTemplates();
		},
		compileTemplates : function() {
			var pattern = /<\\\/(\w+)/g;
			var replace = '</$1';

			$("script[type$='x-jquery-tmpl']").each(function() {
				var id = $(this).attr('id').replace(/-template-js$/, '');
				try {
					var template = $(this).html().replace(pattern, replace);
					$.template(id, template);
				} catch (e) {
					if (typeof (console) == 'object') {
						console.log(e);
					}
				}
			});
		}
	}

});

(function($, window, undefined) {
	$.wa_blog.common.init();
})(jQuery, this);
