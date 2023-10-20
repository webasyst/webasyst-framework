var WASettingsRegions = ( function($) {

    var WASettingsRegions = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$form = that.$wrapper.find('form');
        that.$table = that.$form.find('.js-regions-table');
        that.$footer_actions = that.$form.find('.js-footer-actions');
        that.$button = that.$footer_actions.find('.js-submit-button');
        that.$cancel = that.$footer_actions.find('.js-cancel');
        that.$loading = that.$footer_actions.find('.s-loading');

        // VARS
        that.country_iso3letter = options.country_iso3letter;
        that.locales = options.locales;
        that.wa_url = options.wa_url;

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    WASettingsRegions.prototype.initClass = function() {
        var that = this;

        //
        var $sidebar = $('#js-sidebar-wrapper');
        $sidebar.find('ul li').removeClass('selected');
        $sidebar.find('[data-id="regions"]').addClass('selected');
        //
        that.initSelector();
        //
        that.initAddLink();
        //
        that.initDeleteLinks();
        //
        that.initFavorite();
        //
        that.initSubmit();

    };

    WASettingsRegions.prototype.initSelector = function () {
        var that = this,
            $form = that.$form,
            $selector = $form.find('.js-country-selector');

        // Helper to determine whether the form has changed and we should warn user if he leaves the page
        // Reload the page when user changes country in the selector
        $selector.change(function(e) {

            if (that.$button.hasClass('yellow')) {
                var msg = that.locales['confirm_region_not_saved'];
                if (!confirm(msg)) {
                    $selector.val(that.country_iso3letter);
                    return false;
                }
            }
            var new_country = $selector.val();
            $selector.attr('disabled', true);
            $('#s-content-block').load('?module=settingsRegions&country='+new_country);
        });
    };

    WASettingsRegions.prototype.initAddLink = function () {
        var that = this,
            $table = that.$table;

        // Link to add new region
        $table.on('click', '.js-add-region-link', function () {
            var empty_row = $table.find('.js-template-new').clone().removeClass('hidden').removeClass('js-template-new');

            $(this).parents('tr').before(empty_row);
            empty_row.siblings('.empty-stub').hide();
        });

        // заполнить регионы из справочника
        $table.on('click', '.js-fill-regions-link', function () {
            let link = this;
            let select_country = that.$form.find('.js-country-selector').val();

            $.getScript(that.wa_url +'wa-content/js/jquery-wa/countries.js', function () {
                // variable country_list in countries.js
                if (select_country && country_list && country_list.hasOwnProperty(select_country)) {
                    let empty_row = $table.find('.js-template-new').clone().removeClass('hidden').removeClass('js-template-new');
                    $table.find('.empty-stub').hide();
                    $(link).hide();
                    for (let region of country_list[select_country]) {
                        let reg = empty_row.clone();
                        reg.find('[name="region_codes[]"]').val(region[0]);
                        reg.find('[name="region_names[]"]').val(region[1]);
                        if (region[2]) {
                            reg.find('[name="region_centers[]"]').val(region[2]);
                        }
                        $('.js-add-region-link').parents('tr').before(reg);
                    }
                    that.$button.addClass('yellow');
                } else if (!country_list.hasOwnProperty(select_country)) {
                    alert(that.locales['country_is_not_list']);
                }
            });
        });
    };

    WASettingsRegions.prototype.initDeleteLinks = function () {
        var that = this,
            $table = that.$table;

// Mark table row for deletion when user clicks delete icon
            $table.on('click', '.js-action-del', function() {
                var $svg_icon = $(this).find('.del');
                var $tr = $svg_icon.parents('tr');
                var initial_value = $tr.find('[name="region_names[]"]').attr('rel');
                if (!initial_value) {
                    $tr.remove();
                    if ($table.find('tbody tr:not(.white):visible').length <= 0) {
                        $table.find('.empty-stub').show();
                    }
                    return;
                }

                var row = $table.find('.js-template-deleted').clone().removeClass('hidden').removeClass('js-template-deleted');
                row.find('.insert-name-here').text(initial_value);
                $tr.after(row).remove();

                that.$footer_actions.addClass('is-changed');
                that.$button.addClass('yellow').next().show();
            });
    };

    WASettingsRegions.prototype.initFavorite = function () {
        var that = this,
            $form = that.$form,
            $table = that.$table,
            $country_fav_icon = $form.find('.js-country-fav-icon'),
            $country_fav_input = $form.find('.js-contry-fav-input'),
            href = $form.attr('action');

            // Icon to mark country as favorite
            $table.on('click', '.js-action-fav', function() {
                var $svg_icon = $(this).find('.fav');
                $svg_icon.attr('data-prefix') === 'far' ? $svg_icon.attr('data-prefix', 'fas') : $svg_icon.attr('data-prefix', 'far');
                var fav_sort = $svg_icon.attr('data-prefix') === 'fas' ? '1' : '',
                    region = $svg_icon.parents('tr').data('orig-code'),
                    data = { fav: 1, country: that.country_iso3letter, region: region, fav_sort: fav_sort };

                $svg_icon.siblings('input:hidden').val(fav_sort);

                // Save immediately via AJAX so user does not have to click save
                if ($svg_icon.parents('.just-added').length <= 0) {
                    $.post(href, data);
                }
            });

        // Icon to mark country as favorite
        $country_fav_icon.on('click', function () {
            var $svg_icon = $(this).find('svg'),
                fav_sort = $svg_icon.attr('data-prefix') === 'fas' ? '' : '1',
                data = { fav: 1, country: that.country_iso3letter, fav_sort: fav_sort };

            // toggleAttr
            $svg_icon.attr('data-prefix') === 'far' ? $svg_icon.attr('data-prefix', 'fas') : $svg_icon.attr('data-prefix', 'far');

            $country_fav_input.val(fav_sort);

            // Save immediately via AJAX so user does not have to click save
            $.post(href, data);
        });
    };

    WASettingsRegions.prototype.initSubmit = function () {
        var that = this,
            $form = that.$form;

        $form.submit(function(e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }

            // Validation
            var errors = false;
            that.$table.find('input:visible').each(function() {
                var self = $(this),
                    value = $.trim(self.val());

                if (self.hasClass('js-input-required') && (!value || value == '0')) {
                    self.addClass('state-error').one('focus', function() {
                        self.removeClass('state-error');
                    });
                    errors = true;
                }
            });

            if (errors) {
                return false
            }

            that.is_locked = true;
            that.$button.prop('disabled', true);

            var $button_text = that.$button.text(),
                $loader_icon = ' <i class="fas fa-spinner fa-spin"></i>',
                $success_icon = ' <i class="fas fa-check-circle"></i>';
            that.$button.empty().html($button_text + $loader_icon);

            var href = $form.attr('action'),
                data = $form.serialize();

            $.post(href, data, function (res) {
                if (res) {

                    that.$button.empty().html($button_text + $success_icon).removeClass('yellow');
                    that.$footer_actions.removeClass('is-changed');

                    setTimeout(function(){
                        that.$button.empty().html($button_text);
                    },2000);
                } else {
                    that.$button.empty().html($button_text);
                }
                that.is_locked = false;
                that.$button.prop('disabled', false);
            });
        });

        that.$table.on('input', function () {
            that.$footer_actions.addClass('is-changed');
            that.$button.addClass('yellow').next().show();
        });

        // Reload on cancel
        that.$cancel.on('click', function (e) {
            e.preventDefault();
            $.wa.content.reload();
            return;
        });
    };

    return WASettingsRegions;

})(jQuery);
