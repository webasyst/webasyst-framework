$(function() {
    if ($.wa_blog.editor) {

        var post_content = $("#" + $.wa_blog.editor.options['content_id']);
        var tab = $('#markdown');
        var post_text_markdown = $('#post_text_markdown');

        function init() {

            function checkEquals() {
                var markdown_text = post_text_markdown.val();
                var html_text = post_content.val();
                var markdown_and_post_differ = !equals(markdown_text, post_content.val());
                var tab_text = tab.text();
                if (markdown_text && html_text && markdown_and_post_differ) {
                    tab.text(tab_text.replace('*', '') + '*');
                } else {
                    tab.text(tab_text.replace('*', ''));
                }
            }

            checkEquals();

            $.extend($.wa_blog.editor.editors, {
                markdown: {
                    editor: null,
                    container: null,
                    inited: false,
                    shown: false,
                    info_container: null,
                    ext_info_container: null,
                    refresh: null,
                    textarea: null,
                    init: function(textarea) {
                        if (!this.inited) {

                            var that = this;

                            function init() {
                                if (!$('#blog-markdown-editor').length) {
                                    that.container = $('<div id="blog-markdown-editor"></div>').appendTo("#post_text_wrapper");
                                    that.container.wrap('<div class="ace"></dvi>');
                                } else {
                                    that.container = $('#blog-markdown-editor').show();
                                }

                                var text = $('<p class="small highlighted" style="display:none;"></p>');
                                text.append($('#markdown_plugin_text_new_version').show());
                                text.append(' <a href="javascript:void(0);"></a>');
                                text.find('a').append($('#markdown_plugin_text_update').show());

                                text.find('a').click(function() {
                                    var updated_markdown_text = toMarkdown(post_content.val().trim());
                                    post_text_markdown.val(updated_markdown_text);
                                    if (confirm($('#markdown_plugin_text_override').text())) {
                                        that.editor.getSession().setValue(updated_markdown_text);
                                        text.hide();
                                        var tab_text = tab.text();
                                        tab.text(tab_text.replace('*', ''));
                                    }
                                });

                                that.info_container = text;

                                that.container.parent().prepend(text);
                                that.editor = ace.edit('blog-markdown-editor');
                                if (wa_url) {
                                    ace.config.set("basePath", wa_url + 'wa-content/js/ace/');
                                }
                                that.editor.setTheme("ace/theme/eclipse");
                                var session = that.editor.getSession();
                                session.setMode("ace/mode/markdown");
                                session.setUseWrapMode(true);
                                that.editor.renderer.setShowGutter(false);
                                that.editor.setShowPrintMargin(false);
                                that.editor.setFontSize(13);
                                $('.ace_editor').css('fontFamily', '');
                                session.setValue($('#post_text_markdown').val());
                                that.editor.focus();
                                that.editor.moveCursorTo(0, 0);
                                that.container.css({
                                    'min-height': 100
                                });

                                that.editor.commands.addCommand({
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

                                // Whenever a change happens inside the ACE editor, update
                                // the size again
                                var $window = $(window);
                                session.on('change', function() {
                                    heightUpdateFunction(that.editor, "blog-markdown-editor");
                                    $window.scroll(); // trigger sticky bottom buttons
                                });
                                setTimeout(function() {
                                    heightUpdateFunction(that.editor, "blog-markdown-editor");
                                }, 50);

                                $window.resize(function() {
                                    that.editor.resize();
                                    heightUpdateFunction(that.editor, "blog-markdown-editor");
                                });
                            }

                            init();

                            that.inited = true;
                            that.refresh = init;
                            that.textarea = textarea;
                        }
                        return true;
                    },
                    update: function(textarea) {},
                    onHide: function() {
                        if (this.getValue()) {
                            post_content.val(markdown.toHTML(this.getValue()));
                            post_text_markdown.val(this.getValue());
                        }
                    },
                    onShow: function() {
                        var markdown_text = post_text_markdown.val();
                        var html_text = post_content.val();
                        var markdown_and_post_differ = !equals(markdown_text, html_text);
                        if (html_text && markdown_text && markdown_and_post_differ) {
                            this.info_container.show();
                        }
                        checkEquals();
                    },
                    hide: function() {
                        if (this.container) {
                            this.container.hide();
                            this.container.parent().hide();
                        }
                        if (this.ext_info_container) {
                            this.ext_info_container.hide();
                        }
                        this.shown = false;
                        this.onHide();
                        $('.b-single-post').removeClass('markdown');
                    },
                    show: function() {

                        var markdown_text = post_text_markdown.val();
                        var html_text = post_content.val();
                        var that = this;
                        if (!markdown_text && html_text) {

                            if (!$('#post-no-markdown-markup-yet').length) {
                                var div = $('<div class="block triple-padded" id="post-no-markdown-markup-yet"><p class="align-center">' + $('#markdown_plugin_text_no_markup_yet').html() +
                                        '<br><br><input type="button" value="'+$('#markdown_plugin_text_generate').text()+'" /></p></div>').appendTo("#post_text_wrapper");
                                div.find('input[type=button]').click(function() {
                                    div.hide();
                                    var updated_markdown_text = toMarkdown(post_content.val().trim());
                                    post_text_markdown.val(updated_markdown_text);
                                    that.refresh();

                                    if (that.container) {
                                        that.container.show();
                                        that.container.parent().show();
                                    }

                                    return false;
                                });
                                this.ext_info_container = div;
                            } else {
                                this.ext_info_container = $('#post-no-markdown-markup-yet');
                            }

                            var sidebarHeight = $('#post-form .sidebar:first .b-edit-options').height();
                            var minHeight = sidebarHeight - 163;
                            var height = this.ext_info_container.height();

                            if (height < minHeight) {
                                this.ext_info_container.height(minHeight);
                            }

                            this.ext_info_container.show();
                            this.container.hide();
                            this.shown = true;
                        } else {
                            if (this.container) {
                                this.container.show();
                                this.container.parent().show();
                                if (this.ext_info_container) {
                                    this.ext_info_container.hide();
                                }
                                this.shown = true;
                            }
                        }
                        $('.b-single-post').addClass('markdown');
                        this.onShow();
                    },
                    isShown: function() {
                        return this.shown;
                    },
                    getValue: function() {
                        if (this.editor) {
                            return this.editor.getValue();
                        } else {
                            return null;
                        }
                    }
                }
            });

            if ($.storage.get('blog/editor') == 'markdown') {
                $.wa_blog.editor.selectEditor('markdown');
            }

        }

        function equals(markdown_text, html_text) {
            markdown_text = markdown_text.trim();
            html_text = html_text.trim();
            var markdown_text_html = markdown.toHTML(markdown_text);
            if (markdown_text_html !== html_text) {
                var html_text_markdown = toMarkdown(html_text);
                return markdown_text === html_text_markdown;
            } else {
                return true;
            }
        }

        init();



        var onSubmit = $.wa_blog.editor.onSubmit;
        if (onSubmit instanceof Function) {
            $.wa_blog.editor.onSubmit = function() {
                var markdown_editor = $.wa_blog.editor.editors.markdown;
                if (markdown_editor.isShown() && markdown_editor.getValue() !== null && markdown_editor.container && !markdown_editor.container.is(':hidden')) {
                    var textarea = $('#' + $.wa_blog.editor.options['content_id']);
                    textarea.val(markdown.toHTML(markdown_editor.getValue()));
                    post_text_markdown.val(markdown_editor.getValue());
                } else {
                    init();
                }
                onSubmit.apply(this, arguments);
            };
        }
    }
});