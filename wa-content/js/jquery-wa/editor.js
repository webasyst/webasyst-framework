jQuery.fn.waEditor = function (options) {
    var args = arguments;
    var result;
    this.each(function() {
        var self = $(this);
        var wrapper = self.closest('.wa-editor-core-wrapper');
        if (self.data('redactor')) {
            if (typeof options === 'string') {
                if (options == 'sync') {
                    if (wrapper.find('.wysiwyg').parent().hasClass('selected')) {
                        self.redactor('code.sync');
                    } else {
                        self.val(self.data('ace').getValue());
                    }
                } else if (options == 'insert') {
                    if (wrapper.find('.wysiwyg').parent().hasClass('selected')) {
                        self.redactor('insert.html', args[1]);
                    } else {
                        self.data('ace').insert(args[1]);
                    }
                } else if (options == 'get') {
                    if (wrapper.find('.wysiwyg').parent().hasClass('selected')) {
                        result = self.redactor('code.get');
                    } else {
                        result = self.data('ace').getValue();
                    }
                }
            } else {
                self.redactor('code.set', self.val());
                self.data('ace').setValue(self.val());
            }
            return;
        }
        var container = self.parent();
        var button = options.saveButton || null;
        var syncCallback = options.syncBeforeCallback;
        if (options.uploadFields) {
            options['uploadImageFields'] = options.uploadFields;
        }
        options = $.extend({
            editorOnLoadFocus: true,
            deniedTags: false,
            minHeight: 300,
            buttonSource: false,
            paragraphy: false,
            replaceDivs: false,
            toolbarFixed: true,
            buttons: ['html', 'formatting', 'bold', 'italic', 'underline', 'deleted', 'unorderedlist', 'orderedlist',
                'outdent', 'indent', 'image', 'video', 'table', 'link', 'alignment', '|',
                'horizontalrule'],
            plugins: ['fontcolor', 'fontsize', 'fontfamily', 'table', 'video'],
            imageUpload: '?module=pages&action=uploadimage&filelink=1',
            imageUploadErrorCallback: function(json) {
                alert(json.error);
            },
            keydownCallback: function (e) {
                // ctrl + s
                if ((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    if (button) {
                        $(button).click();
                    }
                    return false;
                }
                return true;
            },
            syncCallback: function (html) {
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
                if (syncCallback) {
                    html = syncCallback(html);
                }
                this.$textarea.val(html);
            }
        }, (options || {}));
        if (button) {
            options['changeCallback'] = function (html) {
                $(button).removeClass('green').addClass('yellow');
            }
        }
        // ace
        if (!wrapper.find('.ace').length) {
            var div = $('<div></div>');
            container.append($('<div class="ace"></div>').append(div));
        } else {
            var div = self.closest('.wa-editor-core-wrapper').find('.ace').children('div');
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
        wrapper.find('.ace_editor').css('fontFamily', '');
        wrapper.find('.ace_editor').css('minHeight', 200);
        if (self.val().length) {
            session.setValue(self.val());
        } else {
            session.setValue(' ');
        }
        editor.setOption("minLines", 2);
        editor.setOption("maxLines", 10000);
        editor.setAutoScrollEditorIntoView(true);
        
        if (options['editorOnLoadFocus'])
        {
          editor.focus();
          editor.navigateTo(0, 0);
        }

        editor.commands.addCommands([{
            name: 'waSave',
            bindKey: {win: 'Ctrl-S',  mac: 'Ctrl-S'},
            exec: function(editor) {
                if (button) {
                    $(button).click();
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
        if (options['changeCallback']) {
            session.on('change', options['changeCallback']);
        }

        self.data('ace', editor);

        $(window).resize(function() {
            editor.resize();
        });

        // redactorjs
        self.redactor(options);
        self.redactor('core.getEditor').find('img[src*="$wa_url"]').each(function () {
            var s = decodeURIComponent($(this).attr('src'));
            $(this).attr('data-src', s);
            $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
        });
        if (self.redactor('core.getBox')) {
            self.redactor('core.getBox').css('z-index', 0);
        }
        if (self.redactor('core.getToolbar')) {
            self.redactor('core.getToolbar').css('z-index', 1);
        }


        wrapper.find('.html').click(function () {
            if ($(this).parent().hasClass('selected')) {
                return false;
            }
            if ($.storage) {
                $.storage.set(wa_app + '/editor', 'html');
            }
            wrapper.find('.wa-editor-wysiwyg-html-toggle li.selected').removeClass('selected');
            $(this).parent().addClass('selected');
            self.redactor('core.getBox').hide();
            var p = editor.getCursorPosition();
            self.redactor('core.getEditor').find("img[data-src!='']").each(function () {
                $(this).attr('src', $(this).attr('data-src'));
                $(this).removeAttr('data-src');
            });
            self.redactor('code.sync');
            editor.setValue(self.redactor('code.get'));
            wrapper.find('.ace').show();
            editor.focus();
            editor.navigateTo(p.row, p.column);
            return false;
        });

        wrapper.find('.wysiwyg').click(function () {
            if ($(this).parent().hasClass('selected')) {
                return false
            }
            if ($.storage) {
                $.storage.set(wa_app + '/editor', 'wysiwyg');
            }
            wrapper.find('.wa-editor-wysiwyg-html-toggle li.selected').removeClass('selected');
            $(this).parent().addClass('selected');
            self.redactor('code.set', editor.getValue());
            self.redactor('core.getEditor').find('img[src*="$wa_url"]').each(function () {
                var s = decodeURIComponent($(this).attr('src'));
                $(this).attr('data-src', s);
                $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
            });
            self.redactor('observe.load');
            self.redactor('focus.setStart');
            wrapper.find('.ace').hide();
            self.redactor('core.getBox').show();
            return false;
        });

        if ($.storage && $.storage.get(wa_app + '/editor') == 'html') {
            wrapper.find('.wa-editor-wysiwyg-html-toggle li.selected').removeClass('selected');
            wrapper.find('.html').parent().addClass('selected');
            self.redactor('core.getBox').hide();
            wrapper.find('.ace').show();
            if (options['editorOnLoadFocus']) {
                editor.focus();
                editor.navigateTo(0, 0);
            }
        } else {
            wrapper.find('.ace').hide();
            if (options['editorOnLoadFocus']) {
                if (!options['iframe']) {
                    self.redactor('focus.setStart');
                }
                else {
                    setTimeout(function(){
                        self.redactor('focus.setStart');
                    }, 100);
                }
            }
        }
    });
    return result;
}
