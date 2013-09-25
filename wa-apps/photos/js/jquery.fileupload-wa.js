/*
 * jQuery File Upload User Interface Plugin 6.0.2
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global window, document, URL, webkitURL, FileReader, jQuery */

(function ($) {
    'use strict';

    // The UI version extends the basic fileupload widget and adds
    // a complete user interface based on the given upload/download
    // templates.
    $.widget('wa.fileupload', $.blueimp.fileupload, {

        options: {
            // By default, files added to the widget are uploaded as soon
            // as the user clicks on the start buttons. To enable automatic
            // uploads, set the following option to true:
            autoUpload: false,
            // The following option limits the number of files that are
            // allowed to be uploaded using this widget:
            maxNumberOfFiles: undefined,
            limitConcurrentUploads: 1,
            // The maximum allowed file size:
            maxFileSize: undefined,
            // The minimum allowed file size:
            minFileSize: 1,
            // The regular expression for allowed file types, matches
            // against either file type or file name:
            acceptFileTypes:  /\.(gif|png|jpg|jpeg)$/i,
            // The regular expression to define for which files a preview
            // image is shown, matched against the file type:
            previewFileTypes: /^image\/(gif|jpeg|png)$/,
            // The maximum file size for preview images:
            previewMaxFileSize: 5000000, // 5MB
            // The maximum width of the preview images:
            previewMaxWidth: 80,
            // The maximum height of the preview images:
            previewMaxHeight: 80,
            // By default, preview images are displayed as canvas elements
            // if supported by the browser. Set the following option to false
            // to always display preview images as img elements:
            previewAsCanvas: true,
            // The expected data type of the upload response, sets the dataType
            // option of the $.ajax upload requests:
            dataType: 'json',

            // The add callback is invoked as soon as files are added to the fileupload
            // widget (via file input selection, drag & drop or add API call).
            // See the basic file upload widget for more information:
            add: function (e, data) {
                if ($("#p-uploader").is(":hidden")) {
                    $.photos.uploadDialog();
                }
                var that = $(this).data('fileupload'),
                    files = data.files;
                that._adjustMaxNumberOfFiles(-files.length);
                data.isAdjusted = true;
                data.files.valid = data.isValidated = that._validate(files);
                data.context = that._renderUpload(files)
                    .appendTo(that._files)
                    .data('data', data);
                // Force reflow:
                that._reflow = that._transition && data.context[0].offsetWidth;
                data.context.addClass('in');
                if ((that.options.autoUpload || data.autoUpload) &&
                        data.isValidated) {
                    data.submit();
                }
                var cnt = that._files.children('li').length;
                $('#p-upload-step2 h1').html($_('Upload photos (%d)').replace('%d', cnt) + ' <span class="hint">' + '</span>');
                $('#p-upload-step1').hide();
                $('#p-upload-step1-buttons').hide();
                $('#upload-album-name-field').hide();
                $('#p-upload-step2').show();
                $('#p-upload-step2-buttons').show();
            },
            // Callback for the start of each file upload request:
            send: function (e, data) {
                if (!data.isValidated) {
                    var that = $(this).data('fileupload');
                    if (!data.isAdjusted) {
                        that._adjustMaxNumberOfFiles(-data.files.length);
                    }
                    if (!that._validate(data.files)) {
                        return false;
                    }
                };
                //data.context.find('.p-upload-onephoto-progress').addClass('current');
                if (data.context && data.dataType &&
                        data.dataType.substr(0, 6) === 'iframe') {
                    // Iframe Transport does not support progress events.
                    // In lack of an indeterminate progress bar, we set
                    // the progress to 100%, showing the full animated bar:
                    data.context.find('.p-upload-onephoto-progress').css(
                        'width',
                        parseInt(100, 10) + '%'
                    );
                }
            },
            // Callback for the submit event of each file upload:
            submit: function(e, data) {
                $(this).data('data', data);
            },
            // Callback for successful uploads:
            done: function (e, data) {
                var that = $(this).data('fileupload'),
                    template,
                    preview;

                var n = data.originalFiles.length;
                if (data.context) {
                    data.context.each(function (index) {
                        var file = (data.result && $.isArray(data.result.files) &&
                                data.result.files[index]) || {error: $_('Empty result')};
                        if (file.error) {
                            that._adjustMaxNumberOfFiles(1);
                        }
                        that._transitionCallback(
                            $(this).removeClass('in'),
                            function (node) {
                                that.filesCount++;
                                template = that._renderDownload([file]);
                                template.replaceAll(node);
                                // Force reflow:
                                that._reflow = that._transition &&
                                    template[0].offsetWidth;
                                template.addClass('in');
                                if (!file.error) {
                                    setTimeout(function () {
                                        template.hide(200);
                                    }, 5000);
                                }
                            }
                        );
                    });
                } else {
                    that.filesCount++;
                    template = that._renderDownload(data.result.files)
                        .appendTo(that._files);
                    // Force reflow:
                    that._reflow = that._transition && template[0].offsetWidth;
                    template.addClass('in');
                    if (!file.error) {
                        setTimeout(function () {
                            template.hide(200);
                        }, 5000);
                    }
                }
            },
            // Callback for failed (abort or error) uploads:
            fail: function (e, data) {
                var that = $(this).data('fileupload'),
                    template;
                that._adjustMaxNumberOfFiles(data.files.length);

                if (data.context) {
                    data.context.each(function (index) {
                        if (data.errorThrown !== 'abort') {
                            var file = data.files[index];
                            file.error = file.error || data.errorThrown ||
                                true;
                            if (typeof data.jqXHR === 'object' && !data.jqXHR) {
                                file.responseText = data.jqXHR.responseText;
                            }
                            that._transitionCallback(
                                $(this).removeClass('in'),
                                function (node) {
                                    template = that._renderDownload([file])
                                        .replaceAll(node);
                                    // Force reflow:
                                    that._reflow = that._transition &&
                                        template[0].offsetWidth;
                                    template.addClass('in');
                                }
                            );
                        } else {
                            that._transitionCallback(
                                $(this).removeClass('in'),
                                function (node) {
                                    node.remove();
                                }
                            );
                        }
                    });
                } else if (data.errorThrown !== 'abort') {
                    that._adjustMaxNumberOfFiles(-data.files.length);
                    data.context = that._renderUpload(data.files)
                        .appendTo(that._files)
                        .data('data', data);
                    // Force reflow:
                    that._reflow = that._transition && data.context[0].offsetWidth;
                    data.context.addClass('in');
                }
            },
            // Callback for upload progress events:
            progress: function (e, data) {
                if (data.context) {

                    data.context.find('.p-upload-onephoto-progress').css(
                        'width',
                        parseInt(data.loaded / data.total * 90, 10) + '%'
                    );
                }
            },
            // Callback for global upload progress events:
            progressall: function (e, data) {
                var $this = $(this);
                $this.find('.fileupload-progressbar').css(
                    'width',
                    parseInt(data.loaded / data.total * 95, 10) + '%'
                );
                $("#p-upload-filescount").html(parseInt(data.loaded / data.total * 95, 10) + '%');
                $this.find('.progress-extended').each(function () {
                        $(this).html(
                            $this.data('fileupload')
                                ._renderExtendedProgress(data)
                        );
                });
            },

            // Callback for uploads start, equivalent to the global ajaxStart event:
            start: function () {
                $(this).data('fileupload').filesCount = 0;
                $("#p-upload-filescount").show().empty();
                $('#p-upload-step2').hide();
                $('#p-upload-step2-buttons').hide();
                $('#p-upload-step3').show();
                $('#p-upload-step3-buttons').show();
                $('#p-upload-step3-buttons .cancel').show();
                $(this).find('.fileupload-progressbar')
                    .css('width', '0%');
                $("#p-upload-step3-buttons .progressbar").show();
                $('#p-upload-step3-buttons .cancel').text($_('Stop upload'));
                $("#upload-error").hide();
            },
            // Callback for uploads stop, equivalent to the global ajaxStop event:
            stop: function (e) {
                var self = $(this);
                $("#p-upload-filescount").html('100%');
                self.find('.fileupload-progressbar').animate({
                    'width': '100%'
                });
                if (self.data('is_error')) {
                    $("#p-upload-step3-buttons .progressbar").hide();
                    $('#p-upload-filescount').hide();
                    $("#upload-error").show();
                }
                // log action
                $.get('?module=backend&action=log&action_to_log=photos_upload');

                if (!self.data('is_error') && !self.data('is_aborted')) {
                    var parent_id = parseInt($('#p-uploader-parent').val(), 10);
                    if (parent_id) {
                        $.photos.dispatch();
                    } else {
                        var album_id = parseInt($("#upload-album-id").val(), 10);
                        if (album_id) {
                            var hash = '#/album/' + album_id + '/';
                            if (location.hash == hash) {
                                $.photos.albumAction(album_id);
                            } else {
                                $.wa.setHash(hash);
                            }
                        } else {
                            if (location.hash.replace(/^[^#]*#\/*/, '') == '') {
                                $.photos.photosAction();
                            } else {
                                $.wa.setHash('#/');
                            }
                        }
                    }
                    $('#p-uploader').trigger('close');
                }
                self.data('is_error', false);
                self.data('is_aborted', false);
                $('#p-upload-step3-buttons .cancel').text($_('Close'));
                $('#p-uploader-parent').val(0);
            },
            // Callback for file deletion:
            destroy: function (e, data) {
                var that = $(this).data('fileupload');
                if (data.url) {
                    $.ajax(data);
                }
                that._adjustMaxNumberOfFiles(1);
                that._transitionCallback(
                    data.context.removeClass('in'),
                    function (node) {
                        node.remove();
                    }
                );
            }
        },

        // Link handler, that allows to download files
        // by drag & drop of the links to the desktop:
        _enableDragToDesktop: function () {
            var link = $(this),
                url = link.prop('href'),
                name = decodeURIComponent(url.split('/').pop())
                    .replace(/:/g, '-'),
                type = 'application/octet-stream';
            link.bind('dragstart', function (e) {
                try {
                    e.originalEvent.dataTransfer.setData(
                        'DownloadURL',
                        [type, name, url].join(':')
                    );
                } catch (err) {}
            });
        },

        _adjustMaxNumberOfFiles: function (operand) {
            if (typeof this.options.maxNumberOfFiles === 'number') {
                this.options.maxNumberOfFiles += operand;
                if (this.options.maxNumberOfFiles < 1) {
                    this._disableFileInputButton();
                } else {
                    this._enableFileInputButton();
                }
            }
        },

        _formatFileSize: function (bytes) {
            if (typeof bytes !== 'number') {
                return '';
            }
            if (bytes >= 1000000000) {
                return (bytes / 1000000000).toFixed(2) + ' GB';
            }
            if (bytes >= 1000000) {
                return (bytes / 1000000).toFixed(2) + ' MB';
            }
            return (bytes / 1000).toFixed(2) + ' KB';
        },

        _formatBitrate: function (bits) {
            if (typeof bits !== 'number') {
                return '';
            }
            if (bits >= 1000000000) {
                return (bits / 1000000000).toFixed(2) + ' Gbit/s';
            }
            if (bits >= 1000000) {
                return (bits / 1000000).toFixed(2) + ' Mbit/s';
            }
            if (bits >= 1000) {
                return (bits / 1000).toFixed(2) + ' kbit/s';
            }
            return bits + ' bit/s';
        },

        _formatTime: function (seconds) {
            var date = new Date(seconds * 1000),
                days = parseInt(seconds / 86400, 10);
            days = days ? days + 'd ' : '';
            return days +
                ('0' + date.getUTCHours()).slice(-2) + ':' +
                ('0' + date.getUTCMinutes()).slice(-2) + ':' +
                ('0' + date.getUTCSeconds()).slice(-2);
        },

        _formatPercentage: function (floatValue) {
            return (floatValue * 100).toFixed(2) + ' %';
        },

        _renderExtendedProgress: function (data) {
            return this._formatBitrate(data.bitrate) + ' | ' +
                this._formatTime(
                    (data.total - data.loaded) * 8 / data.bitrate
                ) + ' | ' +
                this._formatPercentage(
                    data.loaded / data.total
                ) + ' | ' +
                this._formatFileSize(data.loaded) + ' / ' +
                this._formatFileSize(data.total);
        },

        _hasError: function (file) {
            if (file.error) {
                return file.error;
            }
            // The number of added files is subtracted from
            // maxNumberOfFiles before validation, so we check if
            // maxNumberOfFiles is below 0 (instead of below 1):
            if (this.options.maxNumberOfFiles < 0) {
                return 'maxNumberOfFiles';
            }
            // Files are accepted if either the file type or the file name
            // matches against the acceptFileTypes regular expression, as
            // only browsers with support for the File API report the type:
            if (!(this.options.acceptFileTypes.test(file.type) ||
                    this.options.acceptFileTypes.test(file.name))) {
                return $_('Files with extensions *.gif, *.jpg, *.jpeg, *.png are allowed only.');
            }
            if (this.options.maxFileSize &&
                    file.size > this.options.maxFileSize) {
                return 'maxFileSize';
            }
            if (typeof file.size === 'number' &&
                    file.size < this.options.minFileSize) {
                return 'minFileSize';
            }
            return null;
        },

        _validate: function (files) {
            var that = this,
                valid = !!files.length;
            $.each(files, function (index, file) {
                file.error = that._hasError(file);
                if (file.error) {
                    valid = false;
                }
            });
            return valid;
        },

        _renderTemplate: function (func, files) {
            return $(this.options.templateContainer).html(func({
                files: files,
                formatFileSize: this._formatFileSize,
                options: this.options
            })).children();
        },

        _renderUpload: function (files) {
            return this._renderTemplate(this.options.uploadTemplate, files);
        },

        _renderDownload: function (files) {
            var nodes = this._renderTemplate(
                this.options.downloadTemplate,
                files
            );
            // set is_error
            for (var i = 0; i < files.length; i++) {
                if (files[i].error) {
                    $('#fileupload').data('is_error', true);
                    break;
                }
            }
            nodes.find('a').each(this._enableDragToDesktop);
            return nodes;
        },

        _startHandler: function (e) {
            e.preventDefault();
            var button = $(this),
                tmpl = button.closest('.template-upload'),
                data = tmpl.data('data');
            if (data && data.submit && !data.jqXHR && data.submit()) {
                button.prop('disabled', true);
            }
        },

        _cancelHandler: function (e) {
            e.preventDefault();
            var tmpl = $(this).closest('.template-upload'),
                data = tmpl.data('data') || {};
            if (!data.jqXHR) {
                data.errorThrown = 'abort';
                e.data.fileupload._trigger('fail', e, data);
            } else {
                $(e.data.fileupload.element[0]).data('is_aborted', true);
                data.jqXHR.abort();
            }
        },

        _deleteHandler: function (e) {
            e.preventDefault();
            var button = $(this);
            e.data.fileupload._trigger('destroy', e, {
                context: button.closest('.template-download'),
                url: button.attr('data-url'),
                type: button.attr('data-type'),
                dataType: e.data.fileupload.options.dataType
            });
        },

        _transitionCallback: function (node, callback) {
            var that = this;
            if (this._transition && node.hasClass('fade')) {
                node.bind(
                    this._transitionEnd,
                    function (e) {
                        // Make sure we don't respond to other transitions events
                        // in the container element, e.g. from button elements:
                        if (e.target === node[0]) {
                            node.unbind(that._transitionEnd);
                            callback.call(that, node);
                        }
                    }
                );
            } else {
                callback.call(this, node);
            }
        },

        _initTransitionSupport: function () {
            var that = this,
                style = (document.body || document.documentElement).style,
                suffix = '.' + that.options.namespace;
            that._transition = style.transition !== undefined ||
                style.WebkitTransition !== undefined ||
                style.MozTransition !== undefined ||
                style.MsTransition !== undefined ||
                style.OTransition !== undefined;
            if (that._transition) {
                that._transitionEnd = [
                    'TransitionEnd',
                    'webkitTransitionEnd',
                    'transitionend',
                    'oTransitionEnd'
                ].join(suffix + ' ') + suffix;
            }
        },

        _initButtonBarEventHandlers: function () {
            var fileUploadButtonBar = this.element.find('.dialog-buttons'),
                filesList = this._files,
                ns = this.options.namespace,
                that = this;
            fileUploadButtonBar.find('.start')
                .bind('click.' + ns, function (e) {
                    e.preventDefault();
                    if ($("#upload-album-id").val() == 'new') {
                        $.post("?module=album&action=save", that.element.serialize().replace(/album_name=/, 'name='), function (response) {
                            if (response.status == 'ok') {
                                $.photos.onCreateAlbum(response.data);
                                $("#upload-album-id").val(response.data.id);
                                $("#upload-album-name").val('');
                                filesList.find('.start').click();
                            }
                        }, "json");
                        return false;
                    }
                    filesList.find('.start').click();
                });
            fileUploadButtonBar.find('.cancel')
                .bind('click.' + ns, function (e) {
                    e.preventDefault();
                    filesList.find('.cancel').click();
                });
            fileUploadButtonBar.find('.delete')
                .bind('click.' + ns, function (e) {
                    e.preventDefault();
                    filesList.find('.delete input:checked')
                        .siblings('button').click();
                });
            fileUploadButtonBar.find('.toggle')
                .bind('change.' + ns, function (e) {
                    filesList.find('.delete input').prop(
                        'checked',
                        $(this).is(':checked')
                    );
                });
        },

        _destroyButtonBarEventHandlers: function () {
            this.element.find('.fileupload-buttonbar button')
                .unbind('click.' + this.options.namespace);
            this.element.find('.fileupload-buttonbar .toggle')
                .unbind('change.' + this.options.namespace);
        },

        _initEventHandlers: function () {
            $.blueimp.fileupload.prototype._initEventHandlers.call(this);
            var eventData = {fileupload: this};
            this._files
                .delegate(
                    '.start',
                    'click.' + this.options.namespace,
                    eventData,
                    this._startHandler
                )
                .delegate(
                    '.cancel',
                    'click.' + this.options.namespace,
                    eventData,
                    this._cancelHandler
                )
                .delegate(
                    '.delete button',
                    'click.' + this.options.namespace,
                    eventData,
                    this._deleteHandler
                );
            this._initButtonBarEventHandlers();
            this._initTransitionSupport();
        },

        _destroyEventHandlers: function () {
            this._destroyButtonBarEventHandlers();
            this._files
                .undelegate('.start', 'click.' + this.options.namespace)
                .undelegate('.cancel', 'click.' + this.options.namespace)
                .undelegate('.delete button', 'click.' + this.options.namespace);
            $.blueimp.fileupload.prototype._destroyEventHandlers.call(this);
        },

        _enableFileInputButton: function () {
            this.element.find('.fileinput-button input')
                .prop('disabled', false)
                .parent().removeClass('disabled');
        },

        _disableFileInputButton: function () {
            this.element.find('.fileinput-button input')
                .prop('disabled', true)
                .parent().addClass('disabled');
        },

        _initTemplates: function () {
            this.options.templateContainer = document.createElement(
                this._files.prop('nodeName')
            );
            this.options.uploadTemplate = window.tmpl('template-upload');
            this.options.downloadTemplate = window.tmpl('template-download');
        },

        _onDrop: function (e) {
            if ($("#p-upload-step3").is(':visible')) {
                return false;
            }
            var that = e.data.fileupload,
                dataTransfer = e.dataTransfer = e.originalEvent.dataTransfer,
                data = {
                    files: $.each(
                        $.makeArray(dataTransfer && dataTransfer.files),
                        that._normalizeFile
                    )
                };
            if (that._trigger('drop', e, data) === false ||
                that._onAdd(e, data) === false) {
                return false;
            }
            if ($.photos && $.photos.isCurrentPhotoStack()) {
                $('#p-uploader-parent').val($.photos.getPhotoId());
            }
            e.preventDefault();
        },

        _initFiles: function () {
            this._files = this.element.find('.files');
        },

        _create: function () {
            this._initFiles();
            $.blueimp.fileupload.prototype._create.call(this);
            this._initTemplates();
        },

        destroy: function () {
            $.blueimp.fileupload.prototype.destroy.call(this);
        },

        enable: function () {
            $.blueimp.fileupload.prototype.enable.call(this);
            this.element.find('input, button').prop('disabled', false);
            this._enableFileInputButton();
        },

        disable: function () {
            this.element.find('input, button').prop('disabled', true);
            this._disableFileInputButton();
            $.blueimp.fileupload.prototype.disable.call(this);
        }

    });

}(jQuery));
