$.wa_blog.plugins_akismet = {
	options : {
		spam_template : '<a href="#" class="small b-comment-spam">%s</a>',
		loading_template : '<i class="b-ajax-status-loading icon16 loading"></i>'
	},
	init : function() {
		var self = this;
		$('#wa').bind('plugin.comment_add', function(e) {
			self.addControl(e.target);
		});

		$('.b-comment-spam').on('click', self.controlClickHandler);
	},
	controlClickHandler : function() {
		var self = $.wa_blog.plugins_akismet;
		var wa_blog = $.wa_blog.comments;
		var item = $(this).parents('.b-comment');
		var id = 0;
		if (item.length) {
			id = parseInt($(item).attr('id').replace(/^[\D]+/, ''));
		}
		$(this).hide().after(self.options.loading_template);
		if (id) {
			$.ajax({
				url : '?plugin=akismet',
				type : 'POST',
				data : {
					'spam' : id
				},
				success : function(response) {
					item.find('.b-ajax-status-loading').remove();
					if (response.status == 'ok') {
						wa_blog.setCommentStatus(id, response.data.status, null);
					} else {
						item.find('.b-comment-spam').show();
					}
				},
				error : function(jqXHR, textStatus, errorThrown) {
					item.find('.b-ajax-status-loading').remove();
					item.find('.b-comment-spam').show();
				},
				dataType : 'json'
			});
		}
		return false;
	},
	onContentUpdate : function() {
		var self = this;
		$('.b-comment:not(.b-deleted):not(.js-akismet)').each(function() {
			self.addControl(this);
		});
		$('.b-comment.b-deleted.js-akismet').each(function() {
			self.deleteControl(this);
		});
	},
	deleteControl : function(elem) {
		if ($(elem).find('.b-comment-delete:hidden').length) {
			$(elem).find('a.b-comment-spam').remove();
			$(elem).removeClass('js-akismet');
		}
	},
	addControl : function(elem) {
		if ($(elem).find('.b-comment-auth-user').length < 1) {
			var control = $(elem).find('.b-comment-delete');
			$(elem).addClass('js-akismet');
			control.before(this.options.spam_template.replace(/%s/,
					$_('mark as spam')));
		}
	}
};
