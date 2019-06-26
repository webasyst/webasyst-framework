var WASettingsDBListDialog = ( function($) {

    WASettingsDBListDialog = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$content = that.$wrapper.find('.js-content');
        that.$button = that.$wrapper.find('.js-button');
        that.$filter_wrapper = that.$wrapper.find('.js-list-filter');
        that.$list_wrapper = that.$wrapper.find('.js-list-wrapper');
        that.$stats_wrapper = that.$wrapper.find('.js-runner-stats-wrapper');
        that.$log_path_wrapper = that.$stats_wrapper.find('.js-log-path-wrapper');
        that.$log_path = that.$log_path_wrapper.find('.js-log-path');
        that.$notice = that.$wrapper.find('.js-dialog-notice');

        // VARS
        that.templates = options["templates"];

        // DYNAMIC VARS
        that.is_locked = false;

        that.tables_all = 0;
        that.tables_converted = 0;
        that.tables_error = 0;

        that.columns_all = 0;
        that.columns_converted = 0;
        that.columns_error = 0;

        // INIT
        that.initClass();
    };

    WASettingsDBListDialog.prototype.initClass = function() {
        var that = this;

        //
        that.$filter_wrapper.on('click', 'a', function () {
            var $self = $(this),
                filter = $self.data('filter');

            that.initFilter(filter);
        });

        //
        that.loadList();
        //
        that.initButton();
    };

    WASettingsDBListDialog.prototype.loadList = function() {
        var that = this,
            href = "?module=settingsDatabaseList";

        $.get(href, function (html) {
            that.$filter_wrapper.show();
            that.$list_wrapper.html(html);
            that.$button.prop('disabled', false);
            $(document).trigger('resize');
        });
    };

    WASettingsDBListDialog.prototype.initButton = function () {
        var that = this;

        that.$button.on('click', function () {
            that.initConvert();
        });
    };

    WASettingsDBListDialog.prototype.initConvert = function () {
        var that = this,
            items_for_convert = that.$list_wrapper.find('[data-is-mb4="0"]'),
            process_hash = null;

        that.$stats_wrapper.hide();
        
        if (that.is_locked || !items_for_convert.length) {
            return false;
        }

        that.tables_all = 0;
        that.tables_converted = 0;
        that.tables_error = 0;

        that.columns_all = 0;
        that.columns_converted = 0;
        that.columns_error = 0;

        new WASettingsDialog({
            html: that.templates["confirm"],
            onConfirm: function () {
                process_hash = Date.now();
                that.initFilter(0);
                that.$notice.show();
                convertCharset(0);
            }
        });
        
        function convertCharset(i) {
            if (typeof items_for_convert[i] === 'undefined') {
                that.$notice.hide();
                renderStats();
                that.is_locked = false;
                return false;
            }
            
            var $item = $(items_for_convert[i]),
                $collation = $item.find('.js-collation'),
                $status = $item.find('.js-status'),
                item_table = $item.data('table'),
                item_column = $item.data('column'),
                href = "?module=settingsDatabaseConvert",
                data = {
                    process_hash: process_hash,
                    table: item_table,
                    column: item_column
                };

            var $loading = $(that.templates["loading"]).clone();
            $status.html($loading);

            // Scroll to item
            var item_top_pos = $item[0].offsetTop;
            that.$content.scrollTop(item_top_pos - 75);

            if (item_column) {
                ++that.columns_all;
            } else {
                ++that.tables_all;
            }
            
            $.post(href, data, function (res) {
                if (res.status == "ok") {
                    $item.data('is-mb4', 1);
                    $item.attr('data-is-mb4', 1);
                    $collation.text(res.data['collation']).removeClass('bad').addClass('good');
                    $loading.removeClass('loading').addClass('yes');

                    if (item_column) {
                        ++that.columns_converted;
                    } else {
                        ++that.tables_converted;
                    }

                } else {
                    $loading.removeClass('loading').addClass('no');
                    
                    if (res.errors["log_path"]) {
                        that.$log_path.text(res.errors["log_path"]);
                        that.$log_path_wrapper.show();
                    }

                    if (item_column) {
                        ++that.columns_error;
                    } else {
                        ++that.tables_error;
                    }
                }

                // Run next item
                ++i;
                convertCharset(i);
            });
        }

        function renderStats() {
            that.$stats_wrapper.find('.js-all-tables-count').text(that.tables_all);
            that.$stats_wrapper.find('.js-all-columns-count').text(that.columns_all);
            that.$stats_wrapper.find('.js-converted-tables-count').text(that.tables_converted);
            that.$stats_wrapper.find('.js-converted-columns-count').text(that.columns_converted);
            that.$stats_wrapper.find('.js-error-tables-count').text(that.tables_error);
            that.$stats_wrapper.find('.js-error-columns-count').text(that.columns_error);
            that.$stats_wrapper.show();

            // Scroll to stats on finish
            that.$content.scrollTop(that.$stats_wrapper[0].offsetTop);
        }
    };

    WASettingsDBListDialog.prototype.initFilter = function (filter) {
        var that = this,
            selected_class = 'is-selected';

        // Remove selected
        that.$filter_wrapper.find('.js-filter-item').removeClass(selected_class);
        // Add selected
        that.$filter_wrapper.find('.js-filter-item[data-filter="'+ filter +'"]').addClass(selected_class);

        //

        if (filter == 'all') {
            that.$list_wrapper.find('.js-list-item').show();
        } else {
            that.$list_wrapper.find('.js-list-item').each(function (i, item) {
                var $item = $(item);

                if ($item.data('is-mb4') == filter) {
                    $item.show();

                    if ($item.hasClass('.js-column')) {
                        $item.prev('.js-table').show();
                    }
                } else {
                    $item.hide();
                }
            });
        }

    };

    return WASettingsDBListDialog;

})(jQuery);