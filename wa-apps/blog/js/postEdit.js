(function($) {
    $.wa_blog.editor = {

        options : {
            blogs: {},
            content_id : 'post_text',
            current_blog_id:null,
            //dateFormat: 'yy-mm-dd',
            dateMonthCount: 2,
            dateShowWeek: false,
            cut_link_label_default: '',
            version: '1.0'
        },

        transliterated: false,

        blog_statuses: {
            'private': 'private',
            'public': 'public'
        },

        init : function(options) {

            var self = this;

            self.options = $.extend(self.options, options);

            self.postUrlWidget = setupPostUrlWidget();

            self.inlineSaveController = setupInlineSave({

                beforeSave: function() {
                    if (!validateDatetime()) {
                        return false;
                    }
                    $.wa_blog.editor.onSubmit();
                },

                afterSave: function(data) {
                    $('#b-post-save-button').removeClass('yellow').addClass('green');
                    $('#postpublish-edit').removeClass('yellow').addClass('green');
                    self.postUrlWidget.resetEditReady();
                    self.postUrlWidget.updateSlugs(data.url, data.preview_hash||false);
                    resetEditDatetimeReady.call($('#inline-edit-datetime').get(0), data.formatted_datetime);
                    if (self.inlineSaveController.getAction() == 'draft') {
                        $('#post-url-field .small').removeClass('small').addClass('hint');
                    }

                    var dialog = $('#b-post-edit-custom-params-dialog');
                    if (!dialog.is(':hidden')) {
                        var meta_title = dialog.find(':input[name=meta_title]').val();
                        var meta_keywords = dialog.find(':input[name=meta_keywords]').val();
                        var meta_description = dialog.find(':input[name=meta_description]').val();
                        var params = dialog.find(':input[name=params]').val();
                        if (meta_title) {
                            $('#b-post-edit-meta-title').show().find('.val').text(meta_title);
                        } else {
                            $('#b-post-edit-meta-title').hide();
                        }
                        if (meta_keywords) {
                            $('#b-post-edit-meta-keywords').show().find('.val').text(meta_keywords);
                        } else {
                            $('#b-post-edit-meta-keywords').hide()
                        }
                        if (meta_description) {
                            $('#b-post-edit-meta-description').show().find('.val').text(meta_description);
                        } else {
                            $('#b-post-edit-meta-description').hide();
                        }
                        if (params) {
                            $('#b-post-edit-custom-params').show().html(params.trim().split("\n").join("<br>") + "<br>");
                        } else {
                            $('#b-post-edit-custom-params').hide();
                        }
                        if (meta_title || meta_keywords || meta_description || params) {
                            $('#b-post-edit-no-meta').hide();
                        } else {
                            $('#b-post-edit-no-meta').show();
                        }
                        dialog.trigger('close');
                    }

                }

            });

            setupSwitcherWidget();
            initDatepickers(self.options);
            initDialogs();

            var editor = null;
            try {
                editor = $.storage.get('blog/editor') || 'redactor';
            } catch(e) {
                this.log('Exception: '+e.message + '\nline: '+e.fileName+':'+e.lineNumber);
            }
            if (!editor || !this.editors[editor]) {
                for(editor in this.editors) {
                    break;
                }
            }
            if (!this.selectEditor(editor, true)) {
                for(editor in this.editors) {
                    if (this.selectEditor(editor, true)) {
                        break;
                    }
                }
            }

            // Place cursor into title field for new posts
            if (!$('#post-form').find('input[name=post_id]').val()) {
                $('#post-title').focus();
            }

            $.wa.dropdownsCloseEnable();
            $('.change-blog').click($.wa_blog.editor.changeBlogHandler);
            $('#postpublish-dialog input[name="publish_blog_id"]').change($.wa_blog.editor.changePublishBlogHandler);


            $('#post-title').keyup(function() {
                var input = $(this),
                    msg = input.next('.maxlength'),
                    maxlength = parseInt(input.attr('maxlength'));

                if (maxlength && input.val().length >= maxlength && !msg.length) {
                    input.after('<em class="hint maxlength">'
                            + $_('input_maxlength').replace(/%d/, maxlength)
                            + '</em>');
                } else if ((!maxlength || input.val().length < maxlength) && msg.length) {
                    msg.remove();
                }
            });

            // link for start inline-editing of date
            $('#inline-edit-datetime').click(function() {
                setEditDatetimeReady.call(this);
            });

            $('.b-post-editor-toggle li a').click(this.editorTooggle);

            $(document).keydown(function(e) {
                  // ctrl + s
                if (e.ctrlKey && e.keyCode == 83) {
                    self.onSaveHotkey(e);
                }
            });

            $('.time').focus(function() {
                hideDatetimeError($(this).parent().find('.datepicker'));
            });

            $('#post-form').submit(function() {
                return false;
            });

            $('#b-post-edit-custom-params-link').click(function() {
                $('#b-post-edit-custom-params-dialog').waDialog({
                    onSubmit: function() {
                        return false;
                    }
                });
                return false;
            });

            // Stick button bar to the bottom of the window
            var $window = $(window), $buttons_bar = $('#buttons-bar');
            $buttons_bar.sticky && $('#buttons-bar').sticky({
                fixed_class: 'b-fixed-button-bar',
                isStaticVisible: function (e, o) {
                    return  $window.scrollTop() + $window.height() >= e.element.offset().top + e.element.outerHeight();
                }
            });

            initRealtimePreviewMode();

            // ====== Functions declarations section ========

            function initDialogs()
            {

                $('.dialog-edit').each(function() {
                    /*$(this).parents('.block:first').click(function(e) {
                        if ($(e.target).hasClass('dialog-edit')) {
                            return linkClickHandler.call(e.target);
                        }
                    });*/
                    $(this).click(function(e) {
                            e.preventDefault();
                            return linkClickHandler.call(e.target);
                    });
                });

                function linkClickHandler()
                {
                    var id = $(this).attr('id').replace(/-.*$/,'');

                    $.wa_blog.editor.currentDialog =
                        $("#" + id +"-dialog").waDialog({
                            disableButtonsOnSubmit: true,
                            onLoad: function () {
                                $(this).find("input,select").removeAttr('disabled');
                                $('.user-date-format').text(getDateFormat());
                            },
                            onSubmit: function () {
                                return false;
                            },
                            onCancel: function(dialog) {
                                $(this).find("input:text,select").each(function(id){
                                    $(this).value = $(this).defaultValue;
                                    $(this).attr('disabled','disabled');
                                });
                            }
                        });

                    return false;
                }
            }

            function setEditDatetimeReady()
            {
                $(this).hide();
                $(this).parent().find('.datetime').show();
                $('#current-time').hide();
                $('.user-date-format').text(getDateFormat());
            }

            function resetEditDatetimeReady(datetime)
            {
                $(this).show();
                $(this).parent().find('.datetime').hide();
                if (datetime) {
                    $('#current-time').text(datetime);
                }
                $('#current-time').show();
            }

            /**
             * Find nearest to input element with class .hint
             * @param input
             * @returns $ wrapped found element
             */
            function findHintToDatepicker(input)
            {
                var hint = input.siblings('.hint:first');
                if (hint.length <= 0) {
                    hint = input.parent().siblings('.hint:first');
                }
                return hint;
            }

            /**
             * Initial validating date before send request to server
             * @returns {Boolean}
             */
            function validateDatetime()
            {
                var input = $('.datepicker:not(:disabled)');

                var success = true;

                if (input.length > 0) {

                    var timeInputes = input.parent('.datetime').find('.time');

                    if (timeInputes.length > 0) {
                        if (timeInputes.get(0) && !validateHour(timeInputes.eq(0).val())) {
                            success = false;
                        }
                        if (timeInputes.get(1) && !validateMinute(timeInputes.eq(1).val())) {
                            success = false;
                        }
                    }

                    if (!success) {
                        showDatetimeError(input);
                    }

                }

                return success;
            }

            function hideDatetimeError(datepickerInput)
            {
                datepickerInput.parent().find('input[type=text]').removeClass('error');
                findHintToDatepicker(datepickerInput).removeClass('errormsg');
            }

            function showDatetimeError(datepickerInput)
            {
                datepickerInput.parent().find('input[type=text]').addClass('error');
                findHintToDatepicker(datepickerInput).addClass('errormsg');
            }

            /**
             * Validate hour
             * @param hour
             * @returns {Boolean}
             */
            function validateHour(hour)
            {
                return hour >= 0 && hour <= 24;
            }

            /**
             * Validate Minute
             * @param minute
             * @returns {Boolean}
             */
            function validateMinute(minute)
            {
                return minute >= 0 && minute <= 60;
            }

            /**
             * Init datepickers of form and dialogs
             *
             * @param options
             */
            function initDatepickers(options)
            {
                var datepicker_options = {
                    changeMonth : true,
                    changeYear : true,
                    shortYearCutoff: 2,
                    showOtherMonths: options.dateMonthCount < 2,
                    selectOtherMonths: options.dateMonthCount < 2,
                    stepMonths: options.dateMonthCount,
                    numberOfMonths: options.dateMonthCount,
                    showWeek: options.dateShowWeek,
                    gotoCurrent: true,
                    constrainInput: false,

                    beforeShow : function() {
                        // hack! It's needed after-show-callback for changing z-index, but it doesn't exist
                        setTimeout(function() {
                            // make min z-index 10
                            var zIndex = $('#ui-datepicker-div').css('z-index');
                            if (zIndex < 10) {
                                $('#ui-datepicker-div').css('z-index', 10);
                            }
                        }, 0);
                    }
                };

                $('.datepicker').datepicker(datepicker_options)
                    .filter('input[name^=schedule_datetime]').datepicker('option', 'minDate', new Date());

                // hide current datepicker by hardcoding style, because jquery.ui.datepicker
                // has bag and doesn't hide calendar by oneself
                $('#ui-datepicker-div').hide();

                $('.datepicker').focus(function() {
                    hideDatetimeError($(this));
                });

            }

            /**
             * Get format in what inputed dates must be
             *
             * @returns {String}
             */
            function getDateFormat()
            {
                return $.datepicker._defaults.dateFormat.toUpperCase().replace('YY', 'YYYY');
            }

            // ===== Widgets and classes declaration section ====

            function setupSwitcherWidget()
            {
                var switcher = $('#allow-comment-switcher');

                handler.call(switcher.get(0));

                switcher.iButton({
                    labelOn : '',
                    labelOff : '',
                    className: 'mini'
                }).change(handler);

                function handler()
                {
                    var onLabelSelector = '#' + this.id + '-on-label',
                    offLabelSelector = '#' + this.id + '-off-label';

                    if (!this.checked) {
                        $(onLabelSelector).addClass('b-unselected');
                        $(offLabelSelector).removeClass('b-unselected');
                    } else {
                        $(onLabelSelector).removeClass('b-unselected');
                        $(offLabelSelector).addClass('b-unselected');
                    }
                }
            }

            /**
             * Setup widget for display/typing url of post
             *
             * Widget dynamicly offers version of url by transliterate title of post
             */
            function setupPostUrlWidget() {

                var cachedSlug = null;
                var cache = {};
                var changeBlog = function() {};
                var blog_settings = {};

                init();

                /**
                 *
                 * Get descriptor from server by using ajax
                 *
                 * @param blogId
                 * @param postId
                 * @param postTitle
                 * @returns object descriptor
                 */
                function getDescriptor(blogId, postId, postTitle, fn)
                {
                    var descriptor = null,
                        request = { 'post_title': postTitle, 'post_id': postId, 'blog_id': blogId },
                        cache_key = [blogId, postId].join('&');

                    if (cache[cache_key] && (cachedSlug || !postTitle)) {
                        fn(cache[cache_key]);
                    } else {
                        if (cachedSlug) {
                            request['slug'] = cachedSlug;
                        }
                        $.ajax({
                            url : '?module=post&action=getPostUrl',
                            data: request,
                            dataType: 'json',
                            type: 'post',
                            async: false,
                            success: function(response) {
                                descriptor = response['data'];
                                cache[cache_key] = descriptor;
                                $.wa_blog.editor.transliterated = true;
                                fn(descriptor);
                            }
                        });
                    }
                }

                function updateHtml(descriptor)
                {
                    var wholeUrl = descriptor.link + descriptor.slug + '/';

                    $('#url-link').text(wholeUrl);
                    $('#url-link').attr('href', descriptor.preview_hash
                            ? wholeUrl + '?preview=' + descriptor.preview_hash
                            : wholeUrl
                    );
                    $('#pure-url').text(descriptor.link);
                    $('#preview-url').data('preview-url', descriptor.preview_link);

                    if (descriptor.slug && !cachedSlug) {
                        $('#post-url').val(descriptor.slug);
                        cachedSlug = descriptor.slug;
                    } else {
                        $('#post-url').val(cachedSlug);
                    }

                    var className = descriptor.is_adding ? 'small'
                                        : descriptor.is_published ? 'small' : 'hint';

                    var previewText = $('#post-url-field span:first').contents().filter(function() { return this.nodeType == 3; }).get(0).nodeValue;

                    if (descriptor.other_links && descriptor.other_links instanceof Array) {
                        var data = [];
                        for (k in descriptor.other_links) {
                            data.push({
                                previewText: previewText,
                                className: className,
                                slug: cachedSlug,
                                link: descriptor.other_links[k],
                                preview_link: descriptor.other_preview_links[k],
                                href : descriptor.preview_hash
                                            ? descriptor.other_links[k] + cachedSlug + '/?preview=' + descriptor.preview_hash
                                            : descriptor.other_links[k] + cachedSlug + '/'
                            });
                        }
                        var tmpl = descriptor.is_adding
                            ?   '<span class="${className}">${previewText}${link}' +
                                    '<span class="slug">{{if slug}}${slug}/{{/if}}</span>' +
                                '</span><br>'
                            : '<span class="${className}">${previewText}<a target="_blank" href="${href}" data-preview-url="${preview_link}">${link}' +
                                    '<span class="slug">{{if slug}}${slug}/{{/if}}</span></a>' +
                                '</span><br>';

                        var icon = $('#post-url-field').children(':first');

                        if (icon.is('.icon10')) {
                            tmpl = '<i class="' + icon.attr('class') + '"></i> ' + tmpl;
                        }

                        $('#other-urls').html($.tmpl(tmpl, data));
                    }
                }

                /**
                 *
                 * Show widget. View of widget depends on descriptor
                 *
                 * @param object descriptor
                 */
                function show(descriptor)
                {
                    $('#post-url-field-no-settlements').hide('fast');
                    descriptor && updateHtml(descriptor);
                    $('#post-url-field').show('fast').trigger($.Event('change', { status: 'visible' }));
                }

                /**
                 * Hide widget
                 */
                function hide(status)
                {
                    // 'empty' = no frontend routing for selected blog
                    // 'hidden' = frontend routing exists, but no title present, and the whole control is hidden
                    // 'hidden_empty' = no frontend routing and whole control is hidden
                    status = status || 'hidden';
                    if (status == 'empty') {
                        $('#post-url-field-no-settlements').show('fast');
                    } else {
                        $('#post-url-field-no-settlements').hide('fast');
                    }
                    cachedSlug = null;
                    var $post_url = $('#post-url').val('');

                    $('#post-url-field').hide('fast').trigger($.Event('change', { status: status }));
                }

                function updateSlugs(slug, preview_hash)
                {
                    if (slug) {
                        cachedSlug = slug;
                    }
                    $('.slug').each(function() {
                        if (cachedSlug) {
                            var a = $(this).text(cachedSlug + '/').parents('a:first');
                            a.attr('href', a.text()+(preview_hash?'?preview='+preview_hash:''));
                        } else {
                            $(this).text('');
                        }
                    });
                }

                /**
                 * Initializing widget
                 */
                function init()
                {
                    var postUrlHandler = changeBlog = function() {
                        // Blog app has no frontend at all?
                        if (!$('#post-url-field').length) {
                            hide('empty');
                            return;
                        }

                        var title_value = $('#post-title').val();
                        var blogId = $('input[name=blog_id]').val();
                        if (blog_settings[blogId] && blog_settings[blogId].no_frontend) {
                            if (title_value) {
                                hide('empty');
                            } else {
                                hide('hidden_empty');
                            }
                            return;
                        }

                        if (!title_value) {
                            // Hide control immidiately to be responsive.
                            // Still need to query afterwards to update HTML and live preview.
                            hide('hidden');
                        }

                        var postId = $('input[name=post_id]').val();
                        getDescriptor(blogId, postId, title_value, function(descriptor) {
                            if (!descriptor) {
                                return;
                            }
                            if (!blog_settings[blogId]) {
                                blog_settings[blogId] = {
                                    id: blogId,
                                    no_frontend: descriptor.is_private_blog || !descriptor.link
                                };
                            }

                            if (blog_settings[blogId].no_frontend) {
                                if (title_value) {
                                    hide('empty');
                                } else {
                                    hide('hidden_empty');
                                }
                            } else if (title_value) {
                                show(descriptor);
                            } else {
                                updateHtml(descriptor);
                                hide('hidden');
                            }
                        });
                    };

                    $('#post-title').blur(postUrlHandler);
                    var postId = $('#post-form').find('input[name=post_id]').val();
                    if (postId) {
                        postUrlHandler();
                    }

                    $('#url-edit-link').click(setEditReady);

                    $('#post-url').keyup(function(e) {
                        if (e.keyCode != 9) {        // ignore when blur from other input to this input
                            if(this.value != cachedSlug) {
                                updateSlugs(this.value);
                            }
                        }
                    });
                }

                function setEditReady()
                {
                    $('#url-editable').hide();
                    $('#url-edit').show();
                    $('#post-url').focus();
                }

                function resetEditReady()
                {
                    $('#url-editable').show();
                    $('#url-edit').hide();
                }

                return {
                    setEditReady: setEditReady,
                    resetEditReady: resetEditReady,
                    updateSlugs: updateSlugs,
                    changeBlog:changeBlog
                };

            }    // setupPostUrlWidget

            /**
             *
             */
            function setupInlineSave(options)
            {
                options = options || {};

                var action = '';
                var inline = false;

                var beforeSave = options.beforeSave || function() {};
                var afterSave = options.afterSave || function() {};

                init();

                function init()
                {
                    $('input[type=submit], input[type=button], a.js-submit').click(function() {
                        if($(this).hasClass('dialog-edit')) {
                            return false;
                        }
                        if($(this).hasClass('js-submit')) {
                            var question = $(this).attr('title')||$(this).text()||'Are you sure?';
                            if(!confirm(question)) {
                                return false;
                            }
                        }
                        if ($('#post-id').val() && (this.id == 'b-post-save-button' ||
                                this.id == 'b-post-save-draft-button' ||
                                this.id == 'b-post-save-custom-params'))
                        {
                            inline = true;
                        }
                        action = this.name;
                        if (action == 'deadline' || action == 'schedule') {
                            $('#' + this.name + '-dialog').find('input[name^=datetime]').attr('disabled', false);
                        }
                        save();
                        return false;
                    });
                    $('#post-url').focus(function() {
                        //hideErrorMsg($(this));
                    });
                }

                function showErrorMsg(input, msg)
                {
                    var selector = '#message-' + input.attr('id');
                    input.addClass('error');
                    $(selector).addClass('errormsg').text(msg);
                }

                function hideErrorMsg(input)
                {
                    var selector = '#message-' + input.attr('id');
                    input.removeClass('error');
                    $(selector).removeClass('errormsg').text('');
                }

                function showErrors(errors)
                {
                    if (errors.url) {
                        $('#post-url-field').show();
                        showErrorMsg($('#post-url'), errors.url);
                    }
                    if (errors.datetime) {
                        var input = $('.datepicker:not(:disabled)');
                        if (input.length) {
                            showDatetimeError(input);
                        }
                    }
                }

                function hideErrors()
                {
                    hideErrorMsg($('#post-url'));
                    var input = $('.datepicker:not(:disabled)');
                    if (input.length) {
                        input.datepicker('hide');
                        hideDatetimeError(input);
                    }
                }

                function save()
                {
                    if (beforeSave() !== false) {
                        //hideErrors();
                        submit(afterSave);
                    }
                }

                function onFail(errors)
                {
                    if (!errors.datetime) {
                        if (action == 'deadline') {
                            var date = $('#deadline-dialog .datepicker').val();
                            if (date) {
                                $('#publication-deadline-changable-part').html($.tmpl('publication-deadline-setted', {
                                    date: date
                                }));
                            } else {
                                $('#publication-deadline-changable-part').html($.tmpl('publication-deadline-setted'));
                            }
                            $('#b-post-save-draft-button').attr('name', 'deadline');
                        }
                    }
                }

                function submit(fn)
                {
                    var text = $.wa_blog.editor.wysiwygToHtml($('#post_text', '#post-form').val());
                    $('#post_text', '#post-form').val(text);

                    var data = $('#post-form').serialize() + '&' + action + '=1';

                    data += inline ? '&inline=1' : '';
                    data += !$.wa_blog.editor.transliterated ? '&transliterate=1' : '';

                    fn = fn || function() {};

                    updateStatusIcon('loading');

                    $.ajax({
                        url : '?module=post&action=save',
                        data: data,
                        dataType: 'json',
                        type: 'post',
                        success: function(response) {
                            if (response.status == 'fail') {
                                if (!response.errors.datetime) {
                                    $.wa_blog.editor.closeCurrentDialog();
                                }
                                showErrors(response.errors);
                                onFail(response.errors);
                            } else if (response.data.redirect) {
                                location.href = response.data.redirect;
                            } else {
                                fn(response.data);
                                if (response.data.id) {
                                    $('#post-id').val(response.data.id);
                                }
                                updateStatusIcon('saved');
                            }

                            inline = false;
                            updateStatusIcon('');
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            //TODO
                        }
                    });
                }

                function updateStatusIcon(status, fn)
                {
                    if (!status) {
                        $('#form-status').fadeOut(fn && typeof(fn) == 'function' ? fn : function() {});
                    } else {
                        $('#form-status span').hide();
                        $('#form-status #' + status + '-status').show();
                        $('#form-status').show();
                    }
                }

                function setAction(_action)
                {
                    action = _action;
                }

                function getAction() {
                    return action;
                }

                return {
                    save: save,
                    setAction: setAction,
                    getAction: getAction
                };
            }

            function initRealtimePreviewMode() { "use strict";
                var $post_form = $('#post-form');
                var $iframe = $('#realtime-preview-iframe');
                var $preview_sidebar = $iframe.closest('.sidebar');
                var animation_in_progress = false;
                var $fake_textarea = $('<textarea>').hide().appendTo($preview_sidebar);
                var $post_title = $('#post-title');
                var $not_available_message = $('#not-available-message');
                var $wa_header = $('#wa-header');
                var $fixed_button_bar = $('.b-fixed-button-bar');
                var $album_selector = $('#blog-photo_bridge-editor [name="album_id"]');
                var $hidden_realtime_on = $preview_sidebar.find('[name="realtime_on"]');

                var iframe_visible = false;
                var iframe_loaded = false;
                $iframe.one('load', function() {
                    iframe_loaded = true;
                });
                $.pm.bind('updater_loaded', function(data) {
                    iframe_loaded = true;
                });

                // Resize columns when user drags the resize handler
                $.widget('ui.blog_draghandler', $.ui.mouse, {
                    _init: function(){
                        this._mouseInit(); // start up the mouse handling
                        this.$content = $post_form.find('.content');
                    },
                    _mouseStart: function(e){
                        this.xStart = e.pageX;
                        this.sidebar_width = $preview_sidebar.width();
                        this.window_width = $(window).width();
                        if (iframe_visible) {
                            $iframe.hide();
                        }
                    },
                    _mouseDrag: function(e) {
                        this.sidebar_width += this.xStart - e.pageX;
                        this.xStart = e.pageX;

                        if (this.sidebar_width < 320) {
                            this.sidebar_width = 320;
                        } else if (this.window_width - this.sidebar_width < 380) {
                            this.sidebar_width = this.window_width - 380;
                        }
                        $preview_sidebar.width(this.sidebar_width);
                        this.$content.css({
                            marginRight: this.sidebar_width+'px'
                        });
                        $fixed_button_bar.css('right', (this.sidebar_width+16)+'px');
                        $(window).resize();
                    },
                    _mouseStop: function() {
                        if (iframe_visible) {
                            $iframe.show();
                        }

                        $.storage.set('blog/editor/preview_sidebar_width', this.sidebar_width);
                    }
                });
                $preview_sidebar.find('.column-resize-handler:first').blog_draghandler();

                // Toggle realtime preview mode when user clicks on an icon
                $('#realtime-preview-toggle').click(function() {
                    if (animation_in_progress) {
                        return;
                    }
                    if (!$post_form.hasClass('realtime-edit-mode')) {
                        $post_form.addClass('realtime-edit-mode');
                        $(this).addClass('b-close-live-editor');
                        realtimeOn();
                    } else {
                        $post_form.removeClass('realtime-edit-mode');
                        $(this).removeClass('b-close-live-editor');
                        realtimeOff();
                    }
                });

                // Update blog title in iframe in real time as user types
                $post_title.keyup(function() {
                    setIframePostTitle($post_title.val());
                });

                // Every once in a while update post contents in iframe
                setInterval(function() {
                    if (!iframe_loaded || !$post_form.hasClass('realtime-edit-mode')) {
                        return;
                    }
                    var current_id = null;
                    var current_item = $('#post-form .b-post-editor-toggle li.selected a');
                    current_id = current_item.attr('id');
                    if(current_id && $.wa_blog.editor.editors[current_id]) {
                        $.wa_blog.editor.editors[current_id].update($fake_textarea);
                    }
                    setIframePostTitle($post_title.val());
                    setIframePostContent($fake_textarea.val());
                }, 500);

                // Change preview template when user clicks on a preview link while in real-time preview mode
                $('#post-url-field').on('click', 'a', function() {
                    if (!$post_form.hasClass('realtime-edit-mode')) {
                        return;
                    }

                    var template_url = getTemplateUrl($(this));
                    if (template_url != $iframe.attr('src')) {
                        $iframe.attr('src', template_url);
                        var iframe_loaded = false;
                        $iframe.one('load', function() {
                            iframe_loaded = true;
                        });
                    }
                    return false;
                });

                // Change preview template when user changes a blog
                $('#post-url-field').change(function(e) {
                    if (!$post_form.hasClass('realtime-edit-mode')) {
                        return;
                    }

                    if (e.status != 'empty' && e.status != 'hidden_empty') {
                        showIframe();
                        ensureTemplate();
                    } else {
                        hideIframe();
                    }
                });

                // Update iframe height when content changes
                /*$.pm.bind('update_height', function(data) {
                    $iframe.height(parseInt(data));
                });*/

                // Height of an iframe depends on height of a window
                $(window).resize(function() {
                    $iframe.height($(window).height() - 30);
                    $iframe.width($preview_sidebar.width() - 10);
                });

                //
                // End of initialization. Function declarations below.
                //

                // Helper to send post title into the iframe
                function setIframePostTitle(str) {
                    $.pm({
                        target: $iframe[0].contentWindow,
                        type: 'update_title',
                        data: str
                    });
                }

                // Helper to send post text into the iframe
                function setIframePostContent(str) {
                    if ($album_selector.val()) {
                        str += '<p class="attached-album-description"><em>';
                        str += $_('Photos from the attached album will be displayed here.');
                        str += '</em></p>';
                    }
                    $.pm({
                        target: $iframe[0].contentWindow,
                        type: 'update_text',
                        data: str
                    });
                }

                // When $iframe src does not match any of static preview links,
                // change src to match the first <a>
                function ensureTemplate() {
                    var url = getAnyTemplateUrl();
                    if (url && url != $iframe.attr('src')) {
                        $iframe.attr('src', url);
                        var iframe_loaded = false;
                        $iframe.one('load', function() {
                            iframe_loaded = true;
                        });
                    }
                }

                // URL of preview template using data from an <a> element of static preview link
                function getTemplateUrl($a) {
                    return $a.data('preview-url') || '';
                }

                function getAnyTemplateUrl() {
                    var any_url = null;
                    $('#post-url-field a').each(function() {
                        var url = getTemplateUrl($(this));
                        if (!url || url.substr(0, 11) == 'javascript:') {
                            return;
                        }
                        if ($iframe.attr('src') == url) {
                            any_url = null;
                            return false;
                        }
                        any_url = url;
                    });

                    if (!any_url) {
                        any_url = $iframe.attr('src');
                        if (any_url == 'about:blank') {
                            any_url = null;
                        }
                    }

                    if (any_url) {
                        any_url += any_url.indexOf('?') >= 0 ? '&' : '?';
                        any_url += 'parent_url='+encodeURIComponent(location.origin);
                    }

                    return any_url;
                }

                // When real-time preview mode is on, show iframe and hide error message
                function showIframe() {
                    $iframe.show();
                    $not_available_message.hide();
                    iframe_visible = true;
                }

                // When real-time preview mode is on, hide preview and show a message saying that preview is not available
                function hideIframe() {
                    $iframe.hide();
                    $not_available_message.show();
                    iframe_visible = false;
                }

                // Animation to toggle real-time preview mode on
                function realtimeOn() {
                    if (!$('#post-url-field-no-settlements').is(':visible') && getAnyTemplateUrl()) {
                        showIframe();
                        ensureTemplate();
                    } else {
                        hideIframe();
                    }

                    $hidden_realtime_on.val('1');
                    animation_in_progress = true;
                    var right_sidebar_width = $.storage.get('blog/editor/preview_sidebar_width') || 600;

                    var $sidebars = $('.sidebar').hide();
                    var sidebar200px_width = $sidebars.first().width()-0;

                    $post_form.closest('.content').animate({
                        marginLeft: 0
                    }, 400);

                    $fixed_button_bar.css({
                        'min-width': 0,
                        right: (sidebar200px_width+16)+'px'
                    }).animate({
                        left: '15px',
                        right: (right_sidebar_width-0+15)+'px'
                    }, 400);

                    $post_form.find('.content').animate({
                        marginRight: right_sidebar_width+'px'
                    }, 400);
                    $preview_sidebar.css('width', 0).show().animate({
                        width: right_sidebar_width+'px'
                    }, 400);
                    $wa_header.slideUp(400);
                    setTimeout(function() {
                        animation_in_progress = false;
                        $(window).resize().scroll();
                    }, 410);
                }

                // Animation to toggle real-time preview mode off
                function realtimeOff() {

                    $hidden_realtime_on.val('');
                    var $sidebars = $('.sidebar');
                    var sidebar200px_width = $sidebars.first().width()-0;

                    animation_in_progress = true;
                    var $outer_content = $post_form.closest('.content').animate({
                        marginLeft: sidebar200px_width+'px'
                    }, 400);
                    var $inner_content = $post_form.find('.content').animate({
                        marginRight: sidebar200px_width+'px'
                    }, 400);
                    $preview_sidebar.hide();
                    $wa_header.slideDown(400);
                    $fixed_button_bar.animate({
                        left: (sidebar200px_width+15)+'px',
                        right: (sidebar200px_width+16)+'px'
                    }, 400);
                    setTimeout(function() {
                        $sidebars.show();
                        $preview_sidebar.hide();
                        animation_in_progress = false;
                        $outer_content.css('marginLeft', '');
                        $inner_content.css('marginRight', '');
                        $fixed_button_bar.css({
                            left: '',
                            right: ''
                        });
                        $(window).resize().scroll();
                    }, 410);
                }
            }
        },
        cloneTextarea: function($textarea,wrapper,editor) {
            var id = "editor_container_"+editor;
            $(wrapper).append($textarea.clone(true).attr({'id':id,'name':'text_'+editor,'disabled':true}));
            $textarea.hide();
            return id;
        },
        cut_hr: '<span class="b-elrte-wa-split-vertical elrte-wa_post_cut">%text%</span>',
        cut_str: '<!-- more %text%-->',
        htmlToWysiwyg: function(text) {
            return text.replace(/<!--[\s]*?more[\s]*?(text[\s]*?=[\s]*?['"]([\s\S]*?)['"])*[\s]*?-->/g, function(cut_str, p1, p2) {
                return p2 ? $.wa_blog.editor.cut_hr.replace('%text%', p2) : $.wa_blog.editor.cut_hr.replace('%text%', $.wa_blog.editor.options.cut_link_label_default);
            });
        },
        wysiwygToHtml: function(text) {
            var tmp = $('<p></p>').html(text);
            var cut_item = tmp.find('.elrte-wa_post_cut');
            if (cut_item.length) {
                var p = cut_item.html();
                if (!p || p === '<br>' || p === $.wa_blog.editor.options.cut_link_label_default) {
                    cut_item.replaceWith($.wa_blog.editor.cut_str.replace('%text%', ''));
                } else {
                    cut_item.replaceWith($.wa_blog.editor.cut_str.replace('%text%', 'text="' + p + '" '));
                }
                return tmp.html().replace('<span></span>', '');
            } else {
                return text;
            }
        },
        editors : {
            ace : {
                editor:null,
                container: null,
                inited: false,
                init : function($textarea) {
                    if(!this.inited) {

                        this.inited = true;
                        var options = $.wa_blog.editor.options;
                        this.container = $('<div id="blog-ace-editor"></div>').appendTo("#post_text_wrapper");
                        this.container.wrap('<div class="ace"></div>');

                        this.editor = ace.edit('blog-ace-editor');
                        ace.config.set("basePath", wa_url + 'wa-content/js/ace/');

                        this.editor.setTheme("ace/theme/eclipse");
                        var session = this.editor.getSession();

                        session.setMode("ace/mode/javascript");
                        session.setMode("ace/mode/css");
                        session.setMode("ace/mode/html");
                        session.setMode("ace/mode/smarty");
                        session.setUseWrapMode(true);
                        this.editor.$blockScrolling = Infinity;
                        this.editor.renderer.setShowGutter(false);
                        this.editor.setShowPrintMargin(false);
                        this.editor.setFontSize(13);
                        $('.ace_editor').css('fontFamily', '');
                        session.setValue($textarea.hide().val());
                        this.editor.moveCursorTo(0, 0);

                        if (navigator.appVersion.indexOf('Mac') != -1) {
                            this.editor.setFontSize(13);
                        } else if (navigator.appVersion.indexOf('Linux') != -1) {
                            this.editor.setFontSize(16);
                        } else {
                            this.editor.setFontSize(14);
                        }

                        this.editor.commands.addCommand({
                            name: "unfind",
                            bindKey: {
                                win: "Ctrl-F",
                                mac: "Command-F"
                            },
                            exec: function(editor, line) {
                                return false;
                            },
                            readOnly: true
                        });

                        var sidebarHeight = $('#post-form .sidebar .b-edit-options:first').height();
                        var heightUpdateFunction = function(editor, editor_id) {
                            // http://stackoverflow.com/questions/11584061/
                            var newHeight = editor.getSession().getScreenLength() * editor.renderer.lineHeight + editor.renderer.scrollBar.getWidth();
                            newHeight *= 1.02; //slightly extend editor height

                            var minHeight = sidebarHeight - 163;
                            if (newHeight < minHeight) {
                                newHeight = minHeight;
                            }

                            $('#' + editor_id).height(newHeight.toString() + "px");

                            // This call is required for the editor to fix all of
                            // its inner structure for adapting to a change in size
                            editor.resize();
                        };

                        var self = this;
                        var $window = $(window);

                        // Whenever a change happens inside the ACE editor, update
                        // the size again
                        session.on('change', function() {
                            heightUpdateFunction(self.editor, "blog-ace-editor");
                            $window.scroll(); // trigger sticky bottom buttons
                        });
                        setTimeout(function() {
                            heightUpdateFunction(self.editor, "blog-ace-editor");
                        }, 50);
                        $window.resize(function() {
                            self.editor.resize();
                            heightUpdateFunction(self.editor, "blog-ace-editor");
                        });

                        this.container.on('keydown', $.wa_blog.editor.editorKeyCallback());
                        this.container.on('keypress', $.wa_blog.editor.editorKeyCallback(true));
                    }

                    return true;
                },
                show: function($textarea) {
                    this.container.show();
                    this.container.parent().show();
                    var self = this;

                    if(self.editor/* && self.editor.editor*/) {
                        var text = $textarea.val();
                        text = $.wa_blog.editor.wysiwygToHtml(text);
                        var p = self.editor.getCursorPosition();
                        self.editor.setValue(text);
                        if (!$('#post-title').is(':focus')) {
                            self.editor.focus();
                        }
                        self.editor.navigateTo(p.row, p.column);
                    } else {
                        if(typeof(console) == 'object') {
                            console.log('wait for ace editor init');
                        }
                        self.show($textarea);
                    }

                },
                hide: function() {
                    this.container.hide();
                    this.container.parent().hide();
                },
                update : function($textarea) {
                    if(this.inited) {
                        $textarea.val(this.editor.getValue());
                    }
                },
                correctEditorHeight: function(height) {
                    return Math.max(height, $.wa_blog.editor.getMinEditorHeight()) + $.wa_blog.editor.getExtHeightShift();
                }
            },
            redactor: {
                options: {},
                inited:false,
                callback:false,
                editor: null,
                init : function($textarea) {
                    if(!this.inited) {
                        var $window = $(window);
                        var $save_button = $('#b-post-save-button');
                        var $postpublish_edit = $('#postpublish-edit');
                        var sidebar_height = $('#post-form .sidebar .b-edit-options:first').height();

                        var options = $.extend({
                            focus: true,
                            deniedTags: false,
                            minHeight: 300, //minHeight: Math.max(300, sidebar_height - 229),
                            linkify: false,
                            source: false,
                            paragraphy: false,
                            replaceDivs: false,
                            toolbarFixed: true,
                            replaceTags: {
                                'b': 'strong',
                                'i': 'em',
                                'strike': 'del'
                            },
                            removeNewlines: false,
                            removeComments: false,
                            imagePosition: true,
                            imageResizable: true,
                            imageFloatMargin: '1.5em',
                            buttons: ['format', 'bold', 'italic', 'underline', 'deleted', 'lists',
                                'image', 'video', 'file', 'table', 'link', 'alignment', 'fontcolor', 'fontsize', 'fontfamily'],
                            plugins: ['fontcolor', 'fontfamily', 'alignment', 'fontsize', 'table', 'video', 'cut'],
                            lang: wa_lang,
                            imageUpload: '?action=upload&r=2&absolute=1',
                            imageUploadFields: $textarea.data('uploadFields'),
                            callbacks: {
                                change: function () {
                                    if ($postpublish_edit.is(':visible')) {
                                        $postpublish_edit.removeClass('green').addClass('yellow');
                                    }
                                    if ($save_button.is(':visible')) {
                                        $save_button.removeClass('green').addClass('yellow');
                                    }
                                    // Make sure sticky bottom buttons behave correctly when height of an editor changes
                                    $window.scroll();
                                }
                            }
                        }, (options || {}));

                        options.callbacks = $.extend({
                            imageUploadError: function(json) {
                                console.log('imageUploadError', json);
                                alert(json.error);
                            },
                            syncClean: function (html) {
                                // Unescape '->' in smarty tags
                                return html.replace(/\{[a-z\$'"_\(!+\-][^\}]*\}/gi, function (match) {
                                    return match.replace(/-&gt;/g, '->');
                                });
                            },
                            syncBefore: function (html) {
                                html = html.replace(/{[a-z$][^}]*}/gi, function (match, offset, full) {
                                    var i = full.indexOf("</script", offset + match.length);
                                    var j = full.indexOf('<script', offset + match.length);
                                    if (i == -1 || (j != -1 && j < i)) {
                                        match = match.replace(/&gt;/g, '>');
                                        match = match.replace(/&lt;/g, '<');
                                        match = match.replace(/&amp;/g, '&');
                                        match = match.replace(/&quot;/g, '"');
                                    }
                                    return match;
                                });

                                return html;
                            }
                        }, (options.callbacks || {}));

                        // Modify image upload dialog to include tab from Photos app
                        if ($.wa_blog_options.photos_bridge_available) {
                            options.callbacks.modalOpened = function(name, $modal_wrapper) {
                                if (name != 'image') {
                                    return;
                                }

                                var redactor = this;
                                this.modal.width = 1100;
                                var $modal = this.modal.getModal();

                                // Contents of Select tab
                                var $photos_selector_wrapper = $('<div id="photos-image-selector-wrapper">');
                                var $custom_tab = $('<div class="redactor-modal-tab redactor-tab1" data-title="'+ $_('Select from Photos app') +'">').hide().append($photos_selector_wrapper).append('<div class="clear-both">');
                                var $total_photos_selected = $custom_tab.find('.total-photos-selected'); // !!! currently not used

                                // Tab headers
                                $modal.append($custom_tab);
                                this.modal.buildTabber();

                                // Controller for Photos selector tab
                                (function() {
                                    var total_photos_selected = 0;
                                    var selected_photos = {}; // id => url

                                    // Insert photos to blog post when user clicks on a button
                                    var $insert_button = $('<button id="redactor-modal-button-action" style="margin-top: 16px;">' + redactor.lang.get('insert') + '</button>').hide();
                                    $custom_tab.append($insert_button);

                                    // Cancel button closes the dialog
                                    var $cancel_button = $('<button id="redactor-modal-button-cancel" style="margin-top: 16px;">' + redactor.lang.get('cancel') + '</button>').hide();
                                    $custom_tab.append($cancel_button);

                                    // Load content from Photos app when user goes to its tab
                                    $('#redactor-modal-tabber a[rel="1"]').on('click', function() {
                                        if ($insert_button.is(':visible')) {
                                            return;
                                        }
                                        $insert_button.show();
                                        $cancel_button.show();
                                        $photos_selector_wrapper.html('<div class="block"><i class="icon16 loading"></i></div>');
                                        $.post('../photos/?module=photo&action=embedSelector', { app_id: 'blog' }, function(r) {
                                            $photos_selector_wrapper.html(r);
                                            updateEmbeddedHtml();
                                        });
                                    });
                                    $cancel_button.off('click').on('click', function() {
                                        redactor.modal.close();
                                    });
                                    $insert_button.on('click', function() {
                                        if (total_photos_selected > 0) {
                                            var html = [];
                                            $.each(selected_photos, function(photo_id, photo_url) {
                                                html.push('<figure><img src="'+photo_url+'"></figure>');
                                            });
                                            redactor.insert.html(html.join("\n"));
                                        }
                                        redactor.modal.close();
                                    });

                                    // Hide buttons when user switches back to Upload tab
                                    $('#redactor-modal-tabber a[rel="0"]').on('click', function() {
                                        $insert_button.hide();
                                        $cancel_button.hide();
                                    });

                                    // When user selects or deselects a photo, remember it and update UI
                                    $photos_selector_wrapper.on('change', 'input:checkbox', function() {
                                        var $checkbox = $(this);
                                        var photo_id = $checkbox.val();
                                        if (this.checked) {
                                            if (!selected_photos[photo_id]) {
                                                selected_photos[photo_id] = $checkbox.data('photoUrl');
                                                total_photos_selected++;
                                            }
                                        } else {
                                            if (selected_photos[photo_id]) {
                                                delete selected_photos[photo_id];
                                                total_photos_selected--;
                                            }
                                        }

                                        if (total_photos_selected > 0) {
                                            $total_photos_selected.text(total_photos_selected).closest('.hidden').show();
                                        } else {
                                            $total_photos_selected.text(total_photos_selected).closest('.hidden').hide();
                                        }
                                    });

                                    // Highlight previously selected photos again when user changes album
                                    $photos_selector_wrapper.on('reloaded', function(e) {
                                        updateEmbeddedHtml();
                                    });

                                    function updateEmbeddedHtml() {
                                        $photos_selector_wrapper.find('input:checkbox').each(function() {
                                            if (selected_photos[this.value]) {
                                                $(this).prop('checked', true).change();
                                            }
                                        });
                                        $(window).resize();
                                    }
                                })();
                            };
                        }

                        $textarea.redactor(options);
                        $textarea.redactor('core.box').css('z-index', 0);
                        $textarea.redactor('core.toolbar').css('z-index', 1);
                        this.editor = $textarea.data('redactor');
                        this.inited = true;
                    }
                    return true;
                },
                show: function($textarea) {
                    var text = $.wa_blog.editor.htmlToWysiwyg($textarea.val());
                    $textarea.val(text);

                    $('.redactor-box').show();
                    $textarea.redactor('core.editor').find('img[src*="$wa_url"]').each(function () {
                        var s = decodeURIComponent($(this).attr('src'));
                        $(this).attr('data-src', s);
                        $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
                    });
                    this.editor.code.set($textarea.val());
                    this.editor.observe.load();
                    this.editor.focus.start();
                },
                hide: function(textarea) {
                    $('.redactor-box').hide();
                },
                update : function($textarea) {
                    if(this.inited) {
                        var code = this.editor.code.get();
                        code = this.editor.clean.onSync(
                            this.editor.clean.onSet(code)
                        );
                        code = $.wa_blog.editor.wysiwygToHtml(code);
                        $textarea.val(code);
                    }
                }
            },
            photo_bridge : {
                container: null,
                inited: false,
                init : function($textarea) {
                    if(!this.inited) {
                        this.inited = true;
                        $textarea.hide();
                        this.container = $('#blog-photo_bridge-editor');
                        if (!this.container.length) {
                            return;
                        }

                        var $album_frontend_link = $('#album-frontend-link');
                        var $album_selector = this.container.find('select[name="album_id"]');
                        var $fields_public_only = this.container.find('.hidden.field');

                        var delay = 0;
                        $album_selector.change(function() {
                            if ($album_selector.val()) {
                                var frontend_link = $album_selector.children(':selected').data('frontend-link');
                                if (frontend_link) {
                                    $fields_public_only.slideDown(delay);
                                    $album_frontend_link.text(frontend_link);
                                } else {
                                    $fields_public_only.slideUp(delay);
                                    $fields_public_only.find('[name="album_link_type"]:checkbox').prop('checked', false);
                                }
                                $('#post-editor .show-when-album-selected').show();
                            } else {
                                $fields_public_only.slideUp(delay);
                                $('#post-editor .show-when-album-selected').hide();
                            }
                        }).change();
                        delay = 300;

                        var sidebar_height = $('#post-form .sidebar .b-edit-options:first').height();
                        this.container.css('min-height', sidebar_height - 100);
                    }
                    return true;
                },
                show: function(textarea) {
                    this.container.show();
                    var self = this;
                },
                hide: function() {
                    this.container.hide();
                },
                update: function(textarea) {
                    // Nothing to do!
                }
            }
        },
        getMinEditorHeight: function() {
            return 200;
        },
        getExtHeightShift: function() {
            return -70;
        },
        editorTooggle : function() {
            var self = $(this);
            if (!self.hasClass('selected')
                    && $.wa_blog.editor.selectEditor(self.attr('id'))) {
                $('.b-post-editor-toggle li.selected').removeClass('selected');
                self.parent().addClass('selected');
            }
            return false;
        },
        selectEditor : function(id, external) {
            if (this.editors[id]) {
                var $textarea = $("#" + this.options['content_id']);
                if($textarea.length) {
                    try {
                        if(this.editors[id].init($textarea)) {
                            var current_id = null;
                            if (!external) {
                                try {
                                    $.storage.set('blog/editor', id);
                                } catch(e) {
                                    this.log('Exception: '+e.message + '\nline: '+e.fileName+':'+e.lineNumber);
                                }
                            }
                            var current_item = $('.b-post-editor-toggle li.selected a');

                            if(current_item.length) {
                                current_item.parent().removeClass('selected');
                                if(current_id = current_item.attr('id')) {
                                    this.editors[current_id].update($textarea);
                                    this.editors[current_id].hide();
                                }
                            }

                            $('#' + id).parent().addClass('selected');
                            this.editors[id].show($textarea);

                            // Make sure sticky bottom buttons behave correctly when height of an editor changes
                            setTimeout(function() { $(window).scroll(); }, 0);
                        }
                    } catch(e) {
                        this.log('Exception: '+e.message + '\nline: '+e.fileName+':'+e.lineNumber, e.stack);
                        return false;
                    }
                    return true;
                } else {
                    this.log('Text container for "' + id + '" not found');
                    return false;
                }
            } else {
                this.log('Blog editor "' + id + '" not found');
                return false;
            }
        },
        editor_key: false,
        editorKeyCallback: function (press) {
            var self = this;

            if (press) {    // when keypress
                return function (e) {
                    // ctrl + s
                    if (e.ctrlKey && e.which == 115 && !$.wa_blog.editor.editor_key) {
                        self.onSaveHotkey(e);
                    }
                };
            } else {    // when keydown
                return function (e) {
                    // ctrl + s
                    $.wa_blog.editor.editor_key = false;
                    if (e.ctrlKey && e.which == 83) {
                        $.wa_blog.editor.editor_key = true;
                        self.onSaveHotkey(e);
                    }
                    if (e.metaKey) {
                        return;
                    }
                    if (
                        (e.which < 33 || e.which > 40) &&
                        (e.which > 27 || e.which == 8 || e.which == 13) &&
                        (e.which < 112 || e.which > 124) &&
                        (!e.ctrlKey || e.which != 67)
                    )
                    {
                        $('#b-post-save-button').removeClass('green').addClass('yellow');
                        $('#postpublish-edit').removeClass('green').addClass('yellow');
                    }
                };
            }
        },
        onSubmit: function() {
            var blog = $.wa_blog;
            for (var i in blog) {
                if (i != 'editor') {
                    if (blog[i].onSubmit && (typeof(blog[i].onSubmit) == 'function')) {
                        try {
                            blog[i].onSubmit();
                        } catch (e) {
                            if (typeof(console) == 'object') {
                                console.log(e);
                            }
                        }
                    }
                }
            }

            var $textarea = $("#" + this.options['content_id']);

            if($textarea.length) {
                var current_id = null;
                var current_item = $('.b-post-editor-toggle li.selected a');
                if(current_item.length && (current_id = current_item.attr('id')) ) {
                    this.editors[current_id].update($textarea);
                }
            }

            var button = $('#b-post-save-button');
            var color_class = 'green';

            if ((button.attr('name') == 'draft') || (button.attr('name') == 'deadline')) {
                color_class = 'grey';
            } else {
                button.removeClass('yellow').addClass(color_class);
                $('#postpublish-edit').removeClass('yellow').addClass(color_class);
            }
        },
        onSaveHotkey: function(e)
        {
            e.preventDefault();

            var draftButton = $('#b-post-save-draft-button');
            var saveButton = $('#b-post-save-button');

            if (draftButton.length) {
                draftButton.click();
            } else if (saveButton.length) {
                saveButton.click();
            }
        },
        currentDialog: null,
        closeCurrentDialog: function()
        {
            if (this.currentDialog) {
                this.currentDialog.waDialog().trigger('close');
            }
        },
        registerEditor : function(id, callback) {
            if (this.editors[id]) {
                this.log('Editor "' + id + '" already registered');
                return false;
            } else {
                this.editors[id] = callback;
                return true;
            }
        },
        log : function(message, stack) {
            if (typeof(console) == 'object') {
                console.log(message);
                if (stack) {
                    console.log(stack);
                }
            }
        },
        changeBlogHandler: function() {
            var id = parseInt($(this).attr('href').replace(/^.*#/,''));
            if($.wa_blog.editor.selectCurrentBlog(id)) {
                $('.dialog :input:checked').attr('checked',false);
                $('.dialog #b-post-publish-blog-'+id).attr('checked',true);
            }
            $.wa.dropdownsClose();
            return false;
        },
        changePublishBlogHandler: function() {
            if($(this).attr('checked')) {
                var id = parseInt($(this).val());
                $.wa_blog.editor.selectCurrentBlog(id);
            }
        },
        selectCurrentBlog: function(id)
        {
            var blog = $.wa_blog.editor.options.blogs[id];
            var prev_id = $.wa_blog.editor.options.current_blog_id;
            if(blog && (prev_id != id)) {
                var current_blog = $.tmpl('selected-blog',{'blog':blog});

                $('.current-blog').replaceWith(current_blog);
                $('#blog-selector-'+prev_id).removeClass('selected');
                var blog_selector = $('#blog-selector-'+id).addClass('selected');

                var prev_blog = $.wa_blog.editor.options.blogs[prev_id];
                $.wa_blog.editor.options.current_blog_id = parseInt(id);
                var post = $('.b-post');
                if(prev_blog) {
                    post.removeClass(prev_blog.color);
                }
                post.addClass(blog.color);

                $.wa_blog.editor.postUrlWidget.changeBlog(blog_selector.attr('data-blog-status'));
                return true;
            }
        }

    };

})(jQuery);
