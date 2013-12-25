(function ($) {
    elRTE.prototype.options.panels.wa_post_cut = ['wa_post_cut'];

    elRTE.prototype.options.buttons['wa_post_cut'] = $_('Post cut');
    elRTE.prototype.ui.prototype.buttons.wa_post_cut = function (rte, name) {
        this.constructor.prototype.constructor.call(this, rte, name);
        var id = 'elrte-wa_post_cut';

        try {
            this.wa_post_cut_text_default = $.wa_blog.editor.options.cut_link_label_defaul;
        } catch (e) {
            this.wa_post_cut_text_default = $_('Continue reading →');
        }

        this.update = function () {
            var hr = $('#' + id, rte.doc);
            if (hr.length) {
                if (!hr.text()) {
                    hr.text(this.wa_post_cut_text_default);
                }
            }
        };

        this.command = function () {
            this.rte.history.add();
            var html = '<span class="b-elrte-wa-split-vertical" id="' + id
                + '">' + this.wa_post_cut_text_default + '</span>';
            this.rte.selection.insertHtml(html);
        };
    };
})(jQuery);