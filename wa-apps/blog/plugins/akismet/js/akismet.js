$.wa_blog.plugins_akismet = {
	init : function() {
		var self = this;
		$('#wa').bind('plugin.comment_add', function(e) {
			self.addControl(e.target);
		});

		$('.b-comment-spam').live('click', self.controlClickHandler );
	},
	controlClickHandler: function(){
		var item = $(this).parents('.b-comment');
		var id = item.length?parseInt($(item).attr('id').replace(/^[\D]+/,'')):0;
		$(this).hide().after('<i class="b-ajax-status-loading icon16 loading"><!-- icon --></i>');
		if(id) {
			$.ajax({
				url : '?plugin=akismet',
				type : 'POST',
				data : {
					'spam' : id
				},
				success : function(response) {
					item.find('.b-ajax-status-loading').remove();
					if (response.status == 'ok') {
						$.wa_blog.comments.setCommentStatus(id, response.data.status, null);
					} else {
						item.find('.b-comment-spam').show();
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
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
		var self = $(elem).find('.b-comment-delete');
		$(elem).addClass('js-akismet');
		self.before('<a href="#" class="small b-comment-spam">' + $_('it\'s a spam') + '</a>');
	}
};