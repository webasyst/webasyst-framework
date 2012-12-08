/** Image uploader plugin for cleditor.
  * Adds uploading functionality to image button.
  * !!! Requires WebAsyst framework to work... */
(function($) {
	$.cleditor.buttons.image.popupName = 'imageupload';
	$.cleditor.buttons.image.popupClass = 'cleditorPrompt';
	$.cleditor.buttons.image.popupContent =
		'<div class="iuform">'+
			'<form id="imageform" target="cledimgsendfile" method="post" enctype="multipart/form-data">'+
				$_('Enter URL:')+
				'<br><input type="text" value="http://" size="35"><br>'+
				$_('Or select a file:')+
				'<br><form action=""><input type="file" name="photo" size="35"><br>'+
			'</form>'+
			'<input type="button" value="'+$_('Submit')+'">'+
		'</div>'+
		'<i class="icon16 loading" style="display:none"></i>'+
		'<div style="display:none">'+
			'<iframe width="1" height="1" src="javascript:true;" name="cledimgsendfile"></iframe>'+
		'</div>';
	$.cleditor.defaultOptions.imgUploadUrl = 'javascript:alert("imgUploadUrl option is not set for CLEditor!");';

	$.cleditor.buttons.image.buttonClick = function(e, data) {
		var editor = data.editor,
			popup = $(data.popup),
			form = popup.find("form");

		form[0].reset();
		popup.find('.loading').hide();
		popup.find('.iuform').show();
		popup.find('iframe').src = 'javascript:true;';

		// Button click inserts img from URL
		popup.find(':button').unbind("click").bind("click", function(e) {
			var url = popup.find(":text").val();
			editor.execCommand(data.command, url, null, data.button);
			editor.hidePopups();
			editor.focus();
		});

		// File input change starts uploading
		popup.find(":file").unbind("change").bind("change", function(e) {
			var form = $(this).parent();

			// upload finish handler
			popup.find('iframe').unbind("load").bind("load", function() {
				var url = $(this).contents().find('body').text();
				if (!url || url.substr(0, 5) == 'error') {
					alert('Unable to upload file: '+url); // !!!
				} else if (url) {
					editor.execCommand(data.command, url, null, data.button);
				}

				editor.hidePopups();
				editor.focus();
			});

			// submit form into iframe
			form.attr('action', editor.options.imgUploadUrl).submit();

			// show loading indicator instead of form
			popup.find('.iuform').hide();
			popup.find('.loading').show();
		});
	}
})(jQuery);
