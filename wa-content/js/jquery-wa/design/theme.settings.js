var WAThemeSettings = ( function($) {

    WAThemeSettings = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$form = that.$wrapper.find('#theme-settings');
        that.$button = that.$form.find(':submit');
        that.$error = that.$wrapper.find('#theme-settings-error');
        that.$message = that.$wrapper.find('#theme-settings-message');
        that.$theme_navigation = that.$wrapper.find('.js-theme-navigation');
        that.$expand_collapse_all = that.$theme_navigation.find('.js-expand-collapse-all');
        that.$search_input = that.$theme_navigation.find('.js-search-setting');
        that.$anchors = that.$theme_navigation.find('.js-anchors');
        that.$divider_list = that.$wrapper.find('.js-divider-list');
        that.$settings_list = that.$wrapper.find('.js-settings-list');
        that.$global_dividers = that.$settings_list.find('.js-theme-setting-divider[data-divider-level="1"]');
        that.$other_blocks = that.$wrapper.find('.js-theme-other-data');

        // VARS
        that.theme_id = options["theme_id"];
        that.theme_routes = options["theme_routes"];
        that.design_url = options["design_url"];
        that.locale = options["locale"];
        that.theme_storage_key = "theme/"+that.theme_id+"/expand";
        that.expand_all_storage_value = '-ALL-';
        that.classes = {
            expand_all: 'js-expand-all',
            collapse_all: 'js-collapse-all',
            divider_expand: 'js-divider-expand',
            divider_collapse: 'js-divider-collapse'
        };

        // DYNAMIC VARS

        // INIT
        that.initClass();
    };

    WAThemeSettings.prototype.initClass = function() {
        var that = this;

        //
        var url = $('#wa-theme-routing-url');
        if(url.length) {
            $('#wa-theme-'+that.theme_id+' .js-theme-routing-url').removeClass('js-theme-routing-url').wrap('<a href="'+url.attr('href')+'"></a>');
        }

        //
        $('.create-new-route-control').focus(function(){
            $('#create-new-route-choice').attr('checked', 'checked');
        });

        // By default, all groups are hidden.
        // The one that the user did not close in the session -
        // we will disclose right now
        var expand_group = (localStorage.getItem(that.theme_storage_key) || null),
            $divider_expand_icons = that.$form.find('i.js-divider-expand');

        $divider_expand_icons.each(function (i, icon) {
            var $divider = $(icon).parents('.js-theme-setting-divider'),
                divider_id = $divider.data('divider-id');

            if (expand_group == that.expand_all_storage_value || divider_id == expand_group) {
                $divider.find('.js-settings-group').show();
                $divider.find('i.js-divider-expand')
                        .attr('title', that.locale.expand)
                        .removeClass('js-divider-expand')
                        .addClass('js-divider-collapse')
                        .removeClass('rarr')
                        .addClass('darr');

                $divider.find('h1.js-divider-expand')
                        .removeClass('js-divider-expand')
                        .addClass('js-divider-collapse');

                that.$anchors.find('.js-anchor-item[data-divider-id="'+ divider_id +'"]').addClass('selected');
            }
        });

        if (expand_group == that.expand_all_storage_value) {
            that.$other_blocks.each(function () {
                that.expandOtherBlock($(this), true);
            });
        }

        //
        that.initThemeReameDialog();
        //
        that.initThemeExportSettingsDialog();
        //
        that.initThemeImportSettingsDialog();
        //
        that.initThemeDownloadDialog();
        //
        that.initThemeUpdateDialog();
        //
        that.initThemeParentDialog();
        //
        that.initThemeCopyDialog();
        //
        that.initThemeResetDialog();
        //
        that.initThemeStartUsingDialog();
        //
        that.initThemeDelete();
        //
        that.initAnchorLink();
        //
        that.initSettingsSearch();
        //
        that.initInvisibleSettings();
        //
        that.initExpandCollapseGroups();
        //
        that.initSettingsControl();
        //
        that.initScrollToTopButton();
        //
        that.initOtherDataBlocks();
        //
        that.initSubmit();
    };

    WAThemeSettings.prototype.initThemeReameDialog = function() {
        var that = this,
            $link = that.$wrapper.find('.theme-rename'),
            $dialog_wrapper = that.$wrapper.find('#wa-theme-name-dialog');

        $link.on('click', function () {
            $dialog_wrapper.waDialog({
                disableButtonsOnSubmit: true,
                onSubmit: function () {
                    var id = $.trim($dialog_wrapper.find('#wa-theme-rename-id').val()),
                        name = $.trim($dialog_wrapper.find('#wa-theme-rename-name').val()),
                        href= '?module=design&action=themeRename',
                        data = {
                            theme: that.theme_id,
                            id: id,
                            name: name
                        };

                    $.post(href, data, function (response) {
                        $dialog_wrapper.hide();
                        if (response.status == 'ok') {
                            if(response.data.redirect) {
                                location.href = location.href.replace(/(\?|#).*$/,'') + response.data.redirect;
                                location.reload();
                            } else {
                                location.reload();
                            }
                        } else {
                            alert(response.errors);
                        }
                    }, "json");
                    return false;
                }
            });
            return false;
        });

    };

    WAThemeSettings.prototype.initThemeExportSettingsDialog = function () {
        var that = this,
            $export_button = that.$wrapper.find('.js-export-theme-settings'),
            $export_error = that.$wrapper.find('.js-export-error'),
            $export_error_caption = that.$wrapper.find('.js-export-error-caption'),
            href = $export_button.attr('href');

        $export_button.on('click', function (e) {
            $.ajax({
                url: href,
                type: 'POST',
                async: false,
                cache: false,
            }).done(function(res) {
                if (res.status === 'fail') {
                    var app_link = '';
                    if (res.errors.app_name != null) {
                        app_link = '<a href="' + res.errors.app_url + '">' +
                                    res.errors.app_name + ' - ' + res.errors.appearance_name + '</a>';
                    }
                    $export_error_caption.html(res.errors.message + app_link);
                    $export_error.slideDown();
                    $export_button.replaceWith("<span class='js-export-theme-settings gray'>" + $export_button.html() + "</span>");
                    e.preventDefault();
                } else {
                    $export_error_caption.empty();
                    $export_error.slideUp();
                }
            });
        });
    };

    WAThemeSettings.prototype.initThemeImportSettingsDialog = function () {
        var that = this,
            $link = that.$wrapper.find('.js-import-theme-settings'),
            $dialog_wrapper = that.$wrapper.find('#wa-theme-import-settings-dialog');

        $link.on('click', function () {
            $dialog_wrapper.waDialog({
                onLoad: function () {
                    var dialog = this;
                    new waThemeSettingsImport({
                        $wrapper: $dialog_wrapper,
                        theme_id: that.theme_id,
                        dialog: dialog
                    });
                }
            });
            return false;
        });
    };

    WAThemeSettings.prototype.initThemeDownloadDialog = function () {
        var that = this,
            $link = that.$wrapper.find('.theme-download'),
            $dialog_wrapper = that.$wrapper.find('#wa-theme-download-dialog');

        $link.on('click', function () {
            $dialog_wrapper.waDialog();
            return false;
        });
    };

    WAThemeSettings.prototype.initThemeUpdateDialog = function() {
        var that = this,
            $link = that.$wrapper.find('.theme-update'),
            $dialog_wrapper = that.$wrapper.find('#wa-theme-update-dialog'),
            href = '?module=design&action=themeUpdate&theme='+that.theme_id;

        $link.on('click', function () {
            if (!$(this).hasClass('disabled'))  {
                $dialog_wrapper.waDialog({
                    url: href,
                    disableButtonsOnSubmit: true,
                    onLoad: function () {
                        $(this).on('change', 'label.bold input:checkbox', function () {
                            var l = $(this).parent();
                            if ($(this).is(':checked')) {
                                if (!l.find('span.hint').length) {
                                    l.append(' <span class="hint">'+ that.locale.will_be_lost +'</span>');
                                }
                            } else {
                                l.find('span.hint').remove();
                            }
                        });
                    },
                    onSubmit: function () {
                        if (confirm(that.locale.update_notice)) {
                            var data = $(this).serialize();
                            $.post(href, data, function (response) {
                                if (response.status == 'ok') {
                                    location.reload();
                                } else {
                                    alert(response.errors);
                                }
                            }, "json");
                        } else {
                            $(this).find(':submit').removeAttr('disabled');
                        }
                        return false;
                    }
                });
            }
            return false;
        });
    };

    WAThemeSettings.prototype.initThemeParentDialog = function() {
        var that = this,
            $link = that.$wrapper.find('.theme-parent'),
            $dialog_wrapper = that.$wrapper.find('#wa-theme-parent-dialog'),
            href = "?module=design&action=themeParent";

        $link.on('click', function () {
            $dialog_wrapper.waDialog({
                disableButtonsOnSubmit: true,
                onSubmit: function () {
                    var data = $(this).serialize();
                    $.post(href, data, function (response) {
                        $dialog_wrapper.hide();
                        if (response.status == 'ok') {
                            location.reload();
                        } else {
                            alert(response.errors);
                        }
                    }, "json");
                    return false;
                }
            });
            return false;
        });
    };

    WAThemeSettings.prototype.initThemeCopyDialog = function() {
        var that = this,
            $link = that.$wrapper.find('.theme-copy'),
            $dialog_wrapper = that.$wrapper.find('#wa-theme-copy-dialog');

        $link.on('click', function () {

            var themeCopy = function (related,options) {
                var href = "?module=design&action=themeCopy",
                    data = {
                        theme: that.theme_id,
                        related: related,
                        options:options
                    };

                $.post(href, data, function (response) {
                    $dialog_wrapper.hide();
                    if (response.status == 'ok') {
                        if (response.data.redirect) {
                            location.href = location.href.replace(/#.*$/, '') + response.data.redirect;
                            location.reload();
                        } else {
                            location.reload(true);
                        }
                    } else {
                        alert(response.errors);
                    }
                }, "json");
            };

            if ($(this).data('related')) {
                $dialog_wrapper.waDialog({
                    disableButtonsOnSubmit: true,
                    onSubmit: function () {
                        var $form = $(this),
                            options = {
                                id: $form.find("#wa-theme-copy-id").val(),
                                name: $form.find("#wa-theme-copy-name").val()
                            };
                        themeCopy($form.find(':input:checked').val(), options);
                        return false;
                    }
                });
            } else {
                themeCopy(false,null);
            }
            return false;
        });
    };

    WAThemeSettings.prototype.initThemeResetDialog = function() {
        var that = this,
            $link = that.$wrapper.find('.theme-reset'),
            $dialog_wrapper = that.$wrapper.find('#wa-theme-reset-dialog'),
            href = "?module=design&action=themeReset";

        $link.on('click', function () {
            if (!$(this).hasClass('disabled'))  {
                $dialog_wrapper.waDialog({
                    disableButtonsOnSubmit: true,
                    onSubmit: function () {
                        $.post(href, $(this).serialize(), function (response) {
                            if (response.status == 'ok') {
                                if(response.data.redirect) {
                                    location.href = location.href.replace(/(\?|#).*$/,'') + response.data.redirect;
                                    location.reload();
                                } else {
                                    location.reload();
                                }
                            } else {
                                alert(response.errors);
                            }
                        }, "json");
                        return false;
                    }
                });
            }
            return false;
        });
    };

    WAThemeSettings.prototype.initThemeStartUsingDialog = function() {
        var that = this,
            $link = that.$wrapper.find('#theme-start-using'),
            $dialog_wrapper = that.$wrapper.find('#wa-theme-start-using-dialog'),
            href = "?module=design&action=themeUse";

        $link.on('click', function () {
            if (!$(this).hasClass('disabled'))  {
                $dialog_wrapper.waDialog({
                    height: '420px',
                    disableButtonsOnSubmit: true,
                    onSubmit: function () {
                        $.post(href, $(this).serialize(), function (response) {
                            if (response.status == 'ok') {
                                location.href = that.design_url+'theme=' + response.data.theme + '&domain=' + response.data.domain + '&route=' + response.data.route;
                                location.reload();
                            } else {
                                alert(response.errors);
                            }
                        }, "json");
                        return false;
                    }
                });
            }
            return false;
        });
    };

    WAThemeSettings.prototype.initAnchorLink = function() {
        var that = this;

        // Go to divider
        that.$anchors.on('click', '.js-anchor-item', function (e) {
            e.preventDefault();
            var divider_id = $(this).data('divider-id'),
                $divider = that.$form.find('div[data-divider-id="'+ divider_id +'"][data-divider-level="1"]');

            that.expandGroup($divider);

            that.$theme_navigation.trigger("is_anchor_set");

            $('html, body').animate({ scrollTop: $divider.offset().top });
        });

        // Go to other block
        that.$anchors.on('click', '.js-other-anchor-item', function (e) {
            e.preventDefault();

            var other_block_id = $(this).data('other-id'),
                $other_block = that.$wrapper.find('.js-theme-other-data[data-id="'+ other_block_id +'"]');

            that.expandOtherBlock($other_block);

            that.$theme_navigation.trigger("is_anchor_set");

            $('html, body').animate({ scrollTop: $other_block.offset().top });
        });
    };

    WAThemeSettings.prototype.initThemeDelete = function() {
        var that = this,
            $link = that.$wrapper.find('.theme-delete'),
            href = "?module=design&action=themeDelete";

        $link.on('click', function () {
            var $self = $(this);

            if (that.theme_routes.length) {
                var $dialog_wrapper = that.$wrapper.find('#wa-theme-blocking-removal-dialog');
                $dialog_wrapper.waDialog();

                return false;
            }

            if (!$self.hasClass('disabled') && confirm($self.data('confirm'))) {
                $.post(href, { theme: that.theme_id }, function (response) {
                    if (response.status === 'ok') {
                        if(response.data.theme_id) {
                            $('#wa-theme-block-' + response.data.theme_id).remove();
                            $('#wa-theme-list-' + response.data.theme_id).remove();
                        }
                        $('#wa-theme-list a').each(function () {
                            if ($(this).attr('href').indexOf('theme=' + that.theme_id) != -1) {
                                $(this).parent().remove();
                            }
                        });
                        alert($self.data('success'));
                        location.href = $('#wa-theme-list li:first a').attr('href');
                    } else {
                        alert(response.errors);
                    }
                }, "json");

            }
            return false;
        });
    };

    WAThemeSettings.prototype.initSettingsSearch = function() {
        var that = this,
            timer = null,
            $search_input = that.$search_input,
            $result_min_symbol = that.$wrapper.find('.js-search-min-symbol'),
            $result_label = that.$wrapper.find('.js-search-result'),
            $no_result_label = that.$wrapper.find('.js-search-no-result');

        // Show all group settings in search mode
        that.$wrapper.on('click', '.js-group-all-settings', function () {
            var $divider = $(this).parents('.js-theme-setting-divider[data-divider-level="1"]');

            $(this).hide();
            $divider.find('.js-search-item').show();
        });

        $search_input.on('input', function (e) {
            var q = $.trim($(this).val());

            that.setExpandAllItems();
            that.$anchors.hide();

            timer && clearTimeout(timer);
            timer = setTimeout(function(){
                settingSearch(q);
            }, 400);
        });

        function settingSearch(query) {
            var $settings_list = that.$settings_list,
                filter = new RegExp(query, 'i'),
                query_length = query.length,
                empty_query = query_length === 0,
                small_query = query_length < 3,
                results = false;

            $result_label.hide();
            $no_result_label.hide();
            $result_min_symbol.hide();
            that.$wrapper.find('.js-group-all-settings').hide();
            that.$wrapper.find('.js-theme-other-data').hide();

            // Collapse all global dividers
            if (!small_query) {
                that.$global_dividers.each(function () {
                    $(this).hide();
                });
            }

            $settings_list.find('.js-search-item').each(function () {
                var $item = $(this),
                    item_name = $item.data('name'),
                    $item_search_name = $item.find('.js-search-item-name'),
                    $divider = $item.parents('.js-theme-setting-divider[data-divider-level="1"]'),
                    data_search = '' + $item.data('search'),
                    matched = null;

                if (small_query) {
                    $item_search_name.html(item_name);
                    $item.show();
                    $divider.show();
                    that.collapseGroup($divider);
                    return;
                }

                if (filter) {
                    matched = data_search.match(filter);
                }

                if (matched) {
                    $item.show();
                    $item.parents('.js-settings-group').siblings('.js-divider-name').show(); // Show all parents divider names
                    $divider.show();
                    that.expandGroup($divider); // Expand global divider
                    if (!empty_query) {
                        var match_value = $("<div />").text(matched[0]).html();
                        item_name = item_name.replace(filter, '<span class="wa-setting-highlight">' + match_value + '</span>');
                    }
                } else {
                    $item.hide();
                }
                $item_search_name.html(item_name);
            });

            // Expand all global dividers on empty query
            if (empty_query) {
                that.$global_dividers.each(function () {
                    $(this).show();
                });
                that.$wrapper.find('.js-theme-other-data').show();
            }

            that.$global_dividers.each(function () {
                if ($(this).is(':visible')) {
                    return results = true;
                }
            });

            if (!empty_query) {
                if (small_query) {
                    $result_min_symbol.show();
                } else if (!results) {
                    $no_result_label.show();
                } else {
                    $result_label.show();
                }
            } else {
                // Collapse all group if empty search query
                that.$global_dividers.each(function () {
                    that.collapseGroup($(this));
                });
            }
        }
    };

    WAThemeSettings.prototype.initInvisibleSettings = function() {
        var that = this,
            $checkbox = that.$wrapper.find('.js-show-invisible-settings'),
            $wrapper = that.$wrapper.find('.js-hidden-settings-wrapper'),
            $hidden_settings = that.$form.find('.invisible-setting');

        if ($hidden_settings.length) {
            $wrapper.removeAttr('style');
        }

        $checkbox.on('change', function () {
            var is_checked = $checkbox.is(':checked');

            $hidden_settings.each(function () {
                if (is_checked) {
                    $(this).removeClass('invisible-setting');
                } else {
                    $(this).addClass('invisible-setting');
                }
            });
        });
    };

    WAThemeSettings.prototype.initExpandCollapseGroups = function() {
        var that = this,
            $dividers = that.$global_dividers,
            $other_blocks = that.$other_blocks,
            expanded_group = localStorage.getItem(that.theme_storage_key);

        // If the design theme is not used or it does not provide settings, then there’s no need to
        if (!that.$theme_navigation.length) {
            return false;
        }

        var fixedBlock = initFixedBlock();
        fixedBlock.is_disabled = true;

        if (expanded_group === that.expand_all_storage_value) {
            that.setCollapseAllItems();
            disableFixedBlock(false);
        } else {
            that.setExpandAllItems();
            disableFixedBlock(true);
        }

        // Expand all
        that.$theme_navigation.on('click', '.'+ that.classes.expand_all, function () {
            that.setCollapseAllItems();

            $dividers.each(function () {
                that.expandGroup($(this), true);
            });

            $other_blocks.each(function () {
                that.expandOtherBlock($(this), true);
            });

            localStorage.setItem(that.theme_storage_key, that.expand_all_storage_value);

            disableFixedBlock(false);
        });

        // Collapse all
        that.$theme_navigation.on('click', '.'+ that.classes.collapse_all, function (e) {
            that.setExpandAllItems();

            $dividers.each(function (index, divider) {
                that.collapseGroup($(divider))
            });

            $other_blocks.each(function () {
                that.collapseOtherBlock($(this), true);
            });

            localStorage.removeItem(that.theme_storage_key);

            disableFixedBlock(true);
        });

        // Expand divider
        that.$form.on('click', '.'+ that.classes.divider_expand, function (e) {
            e.preventDefault();
            var $divider = $(this).parents('.js-theme-setting-divider');
            that.expandGroup($divider);
            $('html, body').animate({ scrollTop: $divider.offset().top });
        });

        // Collapse divider
        that.$form.on('click', '.'+ that.classes.divider_collapse, function (e) {
            e.preventDefault();
            var $divider = $(this).parents('.js-theme-setting-divider');
            that.collapseGroup($divider);

            localStorage.removeItem(that.theme_storage_key);
        });

        that.$theme_navigation.on("is_anchor_set", function() {
            disableFixedBlock(true);
        });

        function disableFixedBlock(disable) {
            fixedBlock.is_disabled = !!disable;
            $(window).trigger("scroll");
        }

        function initFixedBlock() {
            /**
             * @class FixedBlock
             * @description used for fixing form buttons
             * */
            var FixedBlock = ( function($) {

                FixedBlock = function(options) {
                    var that = this;

                    // DOM
                    that.$window = $(window);
                    that.$wrapper = options["$section"];
                    that.$wrapperW = options["$wrapper"];
                    that.$form = that.$wrapper.parents('form');

                    // VARS
                    that.type = (options["type"] || "bottom");
                    that.lift = (options["lift"] || 0);

                    // DYNAMIC VARS
                    that.offset = {};
                    that.$clone = false;
                    that.is_fixed = false;
                    that.is_disabled = false;

                    // INIT
                    that.initClass();
                };

                FixedBlock.prototype.initClass = function() {
                    var that = this,
                        $window = that.$window,
                        resize_timeout = 0;

                    $window.on("resize", function() {
                        clearTimeout(resize_timeout);
                        resize_timeout = setTimeout( function() {
                            that.resize();
                        }, 100);
                    });

                    $window.on("scroll", watcher);

                    that.$wrapper.on("resize", function() {
                        that.resize();
                    });

                    that.$form.on("input", function () {
                        that.resize();
                    });

                    that.init();

                    function watcher() {
                        var is_exist = $.contains($window[0].document, that.$wrapper[0]);
                        if (is_exist) {
                            if (!that.is_disabled) {
                                that.onScroll($window.scrollTop());
                            } else {
                                that.clear();
                            }
                        } else {
                            $window.off("scroll", watcher);
                        }
                    }

                    that.$wrapper.data("block", that);
                };

                FixedBlock.prototype.init = function() {
                    var that = this;

                    if (!that.$clone) {
                        var $clone = $("<div />").css("margin", "0");
                        that.$wrapper.after($clone);
                        that.$clone = $clone;
                    }

                    that.$clone.hide();

                    var offset = that.$wrapper.offset();

                    that.offset = {
                        left: offset.left,
                        top: offset.top,
                        width: that.$wrapper.outerWidth(),
                        height: that.$wrapper.outerHeight()
                    };
                };

                FixedBlock.prototype.resize = function() {
                    var that = this;

                    switch (that.type) {
                        case "top":
                            that.fix2top(false);
                            break;
                        case "bottom":
                            that.fix2bottom(false);
                            break;
                    }

                    var offset = that.$wrapper.offset();
                    that.offset = {
                        left: offset.left,
                        top: offset.top,
                        width: that.$wrapper.outerWidth(),
                        height: that.$wrapper.outerHeight()
                    };

                    that.$window.trigger("scroll");
                };

                /**
                 * @param {Number} scroll_top
                 * */
                FixedBlock.prototype.onScroll = function(scroll_top) {
                    var that = this,
                        window_w = that.$window.width(),
                        window_h = that.$window.height();

                    // update top for dynamic content
                    that.offset.top = (that.$clone && that.$clone.is(":visible") ? that.$clone.offset().top : that.$wrapper.offset().top);

                    switch (that.type) {
                        case "top":
                            var use_top_fix = (that.offset.top - that.lift < scroll_top);

                            that.fix2top(use_top_fix);
                            break;
                        case "bottom":
                            var use_bottom_fix = (that.offset.top && scroll_top + window_h < that.offset.top + that.offset.height);
                            that.fix2bottom(use_bottom_fix);
                            break;
                    }

                };

                /**
                 * @param {Boolean|Object} set
                 * */
                FixedBlock.prototype.fix2top = function(set) {
                    var that = this,
                        fixed_class = "is-top-fixed";

                    if (set) {
                        that.$clone.css({
                            height: that.$wrapper.outerHeight()
                        }).show();

                        that.$wrapper
                            .css({
                                position: "fixed",
                                top: that.lift,
                                left: that.offset.left,
                                width: that.$clone.width()
                            })
                            .addClass(fixed_class);

                    } else {
                        that.$wrapper.removeClass(fixed_class).removeAttr("style");
                        that.$clone.removeAttr("style").hide();
                    }

                    that.is_fixed = !!set;
                };

                /**
                 * @param {Boolean|Object} set
                 * */
                FixedBlock.prototype.fix2bottom = function(set) {
                    var that = this,
                        fixed_class = "is-bottom-fixed";

                    if (set) {
                        that.$clone.css({
                            height: that.$wrapper.outerHeight()
                        }).show();

                        that.$wrapper
                            .css({
                                position: "fixed",
                                bottom: 0,
                                left: that.offset.left,
                                width: that.$clone.width()
                            })
                            .addClass(fixed_class);

                    } else {
                        that.$wrapper.removeClass(fixed_class).removeAttr("style");
                        that.$clone.removeAttr("style").hide();
                    }

                    that.is_fixed = !!set;
                };

                FixedBlock.prototype.clear = function() {
                    var that = this;

                    that.$wrapper.removeClass("is-top-fixed").removeClass("is-bottom-fixed").removeAttr("style");
                    that.$clone.removeAttr("style").hide();
                };

                return FixedBlock;

            })(jQuery);

            return new FixedBlock({
                $wrapper: that.$theme_navigation.closest(".wa-theme-content"),
                $section: that.$theme_navigation,
                type: "top"
            });
        }
    };

    WAThemeSettings.prototype.initSettingsControl = function () {
        var that = this;

        // Delete image
        that.$wrapper.on('click', 'a.delete-image', function () {
            $(this).closest('div.value').find("input:hidden").val('');
            $(this).parent().remove();
            return false;
        });

        // Select image
        that.$wrapper.on('click', '.wa-theme-image-select a', function () {
            var li = $(this).parent(),
                ul = li.parent();

            ul.find('li.selected').removeClass('selected');
            li.addClass('selected');
            ul.next('input').val(li.data('value'));
            return false;
        });

        var input2textarea = function(input) {
            var p = input.parent(),
                rm = false;

            if (!p.length) {
                p = $('<div></div>');
                p.append(input);
                rm = true;
            }
            var val = input.val(),
                html = p.html();

            html = html.replace(/value(\s*?=\s*?['"][\s\S]*?['"])*/, '');
            html = html.replace(/type\s*?=\s*?['"]text['"]/, '');
            html = html.replace('input', 'textarea');
            html = html.replace(/(\/\s*?>|>)/, '></textarea>');


            if (rm) {
                p.remove();
            }

            return $(html).val(val);
        };

        var textarea2input = function(textarea) {
            var p = textarea.parent(),
                rm = false;

            if (!p.length) {
                p = $('<div></div>');
                p.append(textarea);
                rm = true;
            }
            var val = textarea.val(),
                html = p.html();

            html = html.replace('textarea', 'input type="text"');
            html = html.replace('</textarea>', '');

            if (rm) {
                p.remove();
            }

            return $(html).val(val);
        };

        that.$wrapper.find('.flexible').each(function () {
            var timeout = 250,
                threshold = 50,
                height = 45,
                timer_id = null,
                field = $(this);

            var onFocus = function() {
                this.selectionStart = this.selectionEnd = this.value.length;
            };
            var handler = function() {
                if (timer_id) {
                    clearTimeout(timer_id);
                    timer_id = null;
                }
                timer_id = setTimeout(function() {
                    var val = field.val();
                    if (val.length > threshold && field.is('input')) {
                        var textarea = input2textarea(field);
                        textarea.css('height', height);
                        field.replaceWith(textarea);
                        field = textarea;
                        field.focus();
                    } else if (val.length <= threshold && field.is('textarea')) {
                        var input = textarea2input(field);
                        input.css('height', '');
                        field.replaceWith(input);
                        field = input;
                        field.focus();
                    }
                }, timeout);
            };

            var p = field.parent();
            p.off('keydown', '#' + field.attr('id')).on('keydown', '#' + field.attr('id'), handler);
            p.off('focus', '#' + field.attr('id')).on('focus', '#' + field.attr('id'), onFocus);
        });

        // Colorpickers
        that.$form.find('.color').each(function() {
            var $input = $(this),
                $replacer = $('<span class="color-replacer"><i class="icon16 color" style="background: #'+$input.val().substr(1)+'"></i></span>').insertAfter($input),
                $picker = $('<div style="display:none;" class="color-picker"></div>').insertAfter($replacer),
                farbtastic = $.farbtastic($picker, function(color) {
                    $replacer.find('i').css('background', color);
                    $input.val(color);
                });

            farbtastic.setColor('#'+$input.val());

            $replacer.click(function() {
                $picker.slideToggle(200);
                return false;
            });

            var timer_id;
            $input.unbind('keydown').bind('keydown', function() {
                if (timer_id) {
                    clearTimeout(timer_id);
                }
                timer_id = setTimeout(function() {
                    farbtastic.setColor($input.val());
                }, 250);
            });

            $picker.on('click', function () {
                that.$button.removeClass('green').addClass('yellow');
            });
        });
    };

    WAThemeSettings.prototype.initScrollToTopButton = function() {
        var that = this,
            $button = $('#wa-design-scroll-top'),
            top_show = 300;

        // If the design theme is not used or it does not provide settings, then there’s no need to
        if (!that.$theme_navigation.length) {
            return false;
        }

        $(document).ready(function() {
            $(window).scroll(function () {
                if ($(this).scrollTop() > top_show) {
                    $button.addClass('visible');
                } else {
                    $button.removeClass('visible');
                }
            });

            $button.click(function () {
                $('body, html').animate({
                    scrollTop: 0
                }, 'fast');
            });
        });
    };

    WAThemeSettings.prototype.initOtherDataBlocks = function() {
        var that = this;

        that.$wrapper.on('click', '.js-other-label', function () {
            var $block = $(this).parents('.js-theme-other-data'),
                $content = $block.find('.js-other-content'),
                is_visible = $content.is(':visible');

            if (is_visible) {
                that.collapseOtherBlock($block);
            } else {
                that.expandOtherBlock($block);
            }
        });
    };

    WAThemeSettings.prototype.setExpandAllItems = function() {
        var that = this,
            $icon16 = that.$expand_collapse_all.find('.icon16'),
            $action_text = that.$expand_collapse_all.find('.js-action-text');

        that.$anchors.hide();
        that.$expand_collapse_all.removeClass(that.classes.collapse_all).addClass(that.classes.expand_all);
        $icon16.removeClass('darr').addClass('rarr');
        $action_text.text(that.locale.expand_all);
    };

    WAThemeSettings.prototype.setCollapseAllItems = function() {
        var that = this,
            $icon16 = that.$expand_collapse_all.find('.icon16'),
            $action_text = that.$expand_collapse_all.find('.js-action-text');

        that.$anchors.show();
        that.$expand_collapse_all.removeClass(that.classes.expand_all).addClass(that.classes.collapse_all);
        $icon16.removeClass('rarr').addClass('darr');
        $action_text.text(that.locale.collapse_all);
    };

    WAThemeSettings.prototype.expandGroup = function($divider, not_collapse_other) {
        var that = this,
            not_collapse_other = (not_collapse_other || false),
            divider_id = $divider.data('divider-id'),
            search_mode = !!($.trim(that.$search_input.val()));

        if (!search_mode && !not_collapse_other) {
            // Close groups
            that.$global_dividers.each(function () {
                that.collapseGroup($(this));
            });
        }

        if (!not_collapse_other) {
            // Close anchors
            that.setExpandAllItems();

            // Close other blocks
            that.$other_blocks.each(function () {
                that.collapseOtherBlock($(this));
            });
        }

        if (search_mode) {
            // In search mode - show a link that unfolds the entire group
            $divider.find('.js-group-all-settings').removeAttr('style');
        } else {
            // If not search mode - always show group divider name
            // In the search mode, the names are shown separately
            $divider.find('.js-divider-name').show();
        }

        $divider.find('.js-settings-group').show();

        // Divider arrow icon
        $divider.find('i.js-divider-expand')
            .attr('title', that.locale.collapse)
            .removeClass('js-divider-expand')
            .addClass('js-divider-collapse')
            .removeClass('rarr')
            .addClass('darr');

        // GLOBAL Divider name
        $divider.find('h1.js-divider-expand')
            .removeClass('js-divider-expand')
            .addClass('js-divider-collapse');

        if (!search_mode) {
            localStorage.setItem(that.theme_storage_key, divider_id);
        }

        // Update anchors
        that.$anchors.find('.js-anchor-item[data-divider-id="'+ divider_id +'"]').addClass('selected');
    };

    WAThemeSettings.prototype.collapseGroup = function($divider) {
        var that = this,
            divider_id = $divider.data('divider-id');

        $divider.find('.js-settings-group').hide();

        // Divider arrow icon
        $divider.find('i.js-divider-collapse')
            .attr('title', that.locale.expand)
            .removeClass('js-divider-collapse')
            .addClass('js-divider-expand')
            .removeClass('darr')
            .addClass('rarr');

        // GLOBAL Divider name
        $divider.find('h1.js-divider-collapse')
            .removeClass('js-divider-collapse')
            .addClass('js-divider-expand');

        // Update anchors
        that.$anchors.find('.js-anchor-item[data-divider-id="'+ divider_id +'"]').removeClass('selected');

        localStorage.removeItem(that.theme_storage_key);
    };

    WAThemeSettings.prototype.expandOtherBlock = function($other_block, not_collapse_other) {
        var that = this,
            not_collapse_other = (not_collapse_other || false),
            block_id = $other_block.data('id'),
            $label = $other_block.find('.js-other-label'),
            $icon16 = $label.find('.icon16'),
            $content = $other_block.find('.js-other-content');

        if (!not_collapse_other) {
            // Close anchors
            that.setExpandAllItems();

            // Close groups
            that.$global_dividers.each(function () {
                that.collapseGroup($(this));
            });

            // Close other blocks
            that.$other_blocks.each(function () {
                that.collapseOtherBlock($(this));
            });
        }

        $icon16.removeClass('rarr').addClass('darr');
        $content.show();

        that.$anchors.find('.js-other-anchor-item[data-other-id="'+ block_id +'"]').addClass('selected');
    };

    WAThemeSettings.prototype.collapseOtherBlock = function($other_block) {
        var that = this,
            block_id = $other_block.data('id'),
            $label = $other_block.find('.js-other-label'),
            $icon16 = $label.find('.icon16'),
            $content = $other_block.find('.js-other-content');

        $icon16.removeClass('darr').addClass('rarr');
        $content.hide();

        that.$anchors.find('.js-other-anchor-item[data-other-id="'+ block_id +'"]').removeClass('selected');
    };

    WAThemeSettings.prototype.initSubmit = function () {
        var that = this,
            $iframe = that.$wrapper.find('#theme-settings-iframe');

        that.$form.on('input', function () {
            that.$button.removeClass('green').addClass('yellow');
        });

        that.$form.submit(function () {
            $iframe.one('load', function () {
                var response = $.parseJSON($(this).contents().find('body').html());

                if (response.status == 'ok') {
                    that.$button.removeClass('yellow').addClass('green');
                    that.$error.hide().empty();
                    that.$message.fadeIn('slow', function () { $(this).fadeOut('slow');});
                    waDesignLoad();
                } else {
                    that.$error.html(response.errors ? response.errors : response);
                    that.$error.fadeIn("slow");
                }
            });
        });
    };

    return WAThemeSettings;

})(jQuery);

var waThemeSettingsImport = ( function($) {

    waThemeSettingsImport = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$form = that.$wrapper.find('form');
        that.$input_wrapper = that.$form.find('.js-input-wrapper');
        that.$input = that.$input_wrapper.find('input[name=theme_settings]');
        that.$archive_name = that.$form.find('.js-archive-name');
        that.$submit = that.$form.find('input[type=submit]');
        that.$loading = that.$form.find('i.loading');

        // VARS
        that.theme_id = options["theme_id"];
        that.dialog = options["dialog"];

        // DYNAMIC VARS

        // INIT
        that.initClass();
    };

    waThemeSettingsImport.prototype.initClass = function() {
        var that = this;

        //
        that.reInitDialog();
        //
        that.initChangeInput();
        //
        that.initSubmit();
    };

    waThemeSettingsImport.prototype.initChangeInput = function() {
        var that = this;

        that.$input.on('change', function (e) {
            var file = that.$input[0].files[0];

            if (file) {
                that.$submit.prop('disabled', false);
                that.$input_wrapper.hide();
                that.$archive_name.text(file.name);
            } else {
                that.reInitDialog();
            }
        });
    };

    waThemeSettingsImport.prototype.initSubmit = function() {
        var that = this,
            $error = that.$wrapper.find('.js-error-place'),
            href = '?module=design&action=themeImportSettings&theme='+that.theme_id;

        that.$form.on('submit', function (e) {
            e.preventDefault();
            var file = that.$input[0].files[0];

            if (!file) {
                that.reInitDialog();
                return;
            }

            var formData = new FormData();
            formData.append("theme_settings", file);

            var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)")),
                csrf = matches ? decodeURIComponent(matches[1]) : '';
            if (csrf) {
                formData.append("_csrf", csrf);
            }

            that.$loading.show();

            $.ajax({
                url: href,
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false
            }).done(function(res) {
                if (res.status === "ok") {
                    location.reload();
                } else if (res.errors) {
                    $error.text(res.errors);
                    $(that.dialog).trigger('wa-resize');
                    setTimeout(function(){
                        that.reInitDialog();
                    }, 5000);
                }
            });

            that.$input.val('');
            that.$loading.hide();
        });
    };

    waThemeSettingsImport.prototype.reInitDialog = function() {
        var that = this,
            $error = that.$wrapper.find('.js-error-place');

        $error.text('');
        that.$input.val('');
        that.$submit.prop('disabled', true);
        that.$input_wrapper.show();
        that.$archive_name.text('');
    };

    return waThemeSettingsImport;

})(jQuery);