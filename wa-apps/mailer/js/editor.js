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
                        self.redactor('sync');
                    } else {
                        self.val(self.data('ace').getValue());
                    }
                } else if (options == 'insert') {
                    if (wrapper.find('.wysiwyg').parent().hasClass('selected')) {
                        self.redactor('insertHtml', args[1]);
                    } else {
                        self.data('ace').insert(args[1]);
                    }
                } else if (options == 'get') {
                    if (wrapper.find('.wysiwyg').parent().hasClass('selected')) {
                        result = self.redactor('get');
                    } else {
                        result = self.data('ace').getValue();
                    }
                }
            } else {
                self.redactor('set', self.val());
                self.data('ace').setValue(self.val());
            }
            return;
        }
        var container = self.parent();
        var button = options.saveButton || null;
        var syncCallback = options.syncBeforeCallback;
        options = $.extend({
            //boldTag: 'b',
            //italicTag: 'i',
            deniedTags: false,
            minHeight: 300,
            buttonSource: false,
            paragraphy: false,
            convertDivs: false,
            toolbarFixedBox: true,
            buttons: ['html', 'formatting', 'bold', 'italic', 'underline', 'deleted', 'unorderedlist', 'orderedlist',
                'outdent', 'indent', 'image', 'video', 'file', 'table', 'link', 'alignment', '|',
                'horizontalrule'],
            plugins: ['fontcolor', 'fontsize', 'fontfamily'],
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
            syncBeforeCallback: function(html) {
                html = html.replace(/{[a-z$][^}]*}/gi, function (match, offset, full) {
                    match = match.replace(/&lt;/g, '<');
                    match = match.replace(/&gt;/g, '>');
                    match = match.replace(/&amp;/g, '&');
                    match = match.replace(/&quot;/g, '"');

                    match = match.replace(/(empty-cells:\s?show;\s*)?outline:\s?rgba\(0,\s?0,\s?0,\s?0\.6\)\sdashed\s1px;?/gi, '');
                    match = match.replace(/style="\s*"/gi, '');

                    return match;
                });
                return syncCallback ? syncCallback(html) : html;
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

        editor.focus();
        editor.navigateTo(0, 0);

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
        self.redactor('getEditor').find('img[src*="$wa_url"]').each(function () {
            var s = decodeURIComponent($(this).attr('src'));
            $(this).attr('data-src', s);
            $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
        });
        if (self.redactor('getBox')) {
            self.redactor('getBox').css('z-index', 0);
        }
        if (self.redactor('getToolbar')) {
            self.redactor('getToolbar').css('z-index', 1);
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
            wrapper.find('.redactor_box').hide();
            var p = editor.getCursorPosition();
            self.redactor('getEditor').find("img[data-src!='']").each(function () {
                $(this).attr('src', $(this).attr('data-src'));
                $(this).removeAttr('data-src');
            });
            self.redactor('sync');
            editor.setValue(self.redactor('getObject').cleanHtml(self.redactor('get')));
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
            self.redactor('set', editor.getValue());
            self.redactor('getEditor').find('img[src*="$wa_url"]').each(function () {
                var s = decodeURIComponent($(this).attr('src'));
                $(this).attr('data-src', s);
                $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
            });
            self.redactor('observeStart');
            self.redactor('focus');
            wrapper.find('.ace').hide();
            wrapper.find('.redactor_box').show();
            return false;
        });

        if ($.storage && $.storage.get(wa_app + '/editor') == 'html') {
            wrapper.find('.wa-editor-wysiwyg-html-toggle li.selected').removeClass('selected');
            wrapper.find('.html').parent().addClass('selected');
            wrapper.find('.redactor_box').hide();
            wrapper.find('.ace').show();
            editor.focus();
            editor.navigateTo(0, 0);
        } else {
            wrapper.find('.ace').hide();
            if (!options['iframe']) {
                self.redactor('focus');
            }
            else {
                setTimeout(function(){
                    self.redactor('focus');
                }, 100);
            }
        }
    });
    return result;
}
