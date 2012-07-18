/**
 * 
 */
(function ($) {
$.wa.site.themes = {
	options: {},
	init:function (options) {
		var self = this;
		self.options = options;
		if(self.options.title) {
			document.title = self.options.title;
		}
		$('.js-site-theme').die('click');
		$('.js-site-theme').live('click', self.dispatch);
		$("#s-upload-link").click(function () {
			$(".s-dialog-error").text('');
			$("#s-upload-dialog div.loading").hide();
			$("#s-upload-dialog").waDialog({ 
				disableButtonsOnSubmit: true,
				onSubmit: function () {
					$("#s-upload-dialog div.loading").show();
					$("#s-upload-iframe").one('load', function () {
						$("#s-input-file").replaceWith('<input id="s-input-file" type="file" name="theme_files[]" multiple="" >');
						$("#s-input-file").bind('change', function () {
							$("#s-upload-form").submit();	
						});
						var response;
						try {
							response = $.parseJSON($(this).contents().text());
							if (response.status == 'fail') {
								$("#s-upload-dialog div.loading").hide();
								$("#s-input-file").replaceWith('<input id="s-input-file" type="file" name="theme_files[]" multiple="" >');
								self.handleError(response);
								//$("#s-upload-dialog input[type=submit]").removeAttr('disabled');
							} else if (response.status == 'ok') {
								$("#s-upload-dialog").hide();
								$(".s-dialog-error").text('');
								var params = {
									'slug':response.data.slug,
									'actionName':'info'
								};
								$.wa.site.themes['runRequest'].apply($.wa.site.themes, [params]);
							}
						} catch(e) {
							alert($(this).contents().text());
						}
					});
					
				},
				onLoad: function () {
					
				}
			});		
			//$("#s-input-file").click();
			return false;
		});
		$("#s-input-file").bind('change', function () {
			//$("#s-upload-form").submit();	
		});
	},
	dispatch: function() {
		var title =  $(this).attr('title');
		if(!title || confirm(title)) {
			
			var id = $(this).attr('id');
			var actionName = id.replace(/%2f.*$/i,'');
			var onActionName = 'on' + actionName.substr(0,1).toUpperCase() + actionName.substr(1);
			var slug = id.replace(/^[^%]+%2f/i,'');
			var theme_id = slug.replace(/^.*%2f/i,'');
			var params = {'slug':slug,'actionName':actionName,'theme_id':theme_id};

			if($.wa.site.themes[onActionName + 'Action']) {
				$.wa.site.themes[onActionName + 'Action'].apply($.wa.site.themes, [params]);
			} else if ($.wa.site.themes[actionName + 'Action']) {
				$.wa.site.themes['runRequest'].apply($.wa.site.themes, [params]);
			} else if (console) {
					console.log('Invalid action name:', actionName+'Action');
			}
		}
		return false;
	},
	runRequest: function(params) {
		if ($.wa.site.themes[params.actionName + 'Action']) {
			var self = this;
			params.domain = self.options.domain;
			$.ajax({
				url:'?module=themes&action=' + params.actionName,
				data:params,
				type:'POST',
				dataType: 'json',
				error:function(jqXHR, textStatus, errorThrown) {
					alert(jqXHR.responseText);
				},
				success:function(data, textStatus, jqXHR) {
					switch(data['status'])
					{
						case 'ok':
						case 'success': {
							$.wa.site.themes[params.actionName + 'Action'].apply($.wa.site.themes, [params, data]);
							break;
						}
						case 'error': 
						case 'fail': {
							self.handleError(data);
							break;
						}
					};
				}
			});
		} else {
			if (console) {
				console.log('Invalid action name:', params.actionName+'Action');
			}
		}
	},
	handleError: function(data) {
		var error = '';
		for(error_item in data.errors) {
			error += (error?'\n':'') + data.errors[error_item][0];
		}
		if($(".dialog:visible").length) {
			$(".dialog:visible .s-dialog-error").html(error+'<br><br>');
		} else if($(".s-dialog-error:first:visible").length) {
			$(".s-dialog-error:first:visible").html('<br><br>'+error+'<br><br>');
		} else {
			alert(error);
		}
		$("#s-name-dialog input[type=submit]").removeAttr('disabled');
		$("#s-upload-dialog input[type=submit]").removeAttr('disabled');
	},
	onCopyAction: function(params) {
		$.wa.site.themes['runRequest'].apply($.wa.site.themes, [params]);
		return false;
	},
	onRenameAction: function(params) {
		var self = this;
		$("#s-name-dialog-title").text($_('rename_title'));
		$("#s-name-dialog").waDialog({ 
			disableButtonsOnSubmit: true,
			onLoad: function () {
				var name = $('#theme_name_'+params.slug.replace(/([%])/g,'\\$1')).text();
				$("#s-name").val(name);
				$("#s-id").val(params.theme_id).focus().select();
			},
			onSubmit: function () {
				params.id = $("#s-id").val();
				params.name = $("#s-name").val();
				$.wa.site.themes['runRequest'].apply($.wa.site.themes, [params]);
				return false;
			}
		});
	},
	renameAction: function(params,response) {
		$("#s-name-dialog").hide();
		var slug = response.data['slug'].replace(/([%])/g,'\\$1');
		if(slug) {
			$('#theme_container_' + params.slug.replace(/([%])/g,'\\$1')).remove();
			var item = $('#theme_container_' + slug);
			if(item.length>0) {
				item.replaceWith(response.data['content']);
			} else {
				$('#s-themes-list tbody').prepend(response.data.content);
				$('html, body').animate({scrollTop:0});
				$('.s-scrollable-part').animate({scrollTop:0});
			}
		}
	},
	copyAction: function(params, response) {
		$('#s-themes-list tbody').prepend(response.data.content);
		$('html, body').animate({scrollTop:0});
		$('.s-scrollable-part').animate({scrollTop:0});
	},
	brushAction: function(slug, response) {
		var slug = response.data['slug'].replace(/([%])/g,'\\$1');
		if(slug) {
			$('#theme_container_' + slug).replaceWith(response.data['content']);
		}
		if(response.data['message']) {
			alert(response.data['message']);
		}
	},
	infoAction: function(params, response) {
		var slug = response.data['slug'].replace(/([%])/g,'\\$1');
		if(slug) {
			var item = $('#theme_container_' + slug);
			if(item.length>0) {
				item.replaceWith(response.data['content']);
			} else {
				$('#s-themes-list tbody').prepend(response.data.content);
				$('html, body').animate({scrollTop:0});
				$('.s-scrollable-part').animate({scrollTop:0});
			}
		}
		if(response.data['message']) {
			alert(response.data['message']);
		}
	},
	purgeAction: function(params, response) {
		var slug = response.data['slug'].replace(/([%])/g,'\\$1');
		if(slug) {
			$('#theme_container_' + slug).hide();
		}
		if(response.data['message']) {
			alert(response.data['message']);
		}
	}
};
})(jQuery);