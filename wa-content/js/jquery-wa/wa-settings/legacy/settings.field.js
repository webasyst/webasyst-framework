var WASettingsField = (function ($) {

    var WASettingsField = function (options) {
        var that = this;

        // DOM
        that.$wrapper = options.$wrapper;

        // VARS
        // DYNAMIC VARS
        // INIT
        that.initClass();
    };

    WASettingsField.prototype.initClass = function () {
        var that = this;

        //
        $('#s-sidebar-wrapper').find('ul li').removeClass('selected');
        $('#s-sidebar-wrapper').find('[data-id="field"]').addClass('selected');

        that.initSortable();
        that.bindEvents();
    };

    WASettingsField.prototype.initSortable = function () {
        var that = this,
            href = "?module=settingsFieldSortSave",
            item_index,
            xhr = false,
            $block = that.$wrapper.find('.wa-other-fields');

        $block.sortable({
            handle: '.sort',
            items: '.field',
            axis: 'y',
            tolerance: 'pointer',
            delay: 200,
            start: function(event,ui) {
                item_index = ui.item.index();
            },
            stop: function(event,ui) {
                if (item_index != ui.item.index()) {
                    var fields = getSortArray($block);
                    saveSort(href, { fields: fields });
                }
            }
        });

        function getSortArray($block) {
            return $block.find(".field").map(function() {
                return $.trim($(this).data("id")) || '';
            }).toArray();
        }

        function saveSort(href, data) {
            if (xhr) {
                xhr.abort();
                xhr = null;
            }
            return $.post(href, data, function () {
                xhr = null;
            });
        }
    };

    WASettingsField.prototype.bindEvents = function () {
        var that = this,
            href = "?module=settingsFieldEditDialog",
            xhr = null;

        that.$wrapper.on('click', '.js-edit-field-link', function () {
            var $el = $(this);

            if (xhr) {
                xhr.abort();
                xhr = null;
            }

            xhr = $.post(href, { id: $el.data('id') || null }, function(html) {
                new WASettingsDialog({
                    html: html
                });
            });
        });
    };

    return WASettingsField;

})(jQuery);
