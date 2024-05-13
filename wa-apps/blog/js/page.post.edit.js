( function($) {

    var PostEditPage = ( function($) {

        PostEditPage = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$form = that.$wrapper.find("form#post-form");
            that.$errors_place = that.$wrapper.find(".js-errors-place");

            // CONST
            that.templates = options["templates"];
            that.urls = options["urls"];

            that.post_id = options["post_id"];
            that.blog_id = options["blog_id"];

            that.wa_url = options["wa_url"];
            that.wa_lang = options["wa_lang"];
            that.wa_img_upload_path = options["wa_img_upload_path"];
            that.wa_blog_options = options["wa_blog_options"];

            // DYNAMIC VARS
            that.submit_data = {};
            that.is_locked = false;
            that.transliterated = false;

            // INIT
            that.init();
        };

        PostEditPage.prototype.init = function() {
            var that = this;

            // GENERAL
            that.$wrapper.find(".js-datepicker").each( function() {
                initDatepicker( $(this) );
            });

            // CONTENT
            that.initTitle();
            that.initEditor();
            that.initPostUrl();

            // ASIDE
            that.initBlogSection();
            that.initAuthorSection();
            that.initDeadlineSection();
            that.initMetaSection();

            // PAGE
            that.initPostActions();
        };

        PostEditPage.prototype.initEditor = function() {
            var that = this;

            that.$form.on("submit", function(event) {
                event.preventDefault();
            });

            $.wa_blog.editor = {

                options : {
                    blogs: {},
                    content_id : 'post_text',
                    current_blog_id: null,
                    cut_link_label_default: '',
                    version: '1.0'
                },

                blog_statuses: {
                    'private': 'private',
                    'public': 'public'
                },

                init : function(options) {
                    var self = this;

                    self.options = $.extend(self.options, options);

                    var editor = getEditor(this.editors);

                    if (!this.selectEditor(editor, true)) {
                        for(editor in this.editors) {
                            if (this.selectEditor(editor, true)) {
                                break;
                            }
                        }
                    }

                    $('.b-post-editor-toggle li a').on("click", function(event) {
                        event.preventDefault();

                        var $link = $(this),
                            $wrapper = $link.closest(".b-post-editor-toggle");

                        var active_class = "selected",
                            editor_id = $link.attr('id'),
                            is_active = $link.hasClass('selected');

                        if (!is_active && $.wa_blog.editor.selectEditor(editor_id)) {
                            $wrapper.find("li." + active_class).removeClass(active_class);
                            $link.closest("li").addClass(active_class);
                        }
                    });

                    function getEditor(editors) {
                        var editor_id = null;

                        try {
                            editor_id = localStorage.getItem('blog/editor') || 'redactor';
                        } catch(e) {
                            this.log('Exception: '+e.message + '\nline: '+e.fileName+':'+e.lineNumber);
                        }

                        if (!editor_id || !editors[editor_id]) {
                            editor_id = Object.keys(editors)[0];
                        }

                        return editor_id;
                    }

                    /*

                    // ====== Functions declarations section ========


                    self.inlineSaveController = setupInlineSave({
                        beforeSave: function() {
                            if (!validateDatetime()) {
                                return false;
                            }
                            $.wa_blog.editor.onSubmit();
                        },

                        afterSave: function(data) {
                            //$('#js-post-publish').removeClass('yellow').addClass('green');
                            //$('#js-post-publish-confirm').removeClass('yellow').addClass('green');
                            resetEditDatetimeReady.call($('#inline-edit-datetime').get(0), data.formatted_datetime);
                            if (self.inlineSaveController.getAction() === 'draft') {
                                $('#post-url-field .small').removeClass('small').addClass('hint');
                            }

                        }
                    });

                    function resetEditDatetimeReady(datetime) {
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
                     * /
                    function findHintToDatepicker(input) {
                        var hint = input.siblings('.hint:first');
                        if (hint.length <= 0) {
                            hint = input.parent().siblings('.hint:first');
                        }
                        return hint;
                    }

                    /**
                     * Initial validating date before send request to server
                     * @returns {Boolean}
                     * /
                    function validateDatetime() {
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

                        /**
                         * Validate hour
                         * @param hour
                         * @returns {Boolean}
                         * /
                        function validateHour(hour) {
                            return hour >= 0 && hour <= 24;
                        }

                        /**
                         * Validate Minute
                         * @param minute
                         * @returns {Boolean}
                         * /
                        function validateMinute(minute) {
                            return minute >= 0 && minute <= 60;
                        }
                    }

                    function hideDatetimeError(datepickerInput) {
                        datepickerInput.parent().find('input[type=text]').removeClass('error');
                        findHintToDatepicker(datepickerInput).removeClass('errormsg');
                    }

                    function showDatetimeError(datepickerInput) {
                        datepickerInput.parent().find('input[type=text]').addClass('error');
                        findHintToDatepicker(datepickerInput).addClass('errormsg');
                    }

                    /**
                     *
                     * /
                    function setupInlineSave(options) {
                        options = options || {};

                        var action = '';
                        var inline = false;

                        var beforeSave = options.beforeSave || function() {};
                        var afterSave = options.afterSave || function() {};

                        init();

                        function init() {

                            $('input[type=submit], input[type=button], a.js-submit').on("click", function() {

                                if($(this).hasClass('js-submit')) {
                                    var question = $(this).attr('title')||$(this).text()||'Are you sure?';
                                    if(!confirm(question)) {
                                        return false;
                                    }
                                }

                                if ($('#post-id').val() && (this.id === 'js-post-publish' ||
                                    this.id == 'b-post-draft-save')) {
                                    inline = true;
                                }
                                action = this.name;
                                if (action == 'deadline' || action === 'schedule') {
                                    $('#' + this.name + '-dialog').find('input[name^=datetime]').attr('disabled', false);
                                }
                                save();
                                return false;
                            });
                            $('#post-url').focus(function() {
                                //hideErrorMsg($(this));
                            });
                        }

                        function showErrorMsg(input, msg) {
                            var selector = '#message-' + input.attr('id');
                            input.addClass('error');
                            $(selector).addClass('errormsg').text(msg);
                        }

                        function hideErrorMsg(input) {
                            var selector = '#message-' + input.attr('id');
                            input.removeClass('error');
                            $(selector).removeClass('errormsg').text('');
                        }

                        function showErrors(errors) {
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

                        function hideErrors() {
                            hideErrorMsg($('#post-url'));
                            var input = $('.datepicker:not(:disabled)');
                            if (input.length) {
                                input.datepicker('hide');
                                hideDatetimeError(input);
                            }
                        }

                        function save() {
                            if (beforeSave() !== false) {
                                that.save().then(afterSave);
                            }
                        }

                        function onFail(errors) {
                            if (!errors.datetime) {
                                // if (action == 'deadline') {
                                //     var date = $('#js-post-deadline-dialog .datepicker').val();
                                //     if (date) {
                                //         $('#publication-deadline-changable-part').html($.tmpl('publication-deadline-setted', {
                                //             date: date
                                //         }));
                                //     } else {
                                //         $('#publication-deadline-changable-part').html($.tmpl('publication-deadline-setted'));
                                //     }
                                //     $('#b-post-draft-save').attr('name', 'deadline');
                                // }
                            }
                        }

                        function updateStatusIcon(status, fn) {
                            if (!status) {
                                $('#form-status').fadeOut(fn && typeof(fn) == 'function' ? fn : function() {});
                            } else {
                                $('#form-status span').hide();
                                $('#form-status #' + status + '-status').show();
                                $('#form-status').show();
                            }
                        }

                        function setAction(_action) {
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
                    */
                },
                cut_hr: '<span class="b-elrte-wa-split-vertical elrte-wa_post_cut">%text%</span>',
                cut_str: '<!-- more %text%-->',
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
                                ace.config.set("basePath", that.wa_url + 'wa-content/js/ace/');

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

                                var heightUpdateFunction = function(editor, editor_id) {
                                    // http://stackoverflow.com/questions/11584061/
                                    var newHeight = editor.getSession().getScreenLength() * editor.renderer.lineHeight + editor.renderer.scrollBar.getWidth();
                                    newHeight *= 1.02; //slightly extend editor height

                                    var minHeight = 100;
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

                                var options = $.extend({
                                    focus: true,
                                    deniedTags: false,
                                    minHeight: 300,
                                    linkify: false,
                                    source: false,
                                    paragraphy: false,
                                    replaceDivs: false,
                                    toolbarFixed: true,
                                    toolbarFixedTopOffset: $("#wa-nav").outerHeight(),
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
                                    imageTypes: ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
                                    buttons: ['format', 'bold', 'italic', 'underline', 'deleted', 'lists',
                                        'image', 'video', 'file', 'table', 'link', 'alignment', 'fontcolor', 'fontsize', 'fontfamily'],
                                    plugins: ['fontcolor', 'fontfamily', 'alignment', 'fontsize', 'table', 'video', 'cut'],
                                    lang: that.wa_lang,
                                    imageUpload: '?action=upload&r=2&absolute=1',
                                    imageUploadFields: $textarea.data('uploadFields'),
                                    placeholder: $.wa.translate("Your post"),
                                    callbacks: {
                                        change: function () {
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
                                if (that.wa_blog_options.photos_bridge_available) {
                                    options.callbacks.modalOpened = function(name, $modal_wrapper) {
                                        if (name != 'image') {
                                            return;
                                        }

                                        var redactor = this;
                                        this.modal.width = 1100;
                                        var $modal = this.modal.getModal();

                                        // Contents of Select tab
                                        var $photos_selector_wrapper = $('<div id="photos-image-selector-wrapper">');
                                        var $custom_tab = $('<div class="redactor-modal-tab redactor-tab1" data-title="'+ $.wa.translate("Select from Photos app") +'">').hide().append($photos_selector_wrapper).append('<div class="clear-both">');
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
                                $(this).attr('src', s.replace(/\{\$wa_url\}/, that.wa_url));
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

                                this.container.css('min-height', 100);
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
                getMinEditorHeight: function() {
                    return 200;
                },
                getExtHeightShift: function() {
                    return -70;
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
                                            localStorage.setItem('blog/editor', id);
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
                log : function(message, stack) {
                    if (typeof(console) == 'object') {
                        console.log(message);
                        if (stack) {
                            console.log(stack);
                        }
                    }
                }
            };

            $.wa_blog.editor.init(that.wa_blog_options);
        };

        PostEditPage.prototype.initTitle = function() {
            var that = this;

            var $title = that.$wrapper.find(".js-post-title"),
                $message = null;

            if (!that.post_id) {
                setTimeout( function() {
                    $title.trigger("focus");
                }, 100);
            }

            var maxlength = parseInt($title.attr("maxlength"));
            if (maxlength) {
                $title.on("keydown", function(event) {
                    toggleMessage($title.val().length >= maxlength);

                    function toggleMessage(show) {
                        if (show) {
                            if (!$message) {
                                var message = "<div class=\"message error\">" + $.wa.translate("input_maxlength").replace(/%d/, maxlength) + "</div>";
                                $message = $(message).insertAfter($title);
                            }
                        } else {
                            if ($message) {
                                $message.remove();
                                $message = null;
                            }
                        }
                    }
                });
            }
        };

        PostEditPage.prototype.initPostUrl = function() {
            var that = this;

            var $section = that.$wrapper.find(".js-post-url-section");
            if (!$section.length) { return false; }

            var postUrlWidget = setupPostUrlWidget();

            that.$wrapper.on("successful_save", function(event, data) {
                postUrlWidget.resetEditReady();
                postUrlWidget.updateSlugs(data.url, data.preview_hash||false);
            });

            function setupPostUrlWidget() {
                var cachedSlug = null,
                    cache = {},
                    blog_settings = {};

                var $title = that.$wrapper.find(".js-post-title");

                $title.on("blur", postUrlHandler);

                var post_id = that.$form.find("#post-id").val();
                if (post_id) { postUrlHandler(); }

                $section.on("click", ".js-edit-url", setEditReady);

                $('#post-url').keyup(function(e) {
                    // ignore when blur from other input to this input
                    if (e.keyCode !== 9) {
                        if (this.value !== cachedSlug) {
                            updateSlugs(this.value);
                        }
                    }
                });

                return {
                    setEditReady: setEditReady,
                    resetEditReady: resetEditReady,
                    updateSlugs: updateSlugs,
                    changeBlog: postUrlHandler
                };

                function postUrlHandler() {
                    // Blog app has no frontend at all?
                    if (!$('#post-url-field').length) {
                        hide('empty');
                        return;
                    }

                    var title_value = $title.val(),
                        blog_id = $('input[name="blog_id"]').val(),
                        post_id = that.$form.find("#post-id").val();

                    if (blog_settings[blog_id] && blog_settings[blog_id].no_frontend) {
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

                    getDescriptor(blog_id, post_id, title_value, function(descriptor) {
                        if (!descriptor) {
                            return;
                        }
                        if (!blog_settings[blog_id]) {
                            blog_settings[blog_id] = {
                                id: blog_id,
                                no_frontend: descriptor.is_private_blog || !descriptor.link
                            };
                        }

                        if (blog_settings[blog_id].no_frontend) {
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

                    /**
                     *
                     * Get descriptor from server by using ajax
                     *
                     * @param blog_id
                     * @param post_id
                     * @param postTitle
                     * @returns object descriptor
                     */
                    function getDescriptor(blog_id, post_id, postTitle, fn) {
                        var descriptor = null,
                            request = { 'post_title': postTitle, 'post_id': post_id, 'blog_id': blog_id },
                            cache_key = [blog_id, post_id].join('&');

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
                                    that.transliterated = true;
                                    fn(descriptor);
                                }
                            });
                        }
                    }
                }

                function updateHtml(descriptor) {
                    var wholeUrl = descriptor.link + descriptor.slug + '/';

                    $('#url-link')
                        .text(wholeUrl)
                        .attr('href', descriptor.preview_hash ? wholeUrl + '?preview=' + descriptor.preview_hash : wholeUrl );

                    $('#pure-url').text(descriptor.link);
                    $('#preview-url').data('preview-url', descriptor.preview_link);

                    if (descriptor.slug && !cachedSlug) {
                        $('#post-url').val(descriptor.slug);
                        cachedSlug = descriptor.slug;
                    } else {
                        $('#post-url').val(cachedSlug);
                    }

                    var className = descriptor.is_adding ? 'small' : descriptor.is_published ? 'small' : 'hint';

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

                        // $('#other-urls').html($.tmpl(tmpl, data));
                    }
                }

                /**
                 * Show widget. View of widget depends on descriptor
                 * @param {Object} descriptor
                 */
                function show(descriptor) {
                    descriptor && updateHtml(descriptor);
                    $('#post-url-field').show('fast').trigger($.Event('change', { status: 'visible' }));
                }

                /**
                 * Hide widget
                 */
                function hide(status) {
                    // 'empty' = no frontend routing for selected blog
                    // 'hidden' = frontend routing exists, but no title present, and the whole control is hidden
                    // 'hidden_empty' = no frontend routing and whole control is hidden
                    status = status || 'hidden';
                    cachedSlug = null;
                    var $post_url = $('#post-url').val('');

                    $('#post-url-field').hide('fast').trigger($.Event('change', { status: status }));
                }

                function updateSlugs(slug, preview_hash) {
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

                function setEditReady() {
                    $('#url-editable').hide();
                    $('#url-edit').show();
                    $('#post-url').focus();
                }

                function resetEditReady() {
                    $('#url-editable').show();
                    $('#url-edit').hide();
                }
            }
        };

        PostEditPage.prototype.initPostActions = function() {
            var that = this;

            var loading = "<span class=\"icon\" style=\"margin-left: .5rem\"><i class=\"fas fa-spinner fa-spin\"></i></span>";

            // delete
            that.$wrapper.on("click", ".js-delete-post", function(event) {
                event.preventDefault();

                if (that.is_locked) { return false; }

                var post_id = that.post_id;

                $.waDialog({
                    html: that.templates["post-delete-dialog"],
                    onOpen: function($wrapper, dialog) {
                        $wrapper.on("click", ".js-delete-post", function() {
                            that.is_locked = true;
                            deletePost(post_id)
                                .always( function() {
                                    that.is_locked = false;
                                }).done( function() {
                                    dialog.close();
                                    location.href = $.wa_blog.app_url;
                            });
                        });
                    }
                });

                function deletePost(post_id) {
                    var href = "?module=post&action=delete",
                        data = { "id[]": post_id };

                    return $.post(href, data, "json");
                }
            });

            // publish
            that.$wrapper.on("click", ".js-publish-post", function(event) {
                event.preventDefault();
                if (that.is_locked) { return false; }

                var $submit_button = $(this),
                    $loading = $(loading).appendTo($submit_button.attr("disabled", true));

                that.save({
                    action: "published",
                    reload: true
                }).always( function () {
                    $loading.remove();
                    $submit_button.attr("disabled", false);
                });
            });

            // publish with confirm
            that.$wrapper.on("click", ".js-publish-post-with-confirm", function(event) {
                event.preventDefault();
                if (that.is_locked) { return false; }

                $.waDialog({
                    html: that.templates["post-publish-dialog"],
                    onOpen: function($wrapper, dialog) {
                        var blog_id = null;

                        $wrapper.on("change", ".js-blog-radio", function() {
                            blog_id = $(this).val();
                        });

                        $wrapper.on("click", ".js-submit-button", function() {
                            var $submit_button = $(this);

                            if (blog_id) { that.$wrapper.trigger("update-blog-dropdown", blog_id); }

                            var $loading = $(loading).appendTo($submit_button.attr("disabled", true));

                            that.save({
                                action: "published",
                                reload: true
                            }).always( function () {
                                $loading.remove();
                                $submit_button.attr("disabled", false);
                                dialog.close();
                            });
                        });
                    }
                });
            });

            // unpublish
            that.$wrapper.on("click", ".js-unpublish-post", function(event) {
                event.preventDefault();
                if (that.is_locked) { return false; }

                var $submit_button = $(this);

                $.wa.confirm({
                    title: $(this).attr("title"),
                    text: $.wa.translate("Are you sure?")
                }).then( function() {

                    var $loading = $(loading).appendTo($submit_button.attr("disabled", true));

                    that.save({
                        action: "unpublish",
                        reload: true
                    }).always( function () {
                        $loading.remove();
                        $submit_button.attr("disabled", false);
                    });

                });
            });

            // save
            that.$wrapper.on("click", ".js-save-post", function(event) {
                event.preventDefault();
                if (that.is_locked) { return false; }

                var $submit_button = $(this),
                    $loading = $(loading).appendTo($submit_button.attr("disabled", true));

                that.save({
                    action: $submit_button.data("action"),
                    reload: true
                }).always( function () {
                    $loading.remove();
                    $submit_button.attr("disabled", false);
                });
            });

            // schedule
            that.$wrapper.on("click", ".js-schedule-post", function(event) {
                event.preventDefault();

                var $submit_button = $(this),
                    $loading = $(loading).appendTo($submit_button.attr("disabled", true));

                $.waDialog({
                    html: that.templates["post-schedule-dialog"],
                    onOpen: function($dialog, dialog) {

                        if (that.submit_data["scheduled-dialog"]) {
                            updateDialog($dialog, that.submit_data["scheduled-dialog"]);
                        }

                        $dialog.find(".js-user-date-format").text(getDateFormat());

                        var $datepicker = $dialog.find(".js-datepicker");
                        if ($datepicker.length) {
                            initDatepicker($datepicker);
                        }

                        $dialog.on("click", ".js-submit-button", function() {
                            var $submit_button = $(this),
                                $loading = $(loading).appendTo($submit_button.attr("disabled", true));

                            that.submit_data["scheduled-dialog"] = $dialog.find("form:first").serializeArray();

                            that.save({
                                action: "scheduled",
                                reload: true
                            }).always( function () {
                                $loading.remove();
                                $submit_button.attr("disabled", false);

                                dialog.close();
                            });
                        });

                        $dialog.on("click", ".cron-command", function() {
                            var $target = $(this),
                                $input = $('<input type="text" readonly="readonly" />').val( $target.text() );

                            $input
                                .on("focus", function () {
                                    $(this).select();
                                })
                                .on("mouseup", function(event){
                                    event.preventDefault();
                                });

                            $target.replaceWith($input);
                            $input.select();
                        });

                        function updateDialog($dialog, data) {
                            $.each(data, function(i, item) {
                                var $field = $dialog.find("[name=\"" + item.name + "\"]");
                                if ($field.length) {
                                    $field.val(item.value);
                                }
                            });
                        }
                    }
                });
            });

            // Control + S
            $(document).on("keydown", watcher);
            function watcher(event) {
                var is_exist = $.contains(document, that.$wrapper[0]);
                if (is_exist) {
                    if (event.ctrlKey && event.keyCode === 83) {
                        event.preventDefault();
                        that.$wrapper.find(".js-save-post").trigger("click");
                    }
                } else {
                    $(document).off("keydown", watcher);
                }
            }
        };

        PostEditPage.prototype.initBlogSection = function() {
            var that = this;

            var $dropdown = that.$wrapper.find(".js-post-blog-dropdown");
            if ($dropdown.length) {
                var $field = $dropdown.find(".js-post-blog-field");
                $dropdown.waDropdown({
                    hover: false,
                    items: ".menu > li > a",
                    ready: function(dropdown) {

                        /**
                         * @description this event occurs in the news publication dialog
                         * */
                        that.$wrapper.on("update-blog-dropdown", function(event, blog_id) {
                            dropdown.setValue("blog-id", blog_id);
                        });

                    },
                    change: function(event, target, dropdown) {
                        var blog_id = $(target).data("blog-id");
                        $field.val(blog_id).trigger("change");
                        that.blog_id = blog_id;
                    }
                });
            }
        };

        PostEditPage.prototype.initAuthorSection = function() {
            var that = this;

            var $dropdown = that.$wrapper.find(".js-post-author-dropdown");
            if ($dropdown.length) {
                var $field = $dropdown.find(".js-post-author-field");
                $dropdown.waDropdown({
                    hover: false,
                    items: ".menu > li > a",
                    change: function(event, target, dropdown) {
                        var author_id = $(target).data("author-id");
                        $field.val(author_id).trigger("change");
                    }
                });
            }
        };

        PostEditPage.prototype.initDeadlineSection = function() {
            var that = this;

            var $section = that.$wrapper.find(".js-post-deadline-section");
            if (!$section.length) { return false; }

            var $post_date = that.$wrapper.find(".js-post-deadline-date");

            that.$wrapper.find(".js-date-format").text(getDateFormat());

            // link for start inline-editing of date
            $section.on("click", ".js-extend-section", function(event) {
                event.preventDefault();
                $(this).hide();
                $section.toggleClass("is-extended");
            });

            $section.on("click", ".js-manage-post-deadline", function(event) {
                event.preventDefault();

                $.waDialog({
                    html: that.templates["post-deadline-dialog"],
                    onOpen: function($dialog, dialog) {

                        if (that.submit_data["deadline-dialog"]) {
                            updateDialog($dialog, that.submit_data["deadline-dialog"]);
                        }

                        $dialog.find(".js-user-date-format").text(getDateFormat());

                        var $datepicker = $dialog.find(".js-datepicker");
                        if ($datepicker.length) {
                            initDatepicker($datepicker);
                        }

                        $dialog.on("click", ".js-submit-button", function(event) {
                            event.preventDefault();
                            that.submit_data["deadline-dialog"] = $dialog.find("form:first").serializeArray();
                            that.save({
                                action: that.$wrapper.find(".js-save-post").attr("action"),
                                reload: true
                            });
                            updatePost($dialog);
                            dialog.close();
                        });
                    }
                });
            });

            function updateDialog($dialog, data) {
                $.each(data, function(i, item) {
                    var $field = $dialog.find("[name=\"" + item.name + "\"]");
                    if ($field.length) {
                        $field.val(item.value);
                    }
                });
            }

            function updatePost($dialog) {
                var date = $dialog.find(".js-datepicker").val();
                $post_date.text(date);
            }
        };

        PostEditPage.prototype.initMetaSection = function() {
            var that = this;

            var $section = that.$wrapper.find(".js-post-meta-section");
            if (!$section.length) { return false; }

            var $post_meta_title = that.$wrapper.find(".js-post-edit-meta-title"),
                $post_meta_keywords = that.$wrapper.find(".js-post-edit-meta-keywords"),
                $post_meta_description = that.$wrapper.find(".js-post-edit-meta-description"),
                $post_meta_params = that.$wrapper.find(".js-post-edit-custom-params"),
                $post_meta_empty = that.$wrapper.find(".js-post-edit-no-meta");

            $section.on("click", ".js-manage-post-meta", function(event) {
                event.preventDefault();

                $.waDialog({
                    html: that.templates["post-meta-dialog"],
                    onOpen: function($dialog, dialog) {

                        if (that.submit_data["meta-dialog"]) {
                            updateDialog($dialog, that.submit_data["meta-dialog"]);
                        }

                        $dialog.on("click", ".js-submit-button", function(event) {
                            event.preventDefault();

                            that.submit_data["meta-dialog"] = $dialog.find("form:first").serializeArray();
                            that.save({
                                action: that.$wrapper.find(".js-save-post").attr("action"),
                                reload: true
                            });
                            updatePost($dialog);

                            dialog.close();
                        });

                        $dialog.find("textarea").each( function() {
                            initTextareaResize($(this));
                        });

                        function initTextareaResize($textarea) {
                            onResize();

                            $textarea.on("keyup", onResize);

                            function onResize() {
                                $textarea.css("height", "auto");
                                $textarea.css("overflow", "hidden");
                                $textarea.css("height", $textarea[0].scrollHeight + "px");
                                $dialog.trigger("refresh");
                                $textarea.css("overflow", "");
                            }
                        }
                    }
                });
            });

            function updateDialog($dialog, data) {
                $.each(data, function(i, item) {
                    var $field = $dialog.find("[name=\"" + item.name + "\"]");
                    if ($field.length) {
                        $field.val(item.value);
                    }
                });
            }

            function updatePost($dialog) {
                var meta_title = $dialog.find(':input[name=meta_title]').val(),
                    meta_keywords = $dialog.find(':input[name=meta_keywords]').val(),
                    meta_description = $dialog.find(':input[name=meta_description]').val(),
                    params = $dialog.find(':input[name=params]').val();

                if (meta_title) {
                    $post_meta_title.show().find('.val').text(meta_title);
                } else {
                    $post_meta_title.hide();
                }

                if (meta_keywords) {
                    $post_meta_keywords.show().find('.val').text(meta_keywords);
                } else {
                    $post_meta_keywords.hide()
                }

                if (meta_description) {
                    $post_meta_description.show().find('.val').text(meta_description);
                } else {
                    $post_meta_description.hide();
                }

                if (params) {
                    $post_meta_params.show().html(params.trim().split("\n").join("<br>") + "<br>");
                } else {
                    $post_meta_params.hide();
                }

                var is_empty = true;
                if (meta_title || meta_keywords || meta_description || params) {
                    $post_meta_empty.hide();
                } else {
                    $post_meta_empty.show();
                }
            }
        };

        /**
         * @param {Object?} options
         * @return {Promise}
         * */
        PostEditPage.prototype.save = function(options) {
            options = (typeof options === "object" ? options : {});

            var that = this,
                deferred = $.Deferred();

            if (!that.is_locked) {
                that.is_locked = true;

                that.renderErrors(null);

                var href = that.urls["save"],
                    data = that.getData(options);

                $.post(href, data, "json")
                    .always( function() {
                        that.is_locked = false;
                    })
                    .done( function(response) {
                        if (response.status === "ok") {
                            that.submit_data = {};

                            if (response.data.redirect) {
                                that.is_locked = true;
                                location.href = response.data.redirect;

                            } else if (options.reload) {
                                that.is_locked = true;
                                location.reload();

                            } else if (response.data.id) {
                                that.$wrapper.find('#post-id').val(response.data.id);
                                that.$wrapper.trigger("successful_save", response.data);
                                deferred.resolve(response.data);
                            }

                        } else if (response.errors) {
                            that.renderErrors( formatErrors(response.errors) );
                            deferred.reject();
                        } else {
                            console.error("Request failed.");
                            alert("Request failed.");
                            deferred.reject();
                        }
                    })
                    .fail( function() {
                        console.error("Request failed.");
                        alert("Request failed.");
                        deferred.reject();
                    });
            } else {
                deferred.reject();
            }

            return deferred.promise();

            function formatErrors(errors) {
                var result = [];

                $.each(errors, function(id, text) {
                    result.push({
                        "id": id,
                        "text": text
                    });
                });

                return result;
            }
        };

        /**
         * @description merges form and dialog data for a save request
         * @return {Array}
         * */
        PostEditPage.prototype.getData = function(options) {
            var that = this,
                result = [];

            // set data from options
            if (options.action) { result.push({ name: options.action, value: 1 }); }
            if (!options.reload) { result.push({ name: "inline", value: 1 }); }
            if (!that.transliterated) { result.push({ name: "transliterate", value: 1 }); }

            // set editor data
            var $editor_text = that.$form.find("#post_text"),
                text = $.wa_blog.editor.wysiwygToHtml( $editor_text.val() );
            $editor_text.val(text);

            // form data
            var data = that.$form.serializeArray();
            if (data.length) {
                result = result.concat(data);
            }

            // dialogs data
            $.each(that.submit_data, function(data_key, data) {
                if (data.length) {
                    result = result.concat(data);
                }
            });

            return result;
        };

        /**
         * @description this function will display errors. To remove errors, set the @param "errors" to null;
         * @param {Array|Null} errors
         * */
        PostEditPage.prototype.renderErrors = function(errors) {
            var that = this;

            var $errors_place = that.$errors_place;

            var error_template = "<div class=\"message error\"><span class=\"message-icon\"><i class=\"fas fa-exclamation-triangle\"></i></span><span class=\"message-text\">%text%</span><span class=\"message-actions\"><span class=\"message-action js-remove-message\"><i class=\"fas fa-times\"></i></span></span></div>";

            // clear
            $errors_place.html("");

            if (errors) {
                $.each(errors, function(i, error) {
                    if (error.text) {
                        var $error = $(error_template.replace("%text%", error.text));

                        $errors_place.append($error);

                        $error.on("click", ".js-remove-message", function(event) {
                            event.preventDefault();
                            $error.remove();
                        });
                    }
                });
            }
        };

        return PostEditPage;

    })($);

    $.wa_blog.init.initPostEditPage = function(options) {
        return new PostEditPage(options);
    };

    /**
     * Get format in what inputed dates must be
     *
     * @returns {String}
     */
    function getDateFormat() {
        return $.datepicker._defaults.dateFormat.toUpperCase().replace('YY', 'YYYY');
    }

    /**
     * @param {Array} $datepicker
     * @description Init datepicker
     */
    function initDatepicker($datepicker) {
        var date_month_count = 2;
        var datepicker_options = {
            changeMonth : true,
            changeYear : true,
            shortYearCutoff: 2,
            showOtherMonths: date_month_count < 2,
            selectOtherMonths: date_month_count < 2,
            stepMonths: date_month_count,
            numberOfMonths: date_month_count,
            showWeek: false,
            gotoCurrent: true,
            constrainInput: false
        };

        $datepicker
            .datepicker(datepicker_options)
            .datepicker('option', 'minDate', new Date());
    }

})(jQuery);
