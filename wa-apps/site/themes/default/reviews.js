var ReviewImagesSection = ( function($) {

    ReviewImagesSection = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$file_field = that.$wrapper.find(".js-file-field");
        that.$files_wrapper = that.$wrapper.find(".js-attached-files-section");
        that.$errors_wrapper = that.$wrapper.find(".js-errors-section");

        // CONST
        that.max_post_size = options["max_post_size"];
        that.max_file_size = options["max_file_size"];
        that.max_files = options["max_files"];
        that.templates = options["templates"];
        that.patterns = options["patterns"];
        that.locales = options["locales"];

        // DYNAMIC VARS
        that.post_size = 0;
        that.id_counter = 0;
        that.files_data = {};
        that.images_count = 0;

        // INIT
        that.init();
    };

    ReviewImagesSection.prototype.init = function() {
        var that = this,
            $document = $(document);

        that.$wrapper.data("controller", that);

        that.$file_field.on("change", function() {
            addFiles(this.files);
            that.$file_field.val("");
        });

        that.$wrapper.on("click", ".js-show-textarea", function(event) {
            event.preventDefault();
            $(this).closest(".s-description-wrapper").addClass("is-extended");
        });

        that.$wrapper.on("click", ".js-delete-file", function(event) {
            event.preventDefault();
            var $file = $(this).closest(".s-file-wrapper"),
                file_id = "" + $file.data("file-id");

            if (file_id && that.files_data[file_id]) {
                var file_data = that.files_data[file_id];
                that.post_size -= file_data.file.size;
                delete that.files_data[file_id];
                that.images_count -= 1;
            }

            $file.remove();

            that.renderErrors();
        });

        that.$wrapper.on("keyup change", ".js-textarea", function(event) {
            var $textarea = $(this),
                $file = $textarea.closest(".s-file-wrapper"),
                file_id = "" + $file.data("file-id");

            if (file_id && that.files_data[file_id]) {
                var file = that.files_data[file_id];
                file.desc = $textarea.val();
            }
        });

        var timeout = null,
            is_entered = false;

        $document.on("dragover", dragWatcher);
        function dragWatcher(event) {
            var is_exist = $.contains(document, that.$wrapper[0]);
            if (is_exist) {
                onDrag(event);
            } else {
                $document.off("dragover", dragWatcher);
            }
        }

        $document.on("drop", dropWatcher);
        function dropWatcher(event) {
            var is_exist = $.contains(document, that.$wrapper[0]);
            if (is_exist) {
                onDrop(event)
            } else {
                $document.off("drop", dropWatcher);
            }
        }

        $document.on("reset clear", resetWatcher);
        function resetWatcher(event) {
            var is_exist = $.contains(document, that.$wrapper[0]);
            if (is_exist) {
                that.reset();
            } else {
                $document.off("reset clear", resetWatcher);
            }
        }

        function onDrop(event) {
            event.preventDefault();

            var files = event.originalEvent.dataTransfer.files;

            addFiles(files);
            dropToggle(false);
        }

        function onDrag(event) {
            event.preventDefault();

            if (!timeout)  {
                if (!is_entered) {
                    is_entered = true;
                    dropToggle(true);
                }
            } else {
                clearTimeout(timeout);
            }

            timeout = setTimeout(function () {
                timeout = null;
                is_entered = false;
                dropToggle(false);
            }, 100);
        }

        function dropToggle(show) {
            var active_class = "is-highlighted";

            if (show) {
                that.$wrapper.addClass(active_class);
            } else {
                that.$wrapper.removeClass(active_class);
            }
        }

        function addFiles(files) {
            var errors_types = [],
                errors = [];

            $.each(files, function(i, file) {
                var response = that.addFile(file);
                if (response.error) {
                    var error = response.error;

                    if (errors_types.indexOf(error.type) < 0) {
                        errors_types.push(error.type);
                        errors.push(error);
                    }
                }
            });

            that.renderErrors(errors);
        }
    };

    ReviewImagesSection.prototype.addFile = function(file) {
        var that = this,
            file_size = file.size;

        var image_type = /^image\/(png|jpe?g|gif)$/,
            is_image = (file.type.match(image_type));

        if (!is_image) {
            return {
                error: {
                    text: that.locales["file_type"],
                    type: "file_type"
                }
            };

        } else if (that.images_count >= that.max_files) {
            return {
                error: {
                    text: that.locales["files_limit"],
                    type: "files_limit"
                }
            };

        } else if (file_size >= that.max_file_size) {
            return {
                error: {
                    text: that.locales["file_size"],
                    type: "file_size"
                }
            };

        } else if (that.post_size + file_size >= that.max_file_size) {
            return {
                error: {
                    text: that.locales["post_size"],
                    type: "post_size"
                }
            };

        } else {
            that.post_size += file_size;

            var file_id = that.id_counter,
                file_data = {
                    id: file_id,
                    file: file,
                    desc: ""
                };

            that.files_data[file_id] = file_data;

            that.id_counter++;
            that.images_count += 1;

            render();

            return file_data;
        }

        function render() {
            var $template = $(that.templates["file"]),
                $image = $template.find(".s-image-wrapper");

            $template.attr("data-file-id", file_id);

            getImageUri().then( function(image_uri) {
                $image.css("background-image", "url(" + image_uri + ")");
            });

            that.$files_wrapper.append($template);

            function getImageUri() {
                var deferred = $.Deferred(),
                    reader = new FileReader();

                reader.onload = function(event) {
                    deferred.resolve(event.target.result);
                };

                reader.readAsDataURL(file);

                return deferred.promise();
            }
        }
    };

    ReviewImagesSection.prototype.reset = function() {
        var that = this;

        that.post_size = 0;
        that.id_counter = 0;
        that.files_data = {};

        that.$files_wrapper.html("");
        that.$errors_wrapper.html("");
    };

    ReviewImagesSection.prototype.getSerializedArray = function() {
        var that = this,
            result = [];

        var index = 0;

        $.each(that.files_data, function(file_id, file_data) {
            var file_name = that.patterns["file"].replace("%index%", index),
                desc_name = that.patterns["desc"].replace("%index%", index);

            result.push({
                name: file_name,
                value: file_data.file
            });

            result.push({
                name: desc_name,
                value: file_data.desc
            });

            index++;
        });

        return result;
    };

    ReviewImagesSection.prototype.renderErrors = function(errors) {
        var that = this,
            result = [];

        that.$errors_wrapper.html("");

        if (errors && errors.length) {
            $.each(errors, function(i, error) {
                if (error.text) {
                    var $error = $(that.templates["error"].replace("%text%", error.text));
                    $error.appendTo(that.$errors_wrapper);
                    result.push($error);
                }
            });
        }

        return result;
    };

    return ReviewImagesSection;

})(jQuery);

$(function() {
    /**
     * Hotkey combinations
     * {Object}
     */
    var hotkeys = {
        'alt+enter': {
            ctrl:false, alt:true, shift:false, key:13
        },
        'ctrl+enter': {
            ctrl:true, alt:false, shift:false, key:13
        },
        'ctrl+s': {
            ctrl:true, alt:false, shift:false, key:17
        }
    };

    var form_wrapper = $('#product-review-form'),
        closest_form_wrapper = form_wrapper.closest('.row'),
        form = form_wrapper.find('form'),
        content = $('#page-content .reviews'),
        $submit_button = form.find(".js-submit-button");

    var input_rate = form.find('input[name=rate]');
    if (!input_rate.length) {
        input_rate = $('<input name="rate" type="hidden" value=0>').appendTo(form);
    }
    $('#review-rate').rateWidget({
        onUpdate: function(rate) {
            input_rate.val(rate);
        }
    });

    content.off('click', '.review-reply, .write-review a').on('click', '.review-reply, .write-review a', function() {
        var self = $(this);
        var item = self.parents('li:first');
        var parent_id = parseInt(item.attr('data-id'), 10) || 0;
        prepareAddingForm.call(self, parent_id);
        $('.review').removeClass('in-reply-to');
        item.find('.review:first').addClass('in-reply-to');
        return false;
    });

    var captcha = $('.wa-captcha');
    var provider_list = $('#user-auth-provider li');
    var current_provider = provider_list.filter('.selected').attr('data-provider');
    if (current_provider == 'guest' || !current_provider) {
        captcha.show();
    } else {
        captcha.hide();
    }

    provider_list.find('a').click(function () {
        var self = $(this);
        var li = self.parents('li:first');
        if (li.hasClass('selected')) {
            return false;
        }
        li.siblings('.selected').removeClass('selected');
        li.addClass('selected');

        var provider = li.attr('data-provider');
        form.find('input[name=auth_provider]').val(provider);
        if (provider == 'guest') {
            $('div.provider-fields').hide();
            $('div.provider-fields[data-provider=guest]').show();
            captcha.show();
            return false;
        }
        if (provider == current_provider) {
            $('div.provider-fields').hide();
            $('div.provider-fields[data-provider='+provider+']').show();
            captcha.hide();
            return false;
        }

        var left = (screen.width - 600)/2;
        var top =  (screen.height- 400)/2;
        window.open(
            $(this).attr('href'), "oauth", "width=600,height=400,left="+left+",top="+top+",status=no,toolbar=no,menubar=no"
        );
        return false;
    });

    addHotkeyHandler('textarea', 'ctrl+enter', function(event) {
        form.trigger("submit");
    });

    var is_locked = false;

    form.on("submit", function(event) {
        event.preventDefault();
        if (!is_locked) {
            is_locked = true;

            var $loading = form.find(".review-add-form-status");

            $loading.show();

            $submit_button
                .attr("disabled", true)
                .val( $submit_button.data("active") );

            addReview(form).always( function () {
                is_locked = false;

                $loading.hide();

                $submit_button
                    .removeAttr("disabled")
                    .val( $submit_button.data("inactive") );
            });
        }
    });

    function addReview(form) {
        var href = location.pathname + 'add/',
            form_data = getData(form);

        return $.ajax({
            url: href,
            data: form_data,
            cache: false,
            contentType: false,
            processData: false,
            type: 'POST',
            success: onSuccess,
            error: function(jqXHR, errorText) {
                if (console) {
                    console.error("Error", errorText);
                }
            }
        });

        function getData($form) {
            var fields_data = $form.serializeArray(),
                form_data = new FormData();

            $.each(fields_data, function () {
                var field = $(this)[0];
                form_data.append(field.name, field.value);
            });

            var $image_section = $form.find("#js-review-images-section");
            if ($image_section.length) {
                var controller = $image_section.data("controller"),
                    data = controller.getSerializedArray();

                $.each(data, function(i, file_data) {
                    form_data.append(file_data.name, file_data.value);
                });
            }

            return form_data;
        }

        function onSuccess(r) {
            if (r.status === 'fail') {
                clear(form, false);
                showErrors(form, r.errors);
                return;
            }
            if (r.status !== 'ok' || !r.data.html) {
                if (console) {
                    console.error('Error occured.');
                }
                return;
            }
            var html = r.data.html;
            var parent_id = parseInt(r.data.parent_id, 10) || 0;
            var parent_item = parent_id ? form.parents('li:first') : content;
            var ul = $('ul.reviews-branch:first', parent_item);

            if (parent_id) {
                //reply to a review
                ul.show().append(html);
                ul.find('li:last .review').addClass('new');
            } else {
                //top-level review
                ul.show().prepend(html);
                ul.find('li:first .review').addClass('new');
            }

            $('.reviews-count-text').text(r.data.review_count_str);
            $('.reviews-count').text(r.data.count);
            form.find('input[name=count]').val(r.data.count);
            clear(form, true);
            content.find('.write-review a').click();

            form_wrapper.hide();
            if (typeof success === 'function') {
                success(r);
            }
        }
    }

    function showErrors(form, errors) {
        for (var name in errors) {
            $('[name='+name+']', form).last().addClass('error').parent().append($('<em class="errormsg"></em>').text(errors[name]));
        }
    };

    function clear(form, clear_inputs) {
        clear_inputs = typeof clear_inputs === 'undefined' ? true : clear_inputs;
        $('.errormsg', form).remove();
        $('.error',    form).removeClass('error');
        $('.wa-captcha-refresh', form).click();
        if (clear_inputs) {
            $('input[name=captcha], textarea', form).val('');
            $('input[name=rate]', form).val(0);
            $('input[name=title]', form).val('');
            $('.rate', form).trigger('clear');
        }
    };

    function prepareAddingForm(review_id)
    {
        var self = this; // clicked link
        if (review_id) {
            self.parents('.actions:first').after(form_wrapper);
            $('.rate ', form).trigger('clear').parents('.review-field:first').hide();
        } else {
            self.parents('.write-review').after(form_wrapper);
            form.find('.rate').parents('.review-field:first').show();
        }
        clear(form, false);
        $('input[name=parent_id]', form).val(review_id);
        form_wrapper.show();
        closest_form_wrapper.removeClass('write-review-closed');
        content.find('.write-review').hide();
    };

    function addHotkeyHandler(item_selector, hotkey_name, handler) {
        var hotkey = hotkeys[hotkey_name];
        form.off('keydown', item_selector).on('keydown', item_selector,
            function(e) {
                if (e.keyCode == hotkey.key &&
                    e.altKey  == hotkey.alt &&
                    e.ctrlKey == hotkey.ctrl &&
                    e.shiftKey == hotkey.shift)
                {
                    return handler();
                }
            }
        );
    };
});
