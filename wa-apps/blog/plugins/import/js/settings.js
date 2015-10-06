$.wa_blog_plugins_import = {
    options: {
        loader: '<i class="b-ajax-status-loading icon16 loading"></i>'
    },
    progress: false,
    form: null,
    ajax_pull: {},
    ajaxInit: function () {
        this.ajaxPurge();
        var self = this;
        self.form = $('#plugin-import-form');
        var selector = $(':input[name="blog_import_transport"]').bind(
            'change.plugins_import',
            function (eventObject) {
                return self.settingsHandler
                    .apply(self, [this, eventObject]);
            });
        self.form.submit(function (eventObject) {
            return self.importHandler.apply(self, [this, eventObject]);
        });
        selector.trigger('change');
        $(window).bind('unload.plugins_import', function () {
            return self.checkProgress();
        });
        $("#wa-app > div.sidebar a, #wa-header a, #plugin-list a").live('click.plugins_import', function () {
            return self.checkProgress();
        });

    },
    ajaxPurge: function () {
        $(window).unbind('.plugins_import');
        $("#wa-app > div.sidebar a, #wa-header a, #plugin-list a").die('.plugins_import');
    },
    settingsTooggle: function (display) {
        var self = this;
        if (display) {
            self.form.find('.js-runtime-settings').show();
            self.form.find(':submit').attr('disabled', false).show();
        } else {
            self.form.find('.js-runtime-settings').hide();
            self.form.find(':submit').attr('disabled', true).hide();
        }
    },
    settingsHandler: function (element) {
        var self = this;
        var selector = $('#wa-blog-import-settings');
        var item = $('#wa-blog-import-runtime-settings');
        item.empty();
        self.settingsTooggle(false);
        var transport = $(element).val();
        if (transport) {
            $(element).after(this.options.loader);
            $('.plugin-import-transport-description:visible').hide();
            $('#plugin-import-transport-' + transport).show();
            $.ajax({
                url: '?plugin=import&action=setup',
                type: 'POST',
                data: {
                    'transport': transport
                },
                success: function (response) {
                    selector.find('.b-ajax-status-loading').remove();
                    var field;

                    if (response.status == 'ok') {
                        for (field in response.data) {
                            item.append(response.data[field]);
                        }
                        self.settingsTooggle(true);
                    } else {
                        self.settingsTooggle(false);
                        for (field in response.errors) {
                            var $error = $('<span class="errormsg"></span>');
                            $error.text(response.errors[field]);
                            item.append($error);
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    selector.find('.b-ajax-status-loading').remove();
                    self.settingsTooggle(false);
                },
                dataType: 'json'
            });
        } else {
            $('.plugin-import-transport-description:visible').hide();
        }
        return false;
    },
    importHandler: function (element) {
        var self = this;
        self.progress = true;
        self.form = $(element);
        var data = self.form.serialize();
        self.form.find(':input').attr('disabled', true);
        self.form.find(':submit').after(this.options.loader);
        self.form.find('.errormsg').text('');
        self.form.find('input.error').removeClass('error').attr('title', null);

        var url = $(element).attr('action');
        $.ajax({
            url: url,
            data: data,
            dataType: 'json',
            type: 'post',
            success: function (response) {
                if (response.error) {
                    self.form.find(':input').attr('disabled', false);
                    self.form.find(':submit').show();
                    self.form.find('.b-ajax-status-loading').remove();
                    self.form.find('.js-progressbar-container').hide();
                    self.progress = false;
                    self.form.find('.errormsg').text(response.error);
                    if (response.errors) {
                        var field, $field, selector, title;
                        for (field in response.errors) {
                            title = response.errors[field];
                            field = field.split(':');
                            selector = ':input[name$="\\[' + field[0].replace(/([\]\[])/g, '\\$1') + '\\]"]';
                            $field = self.form.find(selector);
                            if ($field.length) {
                                if (field[1]) {
                                    $field = $($field[parseInt(field[1])]);
                                }
                                $field.addClass('error').attr('title', title);
                            }
                        }
                    }
                } else {
                    self.form.find('.b-ajax-status-loading').remove();
                    self.form.find(':submit').hide();
                    self.form.find('.progressbar .progressbar-inner').css('width', '0%');

                    self.form.find('.progressbar').attr('title', '0.00%');
                    self.form.find('.progressbar-description').text('0.00%');
                    self.form.find('.js-progressbar-container').show();

                    self.ajax_pull[response.processId] = [];
                    self.ajax_pull[response.processId]
                        .push(setTimeout(
                            function () {
                                $.wa.errorHandler = function (xhr) {
                                    return !((xhr.status >= 500) || (xhr.status == 0));
                                };
                                self.progressHandler(url, response.processId,
                                    response);
                            }, 100));
                    self.ajax_pull[response.processId].push(setTimeout(
                        function () {
                            self.progressHandler(url, response.processId);
                        }, 2000));
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                self.form.find(':input').attr('disabled', false);
                self.form.find(':submit').show();
                self.form.find('.b-ajax-status-loading').remove();
                self.form.find('.js-progressbar-container').hide();
                self.progress = false;
            }
        });
        return false;
    },
    updateHandler: function () {

    },
    checkProgress: function () {
        return !this.progress || confirm($_("If you leave this page now, the import process is likely to be interrupted. Leave the page?"));
    },
    progressHandler: function (url, processId, response) {
        // display progress
        // if not completed do next iteration
        var self = $.wa_blog_plugins_import;
        var timer;

        if (response && response.ready) {
            $.wa.errorHandler = null;
            while (timer = self.ajax_pull[processId].pop()) {
                if (timer) {
                    clearTimeout(timer);
                }
            }
            self.form.find(':input').attr('disabled', false);
            self.form.find(':submit').show();
            self.form.find('.b-ajax-status-loading').remove();
            self.form.find('.progressbar').hide();
            self.form.find('.js-progressbar-container').hide();
            self.progress = false;
            $.ajax({
                url: url,
                data: {
                    'processId': response.processId,
                    'cleanup': 1
                },
                dataType: 'json',
                type: 'post',
                success: function (response) {
                    if (response.blog) {
                        location.href = '?blog=' + response.blog;
                    } else {
                        location.href = '?';
                    }
                }
            });

        } else {
            if (response) {
                var bar = self.form.find('.progressbar .progressbar-inner');
                bar.css('width', response.progress.replace(/,/, '.'));
                bar.parents('.progressbar').attr('title', response.progress);
                self.form.find('.progressbar-description').text(response.progress);
            }
            var ajax_url = url;
            var id = processId;
            self.ajax_pull[id].push(setTimeout(function () {
                $.ajax({
                    url: ajax_url,
                    data: {
                        'processId': id
                    },
                    dataType: 'json',
                    type: 'post',
                    success: function (response) {
                        self.progressHandler(url, response
                            ? response.processId
                            : id, response);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        self.progressHandler(url, id, null);
                    }
                });
            }, 3000));
        }
    }
};

$.wa_blog_plugins_import.ajaxInit();
