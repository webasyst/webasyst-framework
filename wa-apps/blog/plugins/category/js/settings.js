if (!$.wa_blog_plugins_category) {
    $.wa_blog_plugins_category = {
        options: {
            loader: '<i class="b-ajax-status-loading icon16 loading"></i>'
        },
        counter: 0,
        ajaxInit: function () {
            //this.ajaxPurge();
            var self = this;
            var $container = $('#b-plugins-categories');
            $container.find('.b-category-add').live('click.plugins_category', function (eventObject) {
                return self.addHandler.apply(self, [this, eventObject]);
            });
            // inline edit
            $container.find('.b-category-edit').live('click.plugins_category', function (eventObject) {
                return self.editHandler.apply(self, [this, eventObject]);
            });
            $container.find('.b-category-delete').live('click.plugins_category', function (eventObject) {
                return self.deleteHandler.apply(self, [this, eventObject]);
            });
            $container.find('.b-plugins-category-icon').live('change.plugins_category', function (eventObject) {
                return self.iconHandler.apply(self, [this, eventObject]);
            });
            $('#plugin-category-form').live('submit.plugins_category', function (eventObject) {
                return self.submitHandler.apply(self, [this, eventObject]);
            });


            if (!$('#b-plugins-categories tbody tr:visible').length) {
                $("#b-plugins-categories .b-category-add").click();
            }
            self.makeSortable();
        },
        ajaxPurge: function () {
            $("#plugin-category-form").die('.plugins_category');
            var $container = $('#b-plugins-categories');
            $("#b-plugins-categories *").die('.plugins_category');
            $('#b-plugins-categories *').unbind('.plugins_category');
        },
        makeSortable: function () {
            var self = this;

            // sort
            $("#b-plugins-categories").sortable({
                distance: 5,
                helper: 'clone',
                items: 'tbody tr',
                handle: '.sort',
                opacity: 0.75,
                tolerance: 'pointer',
                containment: 'parent',
                stop: function (event, ui) {
                    self.showHint();
                }
            });
        },
        addHandler: function (element, eventObject) {
            var self = this;
            var row = $(element).parents('table').find('tbody tr:last');
            if (row) {
                var counter = ++self.counter;
                row = row.clone().insertAfter(row).show();
                row.find(':text').val('');
                row.find(':checked').attr('checked', false);
                row.find('span').text('');
                row.find(':input').each(function () {
                    $(this).attr('name', $(this).attr('name').replace(/\[\-?\d+\]/, '[-' + counter + ']'));
                });
                row.find('.b-category-edit').click();
            }
            return false;
        },
        editHandler: function (element, eventObject) {
            var row = $(element).parents('tr');
            row.addClass('js-inline-edit');
            row.find('span').hide();
            row.find(':input, .js-input').show();
            this.showHint();
            row.find(':input:visible:first').focus();
            return false;
        },
        deleteHandler: function (element, eventObject) {
            var row = $(element).parents('tr');
            row.hide();
            row.find(':input[name$="\[delete\]"]').val(1);
            this.showHint();
            return false;
        },
        iconHandler: function (element, eventObject) {
            var cell = $(element).parents('td');
            var input = cell.find(':input');
            cell.find('.icon16').attr('class', 'icon16 ' + input.val());
        },
        submitHandler: function (element) {
            var form = $(element);
            var data = form.serialize();
            var self = this;
            form.find(':input').attr('disabled', true);
            form.find(':submit').after(this.options.loader);

            $.wa.errorHandler = function (xhr) {
                return !((xhr.status >= 500) || (xhr.status == 0));
            };

            var url = $(element).attr('action');
            $.ajax({
                url: url,
                data: data,
                type: 'post',
                success: function (response) {
                    $.get('?plugin=category&module=backend&action=sidebar', function (html) {
                        $('#blog-category-sidebar-block').replaceWith(html);

                        var $container = $("#wa-plugins-content");

                        $container.empty();
                        $container.html(response);

                        self.makeSortable();
                    });
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    //TODO
                }
            });
            return false;
        },
        showHint: function () {
            $('#b-plugins-categories').next('div').show();
        }
    };
    $.wa_blog_plugins_category.ajaxInit();
}
