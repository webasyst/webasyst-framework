(function($) {
	elRTE.prototype.options.panels.wa_split_vertical = ['wa_split_vertical'];

	elRTE.prototype.options.buttons['wa_split_vertical'] = $_('Split vertical');
	elRTE.prototype.ui.prototype.buttons.wa_split_vertical = function(rte, name) {
		this.constructor.prototype.constructor.call(this, rte, name);
		var id = 'elrte-wa_split_vertical';

		try {
			this.wa_split_vertical_text_default = $.wa_blog.editor.options.cut_link_label_defaul;
		} catch (e) {
			this.wa_split_vertical_text_default = $_('Continue reading →');
		}

		this.update = function() {
			var hr = $('#' + id, rte.doc);
			if (hr.length) {
				if (!hr.text()) {
					hr.text(this.wa_split_vertical_text_default);
				}
			}
		};

		this.command = function() {
			this.rte.history.add();
			var html = '<span class="b-elrte-wa-split-vertical" id="' + id
					+ '">' + this.wa_split_vertical_text_default + '</span>';
			this.rte.selection.insertHtml(html);
		};
	};
})(jQuery);