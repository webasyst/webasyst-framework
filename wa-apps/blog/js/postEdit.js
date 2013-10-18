(function($) {
    $.wa_blog.editor = {

        options : {
            blogs: {},
            content_id : 'post_text',
            current_blog_id:null,
            //dateFormat: 'yy-mm-dd',
            dateMonthCount: 2,
            dateShowWeek: false,
            cut_link_label_defaul: '',
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
                editor = $.storage.get('blog/editor');
            } catch(e) {
                this.log('Exception: '+e.message + '\nline: '+e.fileName+':'+e.lineNumber);
            }

            if (editor) {
                editor = editor.replace(/\W+/g,'');
            } else {
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


            //deadline
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

            // ====== Functions declarations section ========

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

                var postUrlHandler;
                var cachedSlug = null;
                var cache = {};
                var changeBlog = function() {};

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
                        cache_key = [blogId, postId, postTitle].join('&');

                    if (cache[cache_key]) {
                        fn(cache[cache_key]);
                    } else {
                        if (cachedSlug != null) {
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

                /**
                 *
                 * Show widget. View of widget depends on descriptor
                 *
                 * @param object descriptor
                 */
                function show(descriptor)
                {
                    if (!descriptor) {
                        $('#post-url-field').show('fast');
                        return;
                    }

                    var wholeUrl = descriptor.link + descriptor.slug + '/';

                    $('#url-link').text(wholeUrl);
                    $('#url-link').attr('href', descriptor.preview_hash
                            ? wholeUrl + '?preview=' + descriptor.preview_hash
                            : wholeUrl
                    );
                    $('#pure-url').text(descriptor.link);

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
                                href : descriptor.preview_hash
                                            ? descriptor.other_links[k] + cachedSlug + '/?preview=' + descriptor.preview_hash
                                            : descriptor.other_links[k] + cachedSlug + '/'
                            });
                        }
                        var tmpl = descriptor.is_adding
                            ?   '<span class="${className}">${previewText}${link}' +
                                    '<span class="slug">{{if slug}}${slug}/{{/if}}</span>' +
                                '</span><br>'
                            : '<span class="${className}">${previewText}<a target="_blank" href="${href}">${link}' +
                                    '<span class="slug">{{if slug}}${slug}/{{/if}}</span></a>' +
                                '</span><br>';

                        var icon = $('#post-url-field').children(':first');

                        if (icon.is('.icon10')) {
                            tmpl = '<i class="' + icon.attr('class') + '"></i> ' + tmpl;
                        }

                        $('#other-urls').html($.tmpl(tmpl, data));
                    }

                    $('#post-url-field').show('fast');
                }

                /**
                 * Hide widget
                 */
                function hide()
                {
                    $('#post-url-field').hide('fast');
                    $('#post-url').val('');
                }

                function updateSlugs(slug,preview_hash)
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
                    if (!$('#post-url-field').length || $('#post-url-field').hasClass('no-settlements')) {
                        changeBlog = function(blog_status) {
                            if (blog_status == $.wa_blog.editor.blog_statuses['public']) {
                                show();
                            } else {
                                hide();
                            }
                        };
                        return;
                    }
                    postUrlHandler = function() {
                        var postId = $('input[name=post_id]').val();
                        if (!postId && !this.value) {
                            hide();
                            return;
                        }
                        var blogId = $('input[name=blog_id]').val();
                        getDescriptor(blogId, postId, this.value, function(descriptor) {
                            if (descriptor && !descriptor.is_private_blog) {
                                show(descriptor);
                            } else {
                                hide();
                            }
                        });
                    };

                    var postId = $('#post-form').find('input[name=post_id]').val();

                    if (!postId) {    // only when adding post handle blur-event
                        $('#post-title').blur(function() {
                            var self = this;
                            setTimeout(function() {
                                if (cachedSlug == null) {
                                    postUrlHandler.call(self);
                                }
                            }, 200);    // first we need wait for .change-blog handler
                        });
                    }

                    changeBlog = function() {
                        postUrlHandler.call($('#post-title').get(0));
                    };

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

        },
        cloneTextarea: function(textarea,wrapper,editor)
        {
            var id = "editor_container_"+editor;
            $(wrapper).append(textarea.clone(true).attr({'id':id,'name':'text_'+editor,'disabled':true}));
            textarea.hide();
            return id;
        },
        cut_hr: '<span class="b-elrte-wa-split-vertical" id="elrte-wa_post_cut">%text%</span>',
        cut_str: '<!-- more %text%-->',
        htmlToWysiwyg: function(text) {
            return text.replace(/<!--[\s]*?more[\s]*?(text[\s]*?=[\s]*?['"]([\s\S]*?)['"])*[\s]*?-->/g, function(cut_str, p1, p2) {
                return p2 ? $.wa_blog.editor.cut_hr.replace('%text%', p2) : $.wa_blog.editor.cut_hr.replace('%text%', $.wa_blog.editor.options.cut_link_label_defaul);
            });
        },
        wysiwygToHtml: function(text) {
            return text.replace(/<span[\s\S]*?id=['"]elrte-wa_post_cut['"][\s\S]*?>([\s\S]*?)<\/span>/g, function(cut_hr, p1) {
                if (!p1 || p1 == '<br>' || p1 == $.wa_blog.editor.options.cut_link_label_defaul) {
                    return $.wa_blog.editor.cut_str.replace('%text%', '');
                } else {
                    return $.wa_blog.editor.cut_str.replace('%text%', 'text="' + p1 + '" ');
                }
            });
        },
        editors : {
            ace : {
                editor:null,
                container: null,
                inited: false,
                init : function(textarea) {
                    if(!this.inited) {

                        this.inited = true;
                        var options = $.wa_blog.editor.options;
                        this.container = $('<div id="blog-ace-editor"></div>').insertAfter(textarea);
                        this.container.wrap('<div class="ace"></div>');
                        var height = $.wa_blog.editor.calcEditorHeight();

                        this.editor = ace.edit('blog-ace-editor');
                        //ace.config.set("basePath", wa_url + 'wa-content/js/ace/');

                        this.editor.setTheme("ace/theme/eclipse");
                        var session = this.editor.getSession();

                        session.setMode("ace/mode/javascript");
                        session.setMode("ace/mode/css");
                        session.setMode("ace/mode/html");
                        session.setMode("ace/mode/smarty");
                        session.setUseWrapMode(true);
                        this.editor.renderer.setShowGutter(false);
                        this.editor.setShowPrintMargin(false);
                        this.editor.setFontSize(13);
                        $('.ace_editor').css('fontFamily', '');
                        session.setValue(textarea.hide().val());
                        this.editor.focus();
                        this.editor.moveCursorTo(0, 0);

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

                        var heightUpdateFunction = function(editor, editor_id) {

                            // http://stackoverflow.com/questions/11584061/
                            var newHeight = editor.getSession().getScreenLength() * editor.renderer.lineHeight + editor.renderer.scrollBar.getWidth();

                            newHeight *= 1.02; //slightly extend editor height

                            var sidebarHeight = $('#post-form .sidebar:first .b-edit-options').height();
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

                        // Whenever a change happens inside the ACE editor, update
                        // the size again
                        session.on('change', function() {
                            heightUpdateFunction(self.editor, "blog-ace-editor");
                        });
                        setTimeout(function() {
                            heightUpdateFunction(self.editor, "blog-ace-editor");
                        }, 50);

                        $(window).resize(function() {
                            self.editor.resize();
                            heightUpdateFunction(self.editor, "blog-ace-editor");
                        });

                        this.container.on('keydown', $.wa_blog.editor.editorKeyCallback());
                        this.container.on('keypress', $.wa_blog.editor.editorKeyCallback(true));
                    }

                    return true;
                },
                show: function(textarea) {
                    this.container.show();
                    this.container.parent().show();
                    var self = this;
                    setTimeout(function() {
                        if(self.editor/* && self.editor.editor*/) {
                            var text = $.wa_blog.editor.wysiwygToHtml(textarea.val());
                            var p = self.editor.getCursorPosition();
                            self.editor.setValue(text);
                            self.editor.focus();
                            self.editor.navigateTo(p.row, p.column);
                        } else {
                            if(typeof(console) == 'object') {
                                console.log('wait for ace editor init');
                            }
                            self.show(textarea);
                        }
                    },100);

                },
                hide: function() {
                    this.container.hide();
                    this.container.parent().hide();
                },
                update : function(textarea) {
                    if(this.inited) {
                        textarea.val(this.editor.getValue());
                    }
                },
                correctEditorHeight: function(height) {
                    return Math.max(height, $.wa_blog.editor.getMinEditorHeight()) + $.wa_blog.editor.getExtHeightShift();
                }
            },
            elrte : {
                options: {},
                inited:false,
                callback:false,
                init : function(textarea) {
                    if(!this.inited) {
                        var options = $.wa_blog.editor.options;
                        elRTE.prototype.options.lang = wa_lang;
                        elRTE.prototype.options.wa_image_upload = '?module=post&action=image';
                        elRTE.prototype.options.wa_image_upload_path = wa_img_upload_path;
                        elRTE.prototype.beforeSave = function () {};
                        elRTE.prototype.options.toolbars.blogToolbar = ['wa_style', 'alignment', 'colors', 'format', 'indent', 'lists', 'wa_image', 'wa_links', 'wa_elements', 'wa_tables', 'direction', 'wa_post_cut'];

                        this.inited = $.wa_blog.editor.cloneTextarea(textarea,'#' + options['content_id']+'_wrapper','elrte');
                        var height = $.wa_blog.editor.calcEditorHeight();

                        var sidebarHeight = $('#post-form .sidebar:first .b-edit-options').height();
                        var minHeight = sidebarHeight - 83;
                        if (height < minHeight) {
                            height = minHeight;
                        }

                        $('#'+this.inited).elrte({
                            height: height,
                            cssfiles: [wa_url + "wa-content/css/wa/wa-1.0.css?v"+$.wa_blog.editor.options.version, wa_url + "wa-apps/blog/css/blog.css?v"+$.wa_blog.editor.options.version],
                            toolbar: 'blogToolbar',
                            lang: wa_lang,
                            width: "100%"
                        });
                        $('.workzone, iframe', '#post_text_wrapper').height(this.correctEditorHeight(height));

                    }
                    return true;
                },
                show: function(textarea) {
                    var text = $.wa_blog.editor.htmlToWysiwyg(textarea.val());
                    $('#'+this.inited).elrte('val', text);
                    $(".el-rte").css({'width':'100%'}).show();

                    if(!this.callback) {
                        $('.el-rte iframe').contents()
                        .keydown($.wa_blog.editor.editorKeyCallback())
                        .keypress($.wa_blog.editor.editorKeyCallback(true))
                        .keyup(function(e) {
                            //all dialogs should be closed when Escape is pressed
                            if (e.keyCode == 27) {
                                $(".dialog:visible").trigger('esc');
                            }
                        });
                        this.callback = true;
                    }
                    $('.el-rte iframe').contents().find('body').focus();
                },
                hide: function() {
                    $(".el-rte").hide();
                },
                update : function(textarea) {
                    if(this.inited) {
                        textarea.val($('#editor_container_elrte').elrte('val'));
                    }
                },
                correctEditorHeight: function(height) {
                    var decrease = 0;
                    $('#post_text_wrapper .el-rte').children('div:not(:hidden)').each(function() {
                        if (this.className != 'workzone') {
                            decrease += $(this).outerHeight(true);
                        }
                    });

                    return Math.max(height, $.wa_blog.editor.getMinEditorHeight()) - decrease + $.wa_blog.editor.getExtHeightShift();
                }
            }
        },
        getMinEditorHeight: function() {
            return 200;
        },
        getExtHeightShift: function() {
            return -70;
        },
        calcEditorHeight: function() {
            var viewedAreaHeight = $(document.documentElement).height();
            var editorAreaHeightOffset = $('#post-editor').offset();
            var buttonsBarHeight = $('#buttons-bar').outerHeight(true);
            return height = viewedAreaHeight - editorAreaHeightOffset.top - buttonsBarHeight;
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
                var textarea = $("#" + this.options['content_id']);
                if(textarea.length) {
                    try {
                        if(this.editors[id].init(textarea)) {
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
                                    this.editors[current_id].update(textarea);
                                    this.editors[current_id].hide();
                                }
                            }

                            $('#' + id).parent().addClass('selected');
                            this.editors[id].show(textarea);
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

            var textarea = $("#" + this.options['content_id']);

            if(textarea.length) {
                var current_id = null;
                var current_item = $('.b-post-editor-toggle li.selected a');
                if(current_item.length && (current_id = current_item.attr('id')) ) {
                    this.editors[current_id].update(textarea);
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

