if (!RedactorPlugins) var RedactorPlugins = {};

RedactorPlugins.cut = {
    init: function() {
        this.opts.cut_plugin = {
            wa_post_cut_text_default: $.wa_blog.editor.options.cut_link_label_default || $_('Continue reading →'),
            element_id: 'elrte-wa_post_cut'
        };
        this.buttonAdd('wa_post_cut', $_('Post cut'), this.testButton);
    },
    testButton: function(buttonName, buttonDOM, buttonObj, e) {
        var html = '<span class="b-elrte-wa-split-vertical" id="' + this.opts.cut_plugin.element_id
            + '">' + this.opts.cut_plugin.wa_post_cut_text_default + '</span>';
        this.insertHtml(html);
    }
};