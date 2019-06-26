jQuery.fn.waEditor2 = function () {
    var args = arguments;
    var result;

    this.each(function() {
        var $textarea = $(this);

        // API when editor is already initialized
        if ($textarea.data('redactor')) {
            result = callMethod($textarea, args);
            return;
        }

        var options = getOptions(args[0]);

        // ace
        var ace_editor = initAce($textarea, options);
        updateLastWysiwygCode($textarea.val());

        // redactor
        $textarea.redactor(options);

        // fix for smarty tags in image URLs
        $textarea.redactor('core.editor').find('img[src*="$wa_url"]').each(function () {
            var s = decodeURIComponent($(this).attr('src'));
            $(this).attr('data-src', s);
            $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
        });
        if ($textarea.redactor('core.box')) {
            $textarea.redactor('core.box').css('z-index', 0);
        }
        if ($textarea.redactor('core.toolbar')) {
            $textarea.redactor('core.toolbar').css('z-index', 1);
        }

        // Tab header: switch to code source editor (ace)
        var $wrapper = $textarea.closest('.wa-editor-core-wrapper');

        /* Upload image without switch to WYSIWYG */
        if (options.upload_img_dialog) {
            initAceImageUploader($(options.upload_img_dialog), $wrapper, ace_editor, options);
        }

        // Tab header: switch to HTML (Ace)
        $wrapper.find('.html').click(function () {
            if ($(this).parent().hasClass('selected')) {
                return false;
            }

            if (window.wa_app) {
                localStorage.setItem(wa_app + '/editor', 'html');
            }

            $wrapper.find('.wa-editor-wysiwyg-html-toggle li.selected').removeClass('selected');
            $(this).parent().addClass('selected');
            $textarea.redactor('core.box').hide();
            var p = ace_editor.getCursorPosition();
            $textarea.redactor('core.editor').find("img[data-src!='']").each(function () {
                $(this).attr('src', $(this).attr('data-src'));
                $(this).removeAttr('data-src');
            });
            $textarea.redactor('code.sync');

            // If something is modified in WYSIWYG, set new code to source code editor.
            // Otherwise keep the old code, without WYSIWYG's re-formatting.
            var new_code = $textarea.redactor('code.get');
            if (new_code !== $textarea.data('last_wysiwyg_code')) {
                new_code = $textarea.redactor('clean.onSync',
                    $textarea.redactor('clean.onSet', new_code)
                );
                ace_editor.setValue(new_code);
            } else {
                ace_editor.setValue(getSourceCode(new_code));
            }

            $wrapper.find('.ace').show();
            ace_editor.focus();
            ace_editor.navigateTo(p.row, p.column);
            return false;
        });

        // Tab header: switch to WYSIWYG (redactor)
        $wrapper.find('.wysiwyg').click(function () {
            if ($(this).parent().hasClass('selected')) {
                return false
            }
            // Make sure code does not contain Smarty tags
            var source_val = ace_editor.getValue();
            if (options.smarty_wysiwyg_msg && !isWysiwygAllowed(source_val)) {
                alert(options.smarty_wysiwyg_msg);
                return false;
            }

            // Warn user if code is going to be modified by WYSIWYG layout logic
            if ($.trim(source_val) && options.modification_wysiwyg_msg) {
                var source_wysiwyg_cleaned = $textarea.redactor('clean.onSync',
                    $textarea.redactor('clean.onSet', source_val)
                );

                if (source_wysiwyg_cleaned !== source_val) {
                    if ('string' === typeof options.modification_wysiwyg_msg) {
                        if (!confirm(options.modification_wysiwyg_msg)) {
                            return false;
                        } else {
                            // Only show this dialog once per page
                            options.modification_wysiwyg_msg = null;
                        }
                    } else {
                        // Dialog
                        options.modification_wysiwyg_msg.waDialog({
                            onSubmit: function (d) {
                                // Only show this dialog once per page
                                options.modification_wysiwyg_msg = null;
                                d.trigger('close');
                                $wrapper.find('.wysiwyg').trigger('click');
                                return false;
                            }
                        });
                        return false;
                    }
                }
            }

            // Remember WYSIWIG tab as default when editor is started
            if (window.wa_app) {
                localStorage.setItem(wa_app + '/editor', 'wysiwyg');
            }

            // Load code into WYSIWYG
            $textarea.redactor('code.set', source_val);
            $textarea.redactor('core.editor').find('img[src*="$wa_url"]').each(function () {
                // Replace specific allowed Smarty var in img src
                var s = decodeURIComponent($(this).attr('src'));
                $(this).attr('data-src', s);
                $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
            });
            $textarea.redactor('observe.images');
            $textarea.redactor('observe.links');

            // Update tab selection
            $wrapper.find('.wa-editor-wysiwyg-html-toggle li.selected').removeClass('selected');
            $(this).parent().addClass('selected');

            // Show/hide tab content
            $wrapper.find('.ace').hide();
            $textarea.redactor('core.box').show();

            updateLastWysiwygCode(source_val);
            $textarea.redactor('focus.start');
            return false;
        });

        if (window.wa_app && (options.smarty_wysiwyg_msg && !isWysiwygAllowed($textarea.val())) || (localStorage.getItem(wa_app + '/editor') && localStorage.getItem(wa_app + '/editor') == 'html')) {
            $wrapper.find('.wa-editor-wysiwyg-html-toggle li.selected').removeClass('selected');
            $wrapper.find('.html').parent().addClass('selected');
            $textarea.redactor('core.box').hide();
            $wrapper.find('.ace').show();
            setTimeout(function() {
                // Workaround problem when Ace instance is not editable
                // upon initial load
                ace_editor.blur();
            }, 50);
            if (options['focus']) {
                setTimeout(function() {
                    ace_editor.focus();
                    ace_editor.navigateTo(0, 0);
                }, 100);
            }
        } else {
            $wrapper.find('.ace').hide();
            if (options['focus']) {
                if (!options['iframe']) {
                    $textarea.redactor('focus.start');
                } else {
                    setTimeout(function(){
                        $textarea.redactor('focus.start');
                    }, 100);
                }
            }
        }

        function getSourceCode(def) {
            return $textarea.data('original_code') || def;
        }

        function updateLastWysiwygCode(original_code) {
            if (original_code) {
                $textarea.data('original_code', original_code);
            }
            setTimeout(function() {
                $textarea.data('last_wysiwyg_code', $textarea.redactor('code.get'));
            }, 50);
        }
    });

    return result;

    // When called on textarea with editors already initialized
    function callMethod($textarea, args) {
        var $wrapper = $textarea.closest('.wa-editor-core-wrapper');
        if (typeof args[0] === 'string') {
            switch (args[0]) {
                case 'sync':
                    if ($wrapper.find('.wysiwyg').parent().hasClass('selected')) {
                        $textarea.redactor('code.sync');
                        var new_code = $textarea.redactor('code.get');
                        if (new_code !== $textarea.data('last_wysiwyg_code')) {
                            new_code = $textarea.redactor('clean.onSync',
                                $textarea.redactor('clean.onSet', new_code)
                            );
                            $textarea.val(new_code);
                        }
                    } else {
                        $textarea.val($textarea.data('ace').getValue());
                    }
                    return;
                case 'insert':
                    if ($wrapper.find('.wysiwyg').parent().hasClass('selected')) {
                        $textarea.redactor('insert.html', args[1]);
                    } else {
                        $textarea.data('ace').insert(args[1]);
                    }
                    return;
                case 'get':
                    if ($wrapper.find('.wysiwyg').parent().hasClass('selected')) {
                        return $textarea.redactor('code.get');
                    } else {
                        return $textarea.data('ace').getValue();
                    }
            }
        } else {
            $textarea.redactor('code.set', $textarea.val());
            $textarea.data('ace').setValue($textarea.val());
        }
    }

    // Add defaults to options object
    function getOptions(options) {
        options = $.extend({
            focus: true,
            deniedTags: false,
            minHeight: 300,
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
            buttons: ['format', /*'inline',*/ 'bold', 'italic', 'underline', 'deleted', 'lists',
                /*'outdent', 'indent',*/ 'image', 'video', 'table', 'link', 'alignment',
                'horizontalrule',  'fontcolor', 'fontsize', 'fontfamily'],
            plugins: ['fontcolor', 'fontfamily', 'alignment', 'fontsize', /*'inlinestyle',*/ 'table', 'video'],
            imageUpload: '?module=pages&action=uploadimage&r=2',
            imageUploadFields: '[name="_csrf"]:first',
            callbacks: {}
        }, (options || {}));

        if (options.uploadFields || options.uploadImageFields) {
            options['imageUploadFields'] = options.uploadFields || options.uploadImageFields;
        }

        options.callbacks = $.extend({
            imageUploadError: function(json) {
                console.log('imageUploadError', json);
                alert(json.error);
            },
            keydown: function (e) {
                // ctrl + s
                if ((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    if (options.saveButton) {
                        $(options.saveButton).click();
                    }
                    return false;
                }
                return true;
            },
            sync: function (html) {
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
                if (options.callbacks.syncBefore) {
                    html = options.callbacks.syncBefore(html);
                }
                this.$textarea.val(html);
            },
            syncClean: function (html) {
                // Unescape '->' in smarty tags
                return html.replace(/\{[a-z\$'"_\(!+\-][^\}]*\}/gi, function (match) {
                    return match.replace(/-&gt;/g, '->');
                });
            }
        }, (options.callbacks || {}));

        if (options.saveButton && !options.callbacks.change) {
            options.callbacks.change = function (html) {
                $(options.saveButton).removeClass('green').addClass('yellow');
            };
        }

        return options;
    }

    function initAce($textarea, options) {
        var $wrapper = $textarea.closest('.wa-editor-core-wrapper');
        if (!$wrapper.find('.ace').length) {
            var div = $('<div></div>');
            $textarea.parent().append($('<div class="ace"></div>').append(div));
        } else {
            var div = $textarea.closest('.wa-editor-core-wrapper').find('.ace').children('div');
        }
        var editor = ace.edit(div.get(0));
        editor.commands.removeCommand('find');
        ace.config.set("basePath", wa_url + 'wa-content/js/ace/');
        editor.setTheme("ace/theme/eclipse");
        var session = editor.getSession();
        session.setMode("ace/mode/css");
        session.setMode("ace/mode/javascript");
        session.setMode("ace/mode/smarty");
        editor.$blockScrolling = Infinity;
        session.setUseWrapMode(true);
        editor.renderer.setShowGutter(false);
        editor.setShowPrintMargin(false);
        if (navigator.appVersion.indexOf('Mac') != -1) {
            editor.setFontSize(13);
        } else if (navigator.appVersion.indexOf('Linux') != -1) {
            editor.setFontSize(16);
        } else {
            editor.setFontSize(14);
        }
        $wrapper.find('.ace_editor').css('fontFamily', '');
        $wrapper.find('.ace_editor').css('minHeight', 200);
        if ($textarea.val().length) {
            session.setValue($textarea.val());
        } else {
            session.setValue(' ');
        }
        editor.setOption("minLines", 2);
        editor.setOption("maxLines", 10000);
        editor.setAutoScrollEditorIntoView(true);

        if (options['focus']) {
            editor.focus();
            editor.navigateTo(0, 0);
        }

        editor.commands.addCommands([{
            name: 'waSave',
            bindKey: {win: 'Ctrl-S',  mac: 'Ctrl-S'},
            exec: function(editor) {
                if (options.saveButton) {
                    $(options.saveButton).click();
                }
            }
        }, {
            name: "unfind",
            bindKey: {
                win: "Ctrl-F",
                mac: "Command-F"
            },
            exec: function(editor, line) {
                return false;
            },
            readOnly: true
        }]);
        if (options.callbacks.change) {
            session.on('change', options.callbacks.change);
        }

        $textarea.data('ace', editor);

        $(window).resize(function() {
            editor.resize();
        });

        return editor;
    }

    function getCsrf() {
        var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)"));
        if (matches && matches[1]) {
            return decodeURIComponent(matches[1]);
        }
        return '';
    }

    function isWysiwygAllowed(source) {
        // WYSIWYG Redactor is not available if code contains Smarty tags,
        // other than a simple {$var->method()|escape} declarations
        source = source.replace(/\{\$[a-z_][^\}]*\}/gi, '');
        return !source.match(/\{[a-z\$'"_\(!+\-]/i);
    }

    function initAceImageUploader($dialog_wrapper, $wrapper, ace_editor, options) {

        // move the icon of the dialog for loading the images into the Ace tab
        var $uploader_button = $wrapper.find('.wa-editor-upload-img');
        $uploader_button.removeClass('hidden').appendTo($wrapper.find('.ace'));

        $uploader_button.click(function () {
            if (!$.fn.fileupload) {
                console.log('waEditor2 ERROR: jQuery fileupload plugins is required, but missing.');
                return;
            }

            $dialog_wrapper.waDialog();
            return false;
        });
        $uploader_button.one('click', function () {
            if (!$.fn.fileupload) {
                return;
            }

            // same template as used by WYSIWYG
            var img_template = "\n"+'<figure><img src="%url%"></figure>'+"\n";
            $dialog_wrapper.find('input:file').fileupload({
                dataType: 'json',
                start: function () {
                    $dialog_wrapper.find("div.loading").show();
                    $dialog_wrapper.find("input[type=submit]").attr('disabled', 'disabled');
                },
                done: function (e, data) {
                    if (data.result.error) {
                        $('<div style="text-align: center"><p>' + data.result.error + '</p></div>').waDialog({
                            'buttons': '<input type="submit" value="'+ options.locales.close +'" class="button red" />',
                            'height': '100px',
                            'width': '550px',
                            onSubmit: function (d) {
                                d.trigger('close');
                                return false;
                            }
                        });
                        return false;
                    }
                    var img_html = img_template.replace("%url%", data.result.url);
                    // Add img in textarea
                    ace_editor.insert(img_html);
                    ace_editor.clearSelection();
                    ace_editor.focus();
                },

                stop: function () {
                    $dialog_wrapper.hide();
                    $dialog_wrapper.find("div.loading").hide();
                    $dialog_wrapper.find("input[type=submit]").removeAttr('disabled');
                }
            });
            return false;
        });
    }
};
if (!jQuery.fn.waEditor) {
    jQuery.fn.waEditor = jQuery.fn.waEditor2;
}
