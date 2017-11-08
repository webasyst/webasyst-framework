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
        var editor = initAce($textarea, options);
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
        $wrapper.find('.html').click(function () {
            if ($(this).parent().hasClass('selected')) {
                return false;
            }
            if ($.storage) {
                $.storage.set(wa_app + '/editor', 'html');
            }
            $wrapper.find('.wa-editor-wysiwyg-html-toggle li.selected').removeClass('selected');
            $(this).parent().addClass('selected');
            $textarea.redactor('core.box').hide();
            var p = editor.getCursorPosition();
            $textarea.redactor('core.editor').find("img[data-src!='']").each(function () {
                $(this).attr('src', $(this).attr('data-src'));
                $(this).removeAttr('data-src');
            });
            $textarea.redactor('code.sync');

            // If something is modified in WYSIWYG, set new code to source code editor.
            // Otherwise keep the old code, without WYSIWYG's re-formatting.
            var new_code = $textarea.redactor('code.get');
            if (new_code !== $textarea.data('last_wysiwyg_code')) {
                editor.setValue(new_code);
            } else {
                editor.setValue(getSourceCode(new_code));
            }

            $wrapper.find('.ace').show();
            editor.focus();
            editor.navigateTo(p.row, p.column);
            return false;
        });

        // Tab header: switch to WYSIWYG (redactor)
        $wrapper.find('.wysiwyg').click(function () {
            if ($(this).parent().hasClass('selected')) {
                return false
            }
            if ($.storage) {
                $.storage.set(wa_app + '/editor', 'wysiwyg');
            }
            $wrapper.find('.wa-editor-wysiwyg-html-toggle li.selected').removeClass('selected');
            $(this).parent().addClass('selected');
            $textarea.redactor('code.set', editor.getValue());
            $textarea.redactor('core.editor').find('img[src*="$wa_url"]').each(function () {
                var s = decodeURIComponent($(this).attr('src'));
                $(this).attr('data-src', s);
                $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
            });
            $textarea.redactor('observe.load');
            $textarea.redactor('focus.start');
            $wrapper.find('.ace').hide();
            $textarea.redactor('core.box').show();
            updateLastWysiwygCode(editor.getValue());
            return false;
        });

        if ($.storage && $.storage.get(wa_app + '/editor') == 'html') {
            $wrapper.find('.wa-editor-wysiwyg-html-toggle li.selected').removeClass('selected');
            $wrapper.find('.html').parent().addClass('selected');
            $textarea.redactor('core.box').hide();
            $wrapper.find('.ace').show();
            if (options['focus']) {
                editor.focus();
                editor.navigateTo(0, 0);
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
            source: false,
            paragraphy: false,
            replaceDivs: false,
            toolbarFixed: true,
            replaceTags: false,
            removeNewlines: false,
            removeComments: false,
            imagePosition: true,
            imageResizable: true,
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
        ace.config.set("basePath", wa_url + 'wa-content/js/ace/');
        editor.setTheme("ace/theme/eclipse");
        var session = editor.getSession();
        session.setMode("ace/mode/css");
        session.setMode("ace/mode/javascript");
        session.setMode("ace/mode/smarty");
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
};
if (!jQuery.fn.waEditor) {
    jQuery.fn.waEditor = jQuery.fn.waEditor2;
}