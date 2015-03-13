(function ($) {
    "use strict";

    $.storage = new $.store();
    $.wa.mailer = {
        options: {
            lang: 'en'
        },
        init: function (options) {
            if ("undefined" !== typeof($.History)) {
                $.History.bind(function () {
                    $.wa.mailer.dispatch();
                });
            }
            this.options = $.extend(this.options, options);
            var hash = window.location.hash;
            if (hash === '#/' || !hash) {
                hash = $.storage.get('mailer/hash');
                if (hash && hash != null) {
                    $.wa.setHash('#/' + hash);
                } else {
                    this.dispatch();
                }
            } else {
                $.wa.setHash(hash);
            }

            $(window).bind('wa.dispatched', function () {
                // Highlight current item in sidebar, if exists
                $.wa.mailer.highlightSidebar();

                // Remove all non-persistent dialogs
                $('.dialog:not(.persistent)').empty().remove();
            });

            // Set up AJAX error handler
            $.wa.errorHandler = function () {
            };
            $(document).ajaxError(function (e, xhr, settings, exception) {

                // Never save pages causing an error as last hashes
                $.storage.del('mailer/hash');
                $.wa.mailer.stopDispatch(1);
                window.location.hash = '';

                // Show error in a nice safe iframe
                if (xhr.responseText) {
                    var iframe = $('<iframe src="about:blank" style="width:100%;height:auto;min-height:500px;"></iframe>');
                    $("#content").addClass('shadowed').empty().append(iframe);
                    var ifrm = (iframe[0].contentWindow) ? iframe[0].contentWindow : (iframe[0].contentDocument.document) ? iframe[0].contentDocument.document : iframe[0].contentDocument;
                    ifrm.document.open();
                    ifrm.document.write(xhr.responseText);
                    ifrm.document.close();

                    // Close all existing dialogs
                    $('.dialog:visible').trigger('close').remove();
                }
            });

            // Collapsible sidebar sections
            var toggleCollapse = function () {
                $.wa.mailer.collapseSidebarSection(this, 'toggle');
            };
            $(".collapse-handler", $('#wa-app')).die('click').live('click', toggleCollapse);
            this.restoreCollapsibleStatusInSidebar();

            // Reload sidebar once a minute when there are campaigns currently sending
            setTimeout($.wa.mailer.updateSendingSidebar, 60000);

            // development hotkeys for redispatch and sidebar reloading
            $(document).keypress(function (e) {
                if ((e.which == 10 || e.which == 13) && e.shiftKey) {
                    $('#wa-app .sidebar .icon16').first().attr('class', 'icon16 loading');
                    $.wa.mailer.reloadSidebar();
                }
                if ((e.which == 10 || e.which == 13) && e.ctrlKey) {
                    $.wa.mailer.redispatch();
                }
            });
        },

        // if this is > 0 then this.dispatch() decrements it and ignores a call
        skipDispatch: 0,

        /** Cancel the next n automatic dispatches when window.location.hash changes */
        stopDispatch: function (n) {
            this.skipDispatch = n;
        },

        /** Force reload current hash-based 'page'. */
        redispatch: function () {
            this.currentHash = null;
            this.dispatch();
        },

        // last hash processed by this.dispatch()
        currentHash: null,

        /**
         * Called automatically when window.location.hash changes. Should not be called directly.
         * Call a corresponding handler by concatenating leading non-int parts of hash,
         * e.g. for #/aaa/bbb/ccc/111/dd/12/ee/ff
         * a method $.wa.controller.AaaBbbCccAction(['111', 'dd', '12', 'ee', 'ff']) will be called.
         */
        dispatch: function (hash) {
            if (this.skipDispatch > 0) {
                this.skipDispatch--;
                this.currentHash = null;
                return false;
            }
            if (hash == undefined) {
                hash = this.getHash();
            } else {
                hash = this.cleanHash(hash);
            }

            if (this.currentHash == hash) {
                return;
            }
            var old_hash = this.currentHash;
            this.currentHash = hash;

            var e = new $.Event('wa.before_dispatched');
            $(window).trigger(e);
            if (e.isDefaultPrevented()) {
                this.currentHash = old_hash;
                window.location.hash = old_hash;
                return false;
            }

            hash = hash.replace(/^[^#]*#\/*/, '');
            /* */
            if (hash) {
                hash = hash.split('/');
                if (hash[0]) {
                    var actionName = "";
                    var attrMarker = hash.length;
                    var i = 0;
                    for (i; i < hash.length; i++) {
                        var h = hash[i];
                        if (i < 2) {
                            if (i === 0) {
                                actionName = h;
                            } else if (parseInt(h, 10) != h && h.indexOf('=') == -1) {
                                actionName += h.substr(0, 1).toUpperCase() + h.substr(1);
                            } else {
                                attrMarker = i;
                                break;
                            }
                        } else {
                            attrMarker = i;
                            break;
                        }
                    }

                    var attr = hash.slice(attrMarker);

                    if (this[actionName + 'Action']) {
                        this[actionName + 'Action'].apply(this, attr);
                        // save last page to return to by default later
                        $.storage.set('mailer/hash', hash.join('/'));
                    } else {
                        if (console) {
                            console.log('Invalid action name:', actionName + 'Action');
                        }
                    }
                } else {
                    this.defaultAction();
                }
            } else {
                this.defaultAction();
            }

            $(window).trigger('wa.dispatched');
        },

        //
        // Actions called from this.dispatch()
        //

        defaultAction: function () {
            this.campaignsNewAction();
        },

        // List of templates
        templatesAction: function () {
            this.load("?module=templates");
        },

        // Import template from archive page
        templatesImportAction: function () {
            this.load("?module=templates&action=import1");
        },

        // Template editor
        templateAction: function (id) {
            this.load("?module=templates&action=edit&id=" + id);
        },

        // New template page
        templatesAddAction: function () {
            this.load("?module=templates&action=add");
        },

        // New campaign page: select template to use
        campaignsNewAction: function () {
            this.load("?module=campaigns&action=step0");
        },

        // Campaign editor: subject and body
        campaignsLetterAction: function (campaign_id, template_id) {
            if (campaign_id && campaign_id !== 'new') {
                this.load("?module=campaigns&action=step1&campaign_id=" + campaign_id);
                return;
            }

            var template = '';
            if (template_id) {
                template = '&template_id=' + template_id;
            }
            this.load("?module=campaigns&action=step1" + template);
        },

        // Campaign editor: recipients selection page
        campaignsRecipientsAction: function (p) {
            this.load("?module=campaigns&action=recipients&campaign_id=" + p);
        },

        // Campaign editor: settings
        campaignsSendAction: function (campaign_id) {
            this.load("?module=campaigns&action=settings&campaign_id=" + campaign_id);
        },

        // Report page for campaign sent or being sent
        campaignsReportAction: function (campaign_id) {
            this.load("?module=campaigns&action=report&campaign_id=" + campaign_id);
        },

        // Options used for campaign sent or being sent
        campaignsOptionsAction: function (campaign_id) {
            this.load("?module=campaigns&action=settingsReadOnly&campaign_id=" + campaign_id);
        },

        // List of campaigns currently sending
        campaignsSendingAction: function () {
            this.load("?module=campaigns&action=sending");
        },

        // List of campaigns successfully sent
        campaignsArchiveAction: function (start, order) {
            var search = decodeURIComponent($.wa.mailer.getHash().substr(('#/campaigns/archive/' + start + '/' + order + '/').length).replace('/', '')) || '';
            if (search) {
                search = '&search=' + encodeURIComponent(search);
            }
            start = start || '';
            if (start) {
                start = '&start=' + encodeURIComponent(start);
            }
            order = order || $.storage.get('mailer/archive_order') || '';
            if (order) {
                $.storage.set('mailer/archive_order', order);
                order = '&order=' + encodeURIComponent(order);
            }
            this.load("?module=campaigns&action=archive" + start + search + order);
        },

        // List of contacts unsubscribed from campaigns
        unsubscribedAction: function (start, records, order) {
            var search = decodeURIComponent($.wa.mailer.getHash().substr(('#/unsubscribed/' + start + '/' + records + '/' + order + '/').length).replace('/', '')) || '';
            if (search) {
                search = '&search=' + encodeURIComponent(search);
            }
            start = start || '';
            if (start) {
                start = '&start=' + encodeURIComponent(start);
            }
            records = records || $.storage.get('mailer/unsubscribed_records') || '';
            if (records) {
                records = '&records=' + encodeURIComponent(records);
            }
            order = order || $.storage.get('mailer/unsubscribed_order') || '';
            if (order) {
                $.storage.set('mailer/unsubscribed_order', order);
                order = '&order=' + encodeURIComponent(order);
            }
            this.load("?module=unsubscribed&action=list" + start + search + order + records);
        },

        // List of emails used to have delivering errors in the past
        undeliverableAction: function (start, records, order) {
            var search = decodeURIComponent($.wa.mailer.getHash().substr(('#/undeliverable/' + start + '/' + records + '/' + order + '/').length).replace('/', '')) || '';
            if (search) {
                search = '&search=' + encodeURIComponent(search);
            }
            start = start || '';
            if (start) {
                start = '&start=' + encodeURIComponent(start);
            }
            records = records || $.storage.get('mailer/undeliverable_records') || '';
            if (records) {
                records = '&records=' + encodeURIComponent(records);
            }
            order = order || $.storage.get('mailer/undeliverable_order') || '';
            if (order) {
                $.storage.set('mailer/undeliverable_order', order);
                order = '&order=' + encodeURIComponent(order);
            }
            this.load("?module=undeliverable&action=list" + start + search + order + records);
        },

        subscribersAction: function (list_id, start, order, records) {
            this.subscribersListAction(list_id, start, order, records);
        },

        subscribersFormAction: function (form_id) {
            if (form_id === 'new') {
                form_id = -1;
            }
            this.load("?module=subscribers&form_id=" + form_id);
        },

        // Show list of subscribers
        subscribersListAction: function (list_id, start, order, records) {
            var search = decodeURIComponent($.wa.mailer.getHash().substr(('#/subscribers/list/' + list_id + '/' + start + '/' + order + '/' + records + '/').length).replace('/', '')) || '';
            if (search) {
                search = '&search=' + encodeURIComponent(search);
            }
            start = start || '';
            if (start) {
                start = '&start=' + encodeURIComponent(start);
            }
            records = records || $.storage.get('mailer/subscribers_records') || '';
            if (records) {
                records = '&records=' + encodeURIComponent(records);
            }
            order = order || $.storage.get('mailer/subscribers_order') || '';
            if (order) {
                $.storage.set('mailer/subscribers_order', order);
                order = '&order=' + encodeURIComponent(order);
            }

            list_id = list_id || '';
            if (list_id === 'new') {
                list_id = '&id=-1';
            }
            else if (list_id) {
                list_id = '&id=' + list_id;
            }

            this.load("?module=subscribers" + list_id + start + search + order + records);
//        this.load("?module=subscribers&action=list"+list_id+start+search+order);
        },

        subscribersoldAction: function (start, order) {
            var search = decodeURIComponent($.wa.mailer.getHash().substr(('#/subscribersold/' + start + '/' + order + '/').length).replace('/', '')) || '';
            if (search) {
                search = '&search=' + encodeURIComponent(search);
            }
            start = start || '';
            if (start) {
                start = '&start=' + encodeURIComponent(start);
            }
            order = order || $.storage.get('mailer/subscribers_order') || '';
            if (order) {
                $.storage.set('mailer/subscribers_order', order);
                order = '&order=' + encodeURIComponent(order);
            }

            this.load("?module=subscribers&action=listold" + start + search + order);
        },

        // Test message with SpamAssassin
        spamtestAction: function (id) {
            this.load("?module=spamtest&action=assassin&id=" + id);
        },

        designAction: function(params) {
            if (params) {
                if ($('#wa-design-container').length) {
                    waDesignLoad();
                } else {
                    this.load('?module=design', function() {
                        waDesignLoad(params);
                    });
                }
            } else {
                this.load('?module=design', function() {
                    waDesignLoad('');
                });
            }
        },

        //
        // Helper functions
        //

        /* Show "Search results" link above the campaign page, if needed. */
        showLastSearchBreadcrumb: function (campaign_id) {
            if (!($.wa.mailer.showLastSearchBreadcrumb.last_search_ids || {})[campaign_id]) {
                return false;
            }
            var div = $('#content .m-envelope-stripes-2 .m-core-header');
            var a = div.find('a.last-search');
            if (!a.length) {
                a = $('<a href="" class="no-underline">' + $_('Search results') + '</a>');
                div.append('<i class="icon10 larr"></i>').append(a);
            }
            a.attr('href', '#/campaigns/archive/0/!id/' + encodeURIComponent($.wa.mailer.showLastSearchBreadcrumb.last_search_string || '') + '/').show();
            return true;
        },

        // Helper used across the campaign editor pages to save campaign via XHR.
        saveCampaign: function (form, button, callback, no_saved_hint) {
            var were_disabled = button.attr('disabled');
            button.attr('disabled', true).siblings('.process-message').remove();
            var process_message = $('<span class="process-message"><i class="icon16 loading" style="margin:6px 0 0 .5em"></i></span>');
            button.parent().append(process_message);
            $.post(form.attr('action'), form.serialize(), function (r) {
                if (!were_disabled) {
                    button.attr('disabled', false);
                }
                if (r.status == 'ok') {
                    var message_id = r.data;
                    form.find('input[name="id"]').val(message_id);
                    if (no_saved_hint) {
                        process_message.remove();
                    } else {
                        process_message.find('.loading').removeClass('loading').addClass('yes');
                        process_message.append('<span> ' + $_('Saved') + '</span>');
                        process_message.animate({opacity: 0}, 2000, function () {
                            process_message.remove();
                        });
                    }
                } else {
                    process_message.find('.loading').removeClass('loading').addClass('exclamation');
                    console.log('Error saving campaign:', r);
                }

                if (callback) {
                    callback.call(this, r);
                }
            }, 'json');
            return false;
        },

        // Helper to show number of campaign recipients in right sidebar
        showRecipientsInRightSidebar: function (rnum) {
            if (rnum && rnum !== '0' && rnum !== 'null') {
                if (rnum === true) {
                    rnum = '1';
                    $('#right-sidebar-recipients-number').hide();
                } else {
                    $('#right-sidebar-recipients-number').show();
                }
                $('#right-sidebar-recipients-number').html('(' + rnum + ')');
                //$('#right-sidebar-recipients-number').html($_("Total selected:")+' '+rnum).removeClass('red').addClass('green');
            } else {
                rnum = '0';
                $('#right-sidebar-recipients-number').html('(' + rnum + ')').show();
                //$('#right-sidebar-recipients-number').html($_("not specified yet")).removeClass('green').addClass('red').show();
            }
        },

        /** Gracefully reload sidebar. */
        reloadSidebar: function () {
            $.get("?module=backend&action=sidebar", null, function (response) {
                var sb = $("#wa-app > .sidebar");
                sb.css('height', sb.height() + 'px').html(response).css('height', ''); // prevents blinking in some browsers
                $.wa.mailer.highlightSidebar();
                $.wa.mailer.restoreCollapsibleStatusInSidebar();
            });
        },

        /**
         * Reload sidebar when there are campaigns currently sending.
         * Called every minute, see init().
         */
        updateSendingSidebar: function () {
            if ($.wa.mailer.sending_count && $.wa.mailer.getHash().substr(0, 19) != '#/campaigns/archive') {
                $.wa.mailer.reloadSidebar();
            }
            setTimeout($.wa.mailer.updateSendingSidebar, 60000);
        },

        /** Initialize Redactor.js in template editor page and in step 1 page. */
        initWYSIWYG: function (textarea, saveButton, height, csrf, lang) {
            if (!RedactorPlugins) var RedactorPlugins = {};

            textarea.waEditor({
                lang: lang,
                saveButton: saveButton,
                iframe: true,
                minHeight: height,
                plugins: ['fontcolor', 'fontsize', 'fontfamily', 'vars'],
                imageUpload: '?module=files&action=uploadimage&filelink=1',
                uploadFields: {
                    '_csrf': csrf
                }
            });
            textarea.data('ace').setOption("minLines", null);
            textarea.data('ace').setOption("maxLines", null);
            textarea.data('ace').setAutoScrollEditorIntoView(false);
//        textarea.data('ace').resize(true);
            var active_td = null;
            $(textarea.redactor('getIframe')).contents().find('body').on('click', function (e) {
                if (active_td) {
                    active_td.css('outline', '');
                    active_td.css('empty-cells', '');
                }
                active_td = $(e.target).closest('td');
                if (active_td.length) {
                    active_td.css('outline', 'rgba(0, 0, 0, 0.6) dashed 1px');
                }
            });

            this.autoresizeWYSIWYG(height, textarea);

            return textarea;
        },

        resizeWYSIWYG: function (min_height, random, resizer, textarea) {
            if ($('.wa-editor-core-wrapper').length === 0) {
                $(window).off('resize', resizer);
                return;
            }
            var $window = $(window),
                window_h = $window.height(),
                button_container_h = $('#editor-step-1-buttons').outerHeight(),
                editor_offset_offset = $('.wa-editor-core-wrapper').offset(),
                editor_offset_top = editor_offset_offset.top,
                ace_padding_h = $('.ace').outerHeight() - $('.ace').height(),
                editor_toolbar_h = $('.redactor_toolbar').outerHeight(),
                editor_h = window_h - button_container_h - editor_offset_top - 5;

            if ($.wa.mailer.step1_random != random) {
                $(window).off('resize', resizer);
                return;
            }
            if (editor_h > min_height) {
                $('.wa-editor-core-wrapper, .ace').outerHeight(editor_h);
                $('.redactor_box iframe').height(editor_h - editor_toolbar_h - 5);
                $('.ace_editor').height(editor_h - ace_padding_h).css('max-height', editor_h - ace_padding_h);
                if (textarea !== undefined) {
                    textarea.data('ace').resize(true);
                }
            }
        },

        /** Resize the editor depending on window height */
        autoresizeWYSIWYG: function (min_h, textarea) {
            var min_h = min_h + $('#wa-app .m-editor .block.double-padded:last').outerHeight(),
                resizeTimer = null,
                r = Math.random();

            $.wa.mailer.step1_random = r;

            var resizer = function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () {
                    $.wa.mailer.resizeWYSIWYG(min_h, r, resizer, textarea);
                }, 100);
            };

            $(window).on('resize', resizer);

            setTimeout(function () {
                $.wa.mailer.resizeWYSIWYG(min_h, r, null, textarea);
            }, 300);
//
//        var statusbar = workzone.siblings('.statusbar');
//        var r = Math.random();
//        $.wa.mailer.step1_random = r;
//        var resizeEditor = function() {
//            // Unbind self if user left current page
//            var buttons = $('#editor-step-1-buttons');
//            if ($.wa.mailer.step1_random != r) {
//                $(window).unbind('resize', resizeEditor);
//                return;
//            }
//
//            // Calculate and update editor height
//            var new_workzone_height = $(window).height() - workzone.offset().top - (statusbar.outerHeight() || statusbar_height || 0) - buttons.outerHeight() - 5;
//            new_workzone_height = Math.max(300, new_workzone_height);
//            workzone.height(new_workzone_height).find('iframe').height(new_workzone_height);
//            workzone.find('textarea').height(new_workzone_height - 12);
//            workzone.find('.CodeMirror-scroll').height(new_workzone_height);
//        };
//        $(window).resize(resizeEditor);
//        setTimeout(resizeEditor, 1);
        },

        style_html: function (v) {
            if (typeof style_html === 'function') {
                return style_html(v, {
                    max_char: 0
                });
            }
            return v;
        },

        /** Animates campaign report progressbar and countdown. */
        updateCampaignReportProgress: function (campaign_id, percent_complete_precise, campaign_estimated_finish_timestamp, campaign_send_datetime, php_time, campaign_paused) {

            var start_ts = (new Date()).getTime();
            $.wa.mailer.random = start_ts;

            // Delay between page reloads in milliseconds and seconds
            var RELOAD_TIME = 5000;
            var RELOAD_TIME_SEC = RELOAD_TIME / 1000;

            // Set up progressbar
            var previous_time = $.storage.get('mailer/campaign/' + campaign_id + '/time');
            var previous_value = $.storage.get('mailer/campaign/' + campaign_id + '/value');
            var current_time = php_time;
            var current_value = percent_complete_precise;
            if (!previous_time) {
                previous_time = campaign_send_datetime;
                previous_value = 0;
            }
            var set_time, set_value;
            if (current_time - previous_time < RELOAD_TIME_SEC) {
                set_time = current_time;
                set_value = previous_value;
            } else {
                set_time = current_time - RELOAD_TIME_SEC;
                set_value = previous_value + (current_value - previous_value) * (current_time - previous_time - RELOAD_TIME_SEC) / (current_time - previous_time);
            }

            // When campaign is paused, everything is simple.
            if (campaign_paused) {
                $('#progressbar-text').text('' + Math.round(current_value) + '%');
                $('#progressbar-status').css('width', '' + Math.round(current_value) + '%');
                $.storage.set('mailer/campaign/' + campaign_id + '/value', current_value);
                $('#campaign-sending-time-left').parent().hide();
                return;
            }

            // Make sure progress-bar always moves (even if in reality there's no visible progress yet)
            if (current_value <= set_value) {
                if (set_value > previous_value) {
                    // This is safe: user didn't see anything greater than previous_value yet
                    set_value = Math.max(set_value - 1, previous_value);
                } else {
                    // This is a cheat: real progress is less than what we'll show.
                    // Although after about 50% this makes current_value = set_value
                    current_value = set_value + Math.max(0, 0.4 - Math.log(set_value + 2) / 10) * 2;
                }
            }

            // Animate progressbar
            var progressbar_text = $('#progressbar-text').text('' + Math.round(set_value) + '%');
            $('#progressbar-status').width('' + set_value + '%').animate({
                width: '' + current_value + '%'
            }, {
                easing: 'linear',
                duration: RELOAD_TIME * 1.1,
                step: function (value) {
                    var current_value = progressbar_text.text();
                    $.storage.set('mailer/campaign/' + campaign_id + '/value', value);
                    if (current_value && current_value.replace(/[^0-9]/g, '') - 0 < value) {
                        if (!value || value <= 1) {
                            value = '≈1';
                        } else {
                            value = Math.round(value);
                        }
                        progressbar_text.text('' + value + '%');
                    }
                }
            });

            // Helper to pad string with zeros
            var strPad = function (i, l, s) {
                var o = i.toString();
                if (!s) {
                    s = '0';
                }
                while (o.length < l) {
                    o = s + o;
                }
                return o;
            };

            // Helper to format time in [hh:]mm:ss format.
            var formatTime = function (fullseconds) {
                if (fullseconds < 60) {
                    return $_('%ds').replace('%d', fullseconds);
                } else if (fullseconds < 60 * 60) {
                    var seconds = fullseconds % 60;
                    var minutes = Math.floor(fullseconds / 60);
                    return $_('%dm').replace('%d', minutes) + ' ' + $_('%ds').replace('%d', strPad(seconds, 2));
                } else {
                    var seconds = fullseconds % 60;
                    var minutes = Math.floor(fullseconds / 60) % 60;
                    var hours = Math.floor(fullseconds / (60 * 60));
                    return $_('%dh').replace('%d', hours) + ' ' + $_('%dm').replace('%d', strPad(minutes, 2)) + ' ' + $_('%ds').replace('%d', strPad(seconds, 2));
                }
            };

            // Helper to update total campaign duration and time left
            var updateDuration = function () {
                if (campaign_estimated_finish_timestamp) {
                    var current_time_ms = (new Date()).getTime();
                    var seconds_left = Math.max(0, campaign_estimated_finish_timestamp - current_time_ms / 1000);

                    var old = $.wa.mailer.campaign_countdown;
                    if (old && old.campaign_id == campaign_id) {
                        // When old estimate differs from reality too much (more than 20%), then reset the estimate
                        if (old.seconds_left > 10 && ((old.seconds_left * 1.2 < seconds_left) || (seconds_left * 1.2 < old.seconds_left))) {
                            $('#campaign-sending-time-left').html('<i class="icon16 loading"></i>');
                            $.wa.mailer.campaign_countdown = null;
                            return;
                        }
                        // Otherwise, decrement old predition by 1 "fake second" whose length depends on difference
                        // between old and new estimate. This makes the timer run smoothly.
                        else if (old.seconds_left > 0 && current_time_ms - old.current_time_ms < 10000) {
                            // How much real time (in seconds) passed since `old` update
                            var time_diff = Math.min(seconds_left, (current_time_ms - old.current_time_ms) / 1000);
                            // What would the old estimate be if we simply decrement it by real time_diff
                            var bad_estimate = old.seconds_left - time_diff;
                            // New estimate is calculated from previous one (which makes the timer run smoothly)
                            // with a small addition depending on how far our old estimate is from new, updated estimate.
                            seconds_left = bad_estimate + (seconds_left - bad_estimate) * time_diff / old.seconds_left;
                            // Make sure the estimate never goes beyond zero and never increases.
                            seconds_left = Math.max(0, Math.min(seconds_left, old.seconds_left));
                        }
                    }
                    $.wa.mailer.campaign_countdown = {
                        campaign_id: campaign_id,
                        current_time_ms: current_time_ms,
                        seconds_left: seconds_left
                    };
                    $('#campaign-sending-time-left').html(formatTime(Math.floor(seconds_left))).parent().show();
                }
            };
            updateDuration();

            // Updates duration counters and progressbar last update time in localStorage
            var interval = setInterval(function () {
                if ($.wa.mailer.random != start_ts) {
                    window.clearInterval(interval);
                    return;
                }
                var time_passed = (new Date()).getTime() - start_ts;
                var time;
                if (time_passed >= RELOAD_TIME * 1.1) {
                    window.clearInterval(interval);
                    interval = null;
                    time = current_time;
                } else {
                    time = set_time + (current_time - set_time) * time_passed / (RELOAD_TIME * 1.1);
                }
                $.storage.set('mailer/campaign/' + campaign_id + '/time', time);
                updateDuration();
            }, 250);

            // Reload once every several seconds to keep numbers and progressbar fresh
            setTimeout(function () {
                //if ($.wa.mailer.random != start_ts) {
                //    window.clearInterval(reloadInterval);
                //    return;
                //}
                $.get('?module=campaigns&action=reportUpdate&campaign_id=' + campaign_id,
                    function (html) {
                        if ($.wa.mailer.random != start_ts) {
                            //window.clearInterval(reloadInterval);
                            return;
                        }
                        $('#update-container').html(html);
                    });
            }, RELOAD_TIME);
        },

        /**
         * Remove from localStorage any data that no longer needed for archived campaign,
         * Pand remove progressbar from report page.
         */
        cleanupSentCampaign: function (campaign_id) {
            $.storage.del('mailer/campaign/' + campaign_id + '/time');
            $.storage.del('mailer/campaign/' + campaign_id + '/value');

            var progressbar_text = $('#progressbar-text');
            if ($('#report-wrapper .progressbar').length > 0) {
                $('#progressbar-status').animate({
                    width: '100%'
                }, {
                    easing: 'linear',
                    duration: 3000,
                    complete: function () {
                        $.wa.mailer.redispatch();
                    },
                    step: function (value) {
                        if (!value || value <= 0.1) {
                            value = '≈0';
                        } else if (value <= 1) {
                            value = '<1';
                        } else {
                            value = Math.round(value);
                        }
                        progressbar_text.text('' + value + '%');
                    }
                });
            }
        },

        /** Draws pie chart for campaign report in #pie-graph. */
        drawReportPie: function (app_static_url, stats) {
            var r = Raphael($('#pie-graph').empty()[0]);
            var pie = r.piechart(
                120, 120, 100, // center_x, center_y, radius
                stats, {
                    colors: [
                        'green',
                        'blue',
                        'red',
//                    'url('+app_static_url+'img/strokegreen.png)',
//                    'url('+app_static_url+'img/strokered.png)',
//                    'white',
                        '#ccc'
                    ],
                    no_sort: true,
                    minPercent: -1,
                    matchColors: true
                }
            );
            $.wa.mailer.pie = pie; // debugging helper

            pie.hover(function () {
                this.sector.stop().transform('S' + [1.1, 1.1, this.cx, this.cy].join(','));
                var dot = $('#pie-legend .legend-dot')[this.value.order];
                if (!dot) {
                    return;
                }
                var dot = $(dot);
                var large_dot = $('<b class="large legend-dot"></b>').appendTo(dot);
                large_dot.css({
                    'background-color': dot.css('background-color'),
                    'background-image': dot.css('background-image')
                });
            }, function () {
                this.sector.animate({transform: 's1 1 ' + this.cx + ' ' + this.cy}, 500, "bounce");
                $('#pie-legend .legend-dot .legend-dot').remove();
            });
        },

        /** Helper to add submit handler. Disables submit button until response is received. */
        onSubmit: function (form, callback) {
            form.submit(function () {
                var $f = $(this);
                var submits = $f.find("input[type=submit]:enabled").attr('disabled', 'disabled');
                $.post($f.attr('action'), $f.serialize(), function (data, textStatus, jqXHR) {
                    submits.attr('disabled', false);
                    callback.call(this, data, textStatus, jqXHR);
                }, "json");
                return false;
            });
        },

        /** Load HTML content from url and put it into main #content div */
        load: function (url, callback, params) {
            this.showLoading();
            params = params || null;

            var r = Math.random();
            $.wa.mailer.random = r;
            $.get(url, params, function (result) {
                if ($.wa.mailer.random != r) {
                    // too late: user clicked something else.
                    return;
                }
                $("#content").addClass('shadowed').addClass('blank').html(result);
                $.wa.mailer.hideLoading();
                $('html, body').animate({scrollTop: 0}, 200);
                if (callback) {
                    callback.call(this);
                }
            });
        },

        /** Show loading indicator in the header */
        showLoading: function () {
            var h1 = $('h1:visible').first();
            if (h1.size() <= 0) {
                $('#c-core-content .block').first().prepend('<i class="icon16 loading"></i>');
                return;
            }
            if (h1.find('.loading').show().size() > 0) {
                return;
            }
            h1.append('<i class="icon16 loading"></i>');
        },

        /** Hide all loading indicators in h1 headers */
        hideLoading: function () {
            $('h1 .loading').hide();
        },

        /** Add .selected css class to li with <a> whose href attribute matches current hash.
         * If no such <a> found, then the first partial match is highlighted.
         * Hashes are compared after this.cleanHash() applied to them. */
        highlightSidebar: function (sidebar) {
            var currentHash = this.cleanHash(location.hash);
            var partialMatch = false;
            var partialMatchLength = 2;
            var match = false;
            var index = 0;

            if (!sidebar) {
                sidebar = $('#wa-app > .sidebar');
            }

            sidebar.find('li a').each(function (k, v) {
                v = $(v);
                index = k;
                if (!v.attr('href')) {
                    return;
                }
                var h = $.wa.mailer.cleanHash(v.attr('href'));

                // Perfect match?
                if (h == currentHash) {
                    match = v;
                    return false;
                }

                // Partial match? (e.g. for urls that differ in paging only)
                if (h.length > partialMatchLength && currentHash.substr(0, h.length) === h) {
                    partialMatch = v;
                    partialMatchLength = h.length;
                }
            });

            if (!match && partialMatch) {
                match = partialMatch;
            }

            if (match) {
                sidebar.find('.selected').removeClass('selected');
                if (index > 9) {
                    $('#b-all-drafts a').trigger('click');
                }
                // Only highlight items that are outside of dropdown menus
                if (match.parents('ul.dropdown').size() <= 0) {
                    var p = match.parent();
                    while (p.size() > 0 && p[0].tagName.toLowerCase() != 'li') {
                        p = p.parent();
                    }
                    if (p.size() > 0) {
                        p.addClass('selected');
                    }
                }
            }
        },

        /** Collapse sections in sidebar according to status previously set in $.storage */
        restoreCollapsibleStatusInSidebar: function () {
            // collapsibles
            $("#wa-app .collapse-handler").each(function (i, el) {
                $.wa.mailer.collapseSidebarSection(el, 'restore');
            });
        },

        /** Collapse/uncollapse section in sidebar. */
        collapseSidebarSection: function (el, action) {
            if (!action) {
                action = 'coollapse';
            }
            el = $(el);
            if (el.size() <= 0) {
                return;
            }

            var arr = el.find('.darr, .rarr');
            if (arr.size() <= 0) {
                arr = $('<i class="icon16 darr">');
                el.prepend(arr);
            }
            var newStatus;
            var id = el.attr('id');
            var oldStatus = arr.hasClass('darr') ? 'shown' : 'hidden';

            var hide = function () {
                el.nextAll('.collapsible, .collapsible1').first().hide();
                arr.removeClass('darr').addClass('rarr');
                newStatus = 'hidden';
            };
            var show = function () {
                var $collapsible = el.nextAll('.collapsible, .collapsible1').first();
                $collapsible.show();
                $collapsible.find('[data-list="hidden"]').hide();
                $collapsible.find('[data-list="shown"]').show();
                arr.removeClass('rarr').addClass('darr');
                newStatus = 'shown';
            };

            switch (action) {
                case 'toggle':
                    if (oldStatus == 'shown') {
                        hide();
                    } else {
                        show();
                    }
                    break;
                case 'restore':
                    if (id) {
                        var status = $.storage.get('mailer/collapsible/' + id);
                        if (status == 'hidden') {
                            hide();
                        } else {
                            show();
                        }
                    }
                    break;
                case 'uncollapse':
                    show();
                    break;
                //case 'collapse':
                default:
                    hide();
                    break;
            }

            // save status in persistent storage
            if (id && newStatus) {
                $.storage.set('mailer/collapsible/' + id, newStatus);
            }
        },

        /**
         * Helper to make elements (usually table td's) clickable by ctearing <a> elements that wrap their content.
         * @param elements jQuery collection
         * @param getLink callback(el) to get href attribute
         */
        makeClickable: function (elements, getLink) {
            elements.each(function () {
                var el = $(this);
                var a = $('<a href="' + getLink(el) + '" style="color:inherit !important"></a>').html(el.html());
                el.empty().append(a);
            });
        },

        /** Current hash. No URI decoding is performed. */
        getHash: function () {
            return this.cleanHash();
        },

        /** Make sure hash has a # in the begining and exactly one / at the end.
         * For empty hashes (including #, #/, #// etc.) return an empty string.
         * Otherwise, return the cleaned hash.
         * When hash is not specified, current hash is used. No URI decoding is performed. */
        cleanHash: function (hash) {
            if (typeof hash == 'undefined') {
                // cross-browser way to get current hash as is, with no URI decoding
                hash = window.location.toString().split('#')[1] || '';
            }

            if (!hash) {
                return '';
            } else if (!hash.length) {
                hash = '' + hash;
            }
            while (hash.length > 0 && hash[hash.length - 1] === '/') {
                hash = hash.substr(0, hash.length - 1);
            }
            hash += '/';

            if (hash[0] != '#') {
                if (hash[0] != '/') {
                    hash = '/' + hash;
                }
                hash = '#' + hash;
            } else if (hash[1] && hash[1] != '/') {
                hash = '#/' + hash.substr(1);
            }

            if (hash == '#/') {
                return '';
            }

            return hash;
        },


        /** Helper to initialize iButtons */
        iButton: function ($checkboxes, options) {
            options = options || {};
            return $checkboxes.each(function () {
                var cb = $(this);
                var id = cb.attr('id');
                if (!id) {
                    do {
                        id = 'cb' + Date.now() + '-' + (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
                    } while (document.getElementById(id));
                    cb.attr('id', id);
                }
                if (!options.inside_labels) {
                    $($.parseHTML(
                        '<ul class="menu-h ibutton-wrapper">' +
                        '<li><label class="gray" for="' + id + '">' + (options.labelOff || '') + '</label></li>' +
                        '<li class="ibutton-li"></li>' +
                        '<li><label for="' + id + '">' + (options.labelOn || '') + '</label></li>' +
                        '</ul>'
                    )).insertAfter(cb).find('.ibutton-li').append(cb);
                }
            }).iButton($.extend({
                className: 'mini'
            }, options, options.inside_labels ? {} : {
                labelOn: "",
                labelOff: ""
            }));
        },

        /** Helper to insert text into textarea */
        insertAtCursor: function (myField, myValue) {
            // IE support
            if (document.selection) {
                myField.focus();
                var sel = document.selection.createRange();
                sel.text = myValue;
            }
            // MOZILLA and others
            else if (myField.selectionStart || myField.selectionStart == '0') {
                var startPos = myField.selectionStart;
                var endPos = myField.selectionEnd;
                myField.value = myField.value.substring(0, startPos)
                + myValue
                + myField.value.substring(endPos, myField.value.length);
                myField.selectionStart = startPos + myValue.length;
                myField.selectionEnd = startPos + myValue.length;
            } else {
                myField.value += myValue;
            }
        },

        /** Shows a confirmation dialog when user tries to navigate away from current page or current hash. */
        confirmLeave: function (is_relevant, warning_message, confirm_question) {
            var h, h2, $window = $(window);

            $window.on('beforeunload', h = function (e) {
                if (is_relevant()) {
                    return warning_message;
                }
            });

            $window.on('wa_before_dispatched', h2 = function (e) {
                if (!is_relevant()) {
                    $window.off('unload', h).off('wa_before_dispatched', h2);
                    return;
                }
                if (!confirm(warning_message + " " + confirm_question)) {
                    e.preventDefault();
                }
            });
        },

        plotGraph: function ($el, data, color, label, dates_range, zeroline, x_tick_interval, xticks) {
            var getMaxY = function (arr) {
                var m = Math.max.apply(null, $.map(arr, function (el, i) {
                    return parseInt(el[1]);
                }));
                return Math.round(m + 0.5);
            };
            var getYTickInterval = function (max, delimiter) {
                delimiter = delimiter ? delimiter : 10;
                if (max < delimiter) {
                    return 1;
                } else {
                    return Math.floor(max / delimiter);
                }
            };
//        var extend_options = function(colors, labels) {
////            var y_max = getMax(data);
////            var y_tick_interval = getYTickInterval(y_max);
////            opt.axes.yaxis.max = y_max;
////            opt.axes.yaxis.tickInterval = y_tick_interval;
//
////            opt.seriesDefaults.renderer = $.jqplot.LineRenderer;
////            opt.seriesDefaults.rendererOptions = {};
//            graph_options.series = [];
//            $.each(colors, function(i, color) {
//                graph_options.series[i] = {
//                    color: colors[i],
//                    label: ' '
//                };
//            });
//        };
            var clear_graph = function () {
                if ($el.data('jqplot')) {
//                $('#shown-hours').hide().find('span').text('');
                    $el.data('jqplot').destroy();
                }
            };
            var replot_graph = function () {
                if ($el.data('jqplot')) {
                    $el.data('jqplot').replot();
                }
            };
            var bind_onresize = function () {
                $(window).off('resize.mailer.plotGraph').on('resize.mailer.plotGraph', replot_graph);
            };

            $.jqplot.config.enablePlugins = true;
            var graph_options = {
                height: 200,
                canvasOverlay: {
                    show: true,
                    objects: [{
                        verticalLine: {
                            name: 'Start',
                            x: zeroline * 1000,
                            lineWidth: 3,
                            yOffset: 0,
                            color: 'rgb(89, 198, 154)',
                            shadow: false,
                            showTooltip: true,
                            tooltipFormatString: "Start",
                            tooltipLocation: 'se'
                        }
                    }]
                },
                gridPadding: {
                    right: 40,
                    left: 40
                },
                seriesDefaults: {
                    pointLabels: {
                        show: false
                    },
                    markerOptions: {
                        show: true,
                        size: 5
                    }
                },
                series: [
                    {
                        color: color,
                        label: " "
                    }
                ],
                grid: {
                    borderWidth: 1,
                    shadow: false,
                    background: '#ffffff',
                    gridLineColor: '#eeeeee'
                },
                axes: {
                    xaxis: {
                        min: dates_range[0] * 1000,
                        max: dates_range[1] * 1000,
                        renderer: $.jqplot.DateAxisRenderer,
//                  tickInterval: x_tick_interval,
                        tickOptions: {
                            formatString: '%b %e%n%R'
                        },
//                    ticks: xticks,
                        numberTicks: 10,
                        pad: 0,
                        showTickMarks: false
                    },
                    yaxis: {
                        min: 0,
                        max: 0,
                        pad: 0,
                        tickInterval: 0,
                        showTickMarks: false
                    }
                },
                highlighter: {
                    show: false
                },
                cursor: {
                    zoom: true,
                    show: true,
                    showTooltip: true,
                    looseZoom: true,
                    followMouse: true,
                    showVerticalLine: true,
                    showTooltipDataPosition: true,
                    useAxesFormatters: true,
                    tooltipFormatString: '<div style="font-size: 1.3em; background-color: #ffffff;padding: 5px;"><div>%s%s<br/><span style="color:' + color + '">' + label + ': <b style="color:#000">%s</b></span></div>',
                    constrainZoomTo: 'x'
                }
            };
            graph_options.axes.yaxis.max = getMaxY(data);
            graph_options.axes.yaxis.tickInterval = getYTickInterval(graph_options.axes.yaxis.max);
//        graph_options.axes.xaxis.tickInterval = x_tick === 1 ? x_tick+' minute' : x_tick+' minutes';

            clear_graph();
//        extend_options([color], [label]);
//        console.log([data]);
//        console.log(graph_options);
            $el.jqplot([data], graph_options);

            bind_onresize();
//        return graph;
        },

        getAndPlotData: function (campaign_id, graph_id, start, end, quantum, online) {
            $.get('?module=campaigns&action=reportGraphData&campaign_id=' + campaign_id,
                {
                    intervalstart: start,
                    intervalend: end,
                    status: graph_id,
                    quantum: quantum
                },
                function (r) {
                    if (r.status === 'ok') {
                        var graph = r.data,
                            $current_link_pie_legend = $('#report-wrapper').find('.pie-graph-legend .show-recipients-link.disabled:first'),
                            graph_color = $current_link_pie_legend.data('graph-color'),
                            graph_label = $current_link_pie_legend.data('graph-label'),
                            graph_wrapper = $('#graph-wrapper-all');

                        $('#quantum').val(graph.quantum);

                        if (online) {
                            $('.m-graph-times').hide();
                            $('.m-graph-title').hide();
                        } else {
                            $('.m-graph-times').show();
                            $('.m-graph-title').show();

                            // select datetime interval
                            $('.m-graph-times').find('[data-time]').each(function (i, el) {
                                var time = $(el).data('time');

                                if (time <= graph.days * 24) {
                                    $(el).removeClass('hidden');
                                }
                            });

                            // select minute/hour quantum
                            var $quantum_link = $('[data-quantum="' + graph.quantum + '"]');
                            $quantum_link.addClass('active')
                                .insertBefore($quantum_link.siblings('a'));
                            $('[data-quantum-separator]').insertAfter($quantum_link);
                        }

                        if (graph.data.length === 0) {
                            $('.m-graph').hide();
                        } else {
                            $('.m-graph').show();
                            $.wa.mailer.plotGraph(
                                graph_wrapper,
                                graph.data,
                                graph_color,
                                graph_label,
                                [graph.mindate.toString(), graph.maxdate.toString()],
                                graph.zeroline,
                                graph.tickinterval,
                                graph.ticks
                            );
                        }
                    } else {

                    }
                }, 'json')
                .always(function () {
                    $('.m-graph').find('.loading:first').hide();
                });
        },

        loadNext50Recipients: function (campaign_id, params, callback_before, callback_after) {
            var recipient_lists_wrapper = $('#recipient-lists-wrapper'),
                report_wrapper = $('#report-wrapper');

            recipient_lists_wrapper.append('<i class="icon16 loading" style="margin:0 0 0 27px;"></i>');
            $.get('?module=campaigns&action=recipientsReport&campaign_id=' + campaign_id, params, function (r) {
                var active_link_rel = [
                    report_wrapper.find('.show-recipients-link.disabled:first').attr('rel'),
                    recipient_lists_wrapper.find('.show-recipients-link.disabled:first').attr('rel')
                ];

                recipient_lists_wrapper.children('.loading').remove();
                if (typeof callback_before == 'function') {
                    callback_before();
                }
                var existing_table = recipient_lists_wrapper.find('table.recipients-report:first');
                var container = $('<div></div>').appendTo(recipient_lists_wrapper);

                container.html(r);

                // Retain active link selection in chart legend

                report_wrapper
                    .find('.show-recipients-link')
                    .filter('[rel="' + active_link_rel[0] + '"]')
                    .addClass('disabled');
                if (active_link_rel[1]) {
                    recipient_lists_wrapper
                        .find('.show-recipients-link')
                        .removeClass('disabled')
                        .filter('[rel="' + active_link_rel[1] + '"]')
                        .addClass('disabled');
                }

                container.find('td:last-child').each(function () {
                    var self = $(this);
                    if (self.children('div.hidden').length > 0) {
                        self.children('span').addClass('show-full-error-text');
                    }
                });
                if (existing_table.length > 0) {
                    existing_table.find('tr:last').after(container.find('tr'));
                    container.find('table').remove();
                }
            });
        },

        setTitle: function (title) {
            var $h1 = $('#wa-app .content h1:first');
            if ((typeof title === 'undefined' || !title.length) && $h1.length > 0) {
                var $h1_input = $h1.find('input');
                if ($h1_input.length > 0) {
                    title = $h1_input.val().trim();
                }
                else {
                    title = $h1.contents().filter(function () {
                        return this.nodeType == 3 && this.nodeValue.trim().length > 0;
                    })[0].nodeValue.trim()
                }
            }

            if (title) {
                $('title').html(title + " &mdash; Webasyst");
            }
        }

    }; // end of $.wa.mailer

})(jQuery);
