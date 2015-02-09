if (!RedactorPlugins) var RedactorPlugins = {};

RedactorPlugins.vars = {
    init: function()
    {
        var callback = $.proxy(function()
        {
            $('#redactor_modal').find('.one-var').each($.proxy(function(i, s)
            {
                $(s).click($.proxy(function()
                {
                    this.insertClip($(s).find('.var-code').text());
                    return false;

                }, this));
            }, this));

            this.selectionSave();
            this.bufferSet();

        }, this );

        $('<div id="clipsmodal" style="display: none"></div>').appendTo('body');
        $('#clipsmodal').empty().append($('<section></section>').html($('#available-smarty-variables').html()));
        $('#clipsmodal').append('<footer><button class="redactor_modal_btn redactor_btn_modal_close">'+$_('Close')+'</button></footer>');


        this.buttonAdd('clips', $_('Insert variable'), function(e)
        {
            this.modalInit($_('Insert variable'), '#clipsmodal', 500, callback);
        });
    },
    insertClip: function(html)
    {
        this.selectionRestore();
        this.insertHtml($.trim(html));
        this.modalClose();
    }
};