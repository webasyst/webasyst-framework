class WaHeaderAnnouncement {
    constructor () {
        this.$form = $('#js-form-new-announcement');
        this.$announcement_groups = $('#js-announcement-groups');
        this.url_api = backend_url + 'webasyst/announcements/';
        this.loading_html = '<span class="js-loading"><i class="fas fa-spinner wa-animation-spin"></i></span>';
        this.editing_data = null;
        this.notify_users_dropdown = null;

        this.init();
        this.bindEvents();
    }

    init () {
        this.initRedactor();
        this.initTooltip(this.$announcement_groups);
        this.notify_users_dropdown = this.initNotifyUsersDropdown();
    }

    bindEvents () {
        const that = this;

        $('.js-new-announcement').on('click', function () {
            $('#js-announcement-remove').addClass('hidden');
            if ($(this).hasClass('is-open')) {
                that.hideAndResetForm();
            } else {
                that.hideAndResetForm(function () {
                    that.showForm();
                    $('.js-new-announcement').addClass('is-open');
                })
            }
        });

        // Events form
        $('#js-publish-announcement').on('click', function () {
            if (!that.isValid()) {
                return false;
            }

            const $self = $(this);
            const $loading = $(`<span class="custom-mr-4">${that.loading_html}</span>`).prependTo($self);
            $self.prop('disabled', true);
            $.post(that.url_api + 'create/', that.getFormData(), function (r) {
                if (r.status === 'ok') {
                    that.hideAndResetForm(function () {
                        that.renderAnnouncementItemWithAnimation(r.data);
                    });
                } else if (r.errors) {
                    r.errors.forEach(err => {
                        that.handleError(err);
                    });
                } else {
                    that.handleSaveError(r);
                }
            }).always(() => {
                $loading.remove();
                $self.prop('disabled', false);
            }).fail((r) => {
                that.handleSaveError(r);
            });
            return false;
        });

        $('#js-preview-announcement').on('click', function () {
            if (!that.isValid()) {
                return false;
            }

            const $preview_item = that.$announcement_groups.find('.js-announcement-list').find('.js-announcement-preview-item');
            if (that.editing_data) {
                const $exists_item = that.$announcement_groups.find(`[data-app-id="${that.editing_data.app_id}"][data-id="${that.editing_data.id}"]`);
                $exists_item.toggleClass('js-announcement-preview-item wa-announcement-preview-item', !$preview_item.length);
            }

            const $self = $(this);
            if ($preview_item.length) {
                that.stopPreview();
                if (that.editing_data) {
                    that.renderAnnouncementItem(that.editing_data, false, true);
                }
            } else {
                $self.text($self.data('inactive-text'));
                that.renderAnnouncementItem(that.editing_data, true);
            }

            return false;
        });

        $('#js-announcement-fields-more').on('click', function () {
            $(this).find('svg').toggleClass('fa-caret-down fa-caret-up');
            $('#js-announcement-fields').slideToggle();
        });

        // Events items
        let is_load_editing = false;
        $(document).on('click', '.js-announcement-edit', function () {
            if (is_load_editing) {
                return false;
            }

            const $self = $(this);
            const $item = $self.closest('.js-wa-announcement');
            if ($item.find('.js-is-allowed-edit').val() !== '1' || $item.hasClass('js-announcement-preview-item')) {
                return false;
            }
            if (that.editing_data) {
                that.hideAndResetForm(function () {
                    if (that.editing_data.id != $item.data('id')) {
                        that.editing_data = null;
                        $self.trigger('click');
                    } else {
                        $('#js-cancel-announcement-form').trigger('click');
                    }
                });
                return false;
            }
            is_load_editing = true;

            const $submit = $('#js-update-announcement');
            const revertChangedButton = () => {
                $submit.addClass('green').removeClass('yellow');
            };

            const $loading = $(that.loading_html);
            $self.find('span').addClass('hidden');
            $self.append($loading);
            $.get(that.url_api + `read/?id=${$item.data('id')}`, function (r) {
                $loading.remove();
                $self.find('span').removeClass('hidden');
                if (r.status === 'ok') {
                    $item.append(that.$form.detach());

                    $submit.removeClass('hidden');
                    $('#js-publish-announcement').addClass('hidden');
                    $('#js-cancel-announcement-form').removeClass('hidden');
                    $('#js-announcement-remove').removeClass('hidden');
                    that.showForm();
                    that.setDataForm(r.data);

                    that.$form.one('change.wa_announcement_edit input.wa_announcement_edit', function (e) {
                        $submit.addClass('yellow').removeClass('green');
                        $(this).off('change.wa_announcement_edit input.wa_announcement_edit');
                    });
                    revertChangedButton();
                }
                is_load_editing = false;
            });

            $submit.off('click').on('click', function () {
                if (!that.isValid()) {
                    return false;
                }

                const $loading = $(`<span class="custom-mr-4">${that.loading_html}</span>`).prependTo($(this));
                $submit.prop('disabled', true);
                $.post(
                    that.url_api + 'update/',
                    { id: $item.data('id'), ...that.getFormData() },
                    function (r) {
                        if (r.status === 'ok') {
                            that.hideAndResetForm(function () {
                                that.renderAnnouncementItemWithAnimation(r.data);
                            });
                        } else if (r.errors) {
                            r.errors.forEach(err => {
                                that.handleError(err);
                            });
                        } else {
                            that.handleSaveError(r);
                        }
                    }
                ).always(() => {
                    $loading.remove();
                    $submit.prop('disabled', false);
                }).fail((r) => {
                    that.handleSaveError(r);
                });
            });

            $('#js-cancel-announcement-form').one('click', function () {
                that.hideAndResetForm(function () {
                    // revert original data
                    if (that.editing_data) {
                        that.renderAnnouncementItem(that.editing_data, false, true);
                    }
                });
            });

            return false;
        });
        $('#js-announcement-remove').on('click', function () {
            const $self = $(this);
            const $item = $self.closest('.js-wa-announcement');
            if ($item.find('.js-is-allowed-edit').val() !== '1') {
                return false;
            }

            const id = $item.data('id');
            if (!id || isNaN(parseInt(id))) {
                console.error('error parse id:' + id);
                return;
            }

            $.waDialog.confirm({
                title: $self.data('text-delete'),
                text: '',
                success_button_title: $self.data('button-delete'),
                success_button_class: 'danger custom-mr-4',
                cancel_button_title:$self.data('button-cancel'),
                cancel_button_class: 'light-gray',
                onSuccess: function (d) {
                    const $submit = d.$block.find('.js-success-action');
                    const $loading = $(`<span class="custom-mr-4">${that.loading_html}</span>`);
                    $submit.prop('disabled', true);
                    d.$block.find('.js-success-action').prepend($loading);
                    $.post(that.url_api + 'delete/', { id }, function (r) {
                        $loading.remove();
                        $submit.prop('disabled', false);
                        if (r.status === 'ok') {
                            that.hideAndResetForm(function () {
                                that.toggleGroupCollapseExpandButton($item, true);
                                const $group_items = $item.closest('.js-announcement-group');
                                if ($group_items.find('.js-wa-announcement').length <= 1) {
                                    $group_items.remove();
                                } else {
                                    $item.remove();
                                }
                            });
                        }
                    });
                }
            });

        });

        $(document).on('click', '.js-announcement-toggle-group', function (e) {
            const $self = $(this);
            if ($(e.target).prop("tagName") === 'A') {
                return true;
            }
            const $current_group = $self.closest('.js-announcement-group');
            const class_collapsed = 'is-collapsed';
            const class_expanded = 'is-expanded';

            if ($current_group.hasClass(class_collapsed)) {
                $self.find('.js-announcement-count').addClass('hidden');
                $current_group.addClass(class_expanded).removeClass(class_collapsed);
            }
        });

        $('#js-announcement-show-more').on('click', function () {
            const hidden_groups = that.$announcement_groups.find('.js-announcement-group.hidden:lt(5)');
            hidden_groups.removeClass('hidden');
            if (!that.$announcement_groups.find('.js-announcement-group.hidden').length) {
                $(this).remove();
            }
        });

        $(document).on('click', '.js-announcement-group .js-announcement-close', function () {
            $('#js-announcement-textarea').redactor('core.destroy');
            that.hideAndResetForm(function () {
                that.initRedactor();
            });

            return true;
        });
    }

    initRedactor () {
        $('#js-announcement-textarea').redactor({
            toolbarFixed: false,
            minHeight: 80,
            maxHeight: 100,
            lang: $('#js-announcement-textarea').data('lang'),
            buttons: ['bold', 'italic', 'underline', 'deleted', 'link'],
            focus: true
        });
    }
    initTooltip ($wrapper) {
        $wrapper.find('[data-wa-tooltip-content]').waTooltip();
    }
    initNotifyUsersDropdown () {
        const $dropdown = $('#dropdown-notify-to-users');
        const $toggle_buttons = $('#toggle-groups-or-users');

        const getToggleType = () => {
            return $toggle_buttons.find('.selected').data('type');
        };
        const updateNotifyCounter = (use_init_value = false) => {
            const dropdown = $dropdown.data('dropdown');
            const $counter = $('#js-announcement-notify-counter');
            const contact_ids = [];
            let sum = 0;
            dropdown.$menu.find(':checkbox:checked').each(function () {
                let val = 1;
                const $contact_ids = $(this).closest('[data-contact-ids]');
                if ($contact_ids.length) {
                    val = $contact_ids.data('contact-ids');
                    if (val) {
                        const arr = String(val).split(',');
                        contact_ids.push(...arr);
                    }
                } else {
                    sum += val;
                }
            });

            if (contact_ids.length) {
                sum = Array.from(new Set(contact_ids)).length;
            }

            if (use_init_value) {
                sum = $counter.data('init-value');
            }

            this.$form.find('[name="data[is_notify]"]:checkbox').prop('disabled', !sum);
            $counter.text(sum);
        };
        const unselect = (list_id) => {
            const dropdown = $dropdown.data('dropdown');
            dropdown.$menu.find((list_id ? `[data-list="${list_id}"] ` : '') + ':checkbox').prop('checked', false);
            dropdown.$button.text(dropdown.$button.data('init-text'));
            updateNotifyCounter(true);
        };
        const selectAllUsers = () => {
            $dropdown.data('dropdown').hide();
            $dropdown.find('.toggle [data-type="all"]').trigger('click');
            unselect();
        };
        const updateDropdownTitle = () => {
            const dropdown = $dropdown.data('dropdown');
            const current_toggle_title = $toggle_buttons.data('toggle').$active.text();
            const count = dropdown.$menu.find(`[data-list="${getToggleType()}"] :checkbox:checked`).length;
            dropdown.setTitle(`${current_toggle_title}: ${count}`);
            this.$form.find('#js-announcement-textarea').redactor('placeholder.hide');
        };

        const updateSelectUsersByGroups = () => {
            if (getToggleType() === 'groups') {
                unselect('contacts');

                let contact_ids_str = '';
                $('#notify-to-groups-list :checkbox:checked').each(function () {
                    contact_ids_str += $(this).closest('[data-contact-ids]').data('contact-ids') + ',';
                });
                if (contact_ids_str) {
                    contact_ids_str = contact_ids_str.slice(0, -1);
                    const unique_ids = Array.from(new Set(contact_ids_str.split(',')));

                    let selector_contacts = '';
                    unique_ids.forEach(id => {
                        selector_contacts += `[data-id="${id}"] :checkbox,`;
                    });
                    selector_contacts = selector_contacts.slice(0, -1);

                    $('#notify-to-users-list').find(selector_contacts).prop('checked', true);
                }
            } else {
                // clicked on other toggle type
                unselect('groups');
            }
        };

        $toggle_buttons.waToggle({
            use_animation: false,
            change: function (_, target) {
                $('[data-list]', $dropdown).hide();
                switch ($(target).data('type')) {
                    case 'all':
                        $('[data-list="all"]', $dropdown).show();
                        selectAllUsers();
                        break;
                    case 'groups':
                        $('#notify-to-groups-list', $dropdown).show();
                        break;
                    case 'contacts':
                        $('#notify-to-users-list', $dropdown).show();
                }
            }
        });

        $dropdown.waDropdown({
            hover: false,
            hide: false,
            items: 'ul > li',
            open: function () {
                $('.js-announcement-group.is-expanded:first').children().css('overflow', 'visible');
            },
            close: function () {
                $('.js-announcement-group.is-expanded:first').children().css('overflow', 'hidden');
            },
            change: function (_, target) {
                const $li = $(target);
                $li.removeClass('selected');

                const $checkbox = $li.find(':checkbox');
                const is_checked = $checkbox.prop('checked');

                $checkbox.prop('checked', !is_checked);
                updateSelectUsersByGroups();
                updateNotifyCounter();
                updateDropdownTitle();
            }
        });

        const selectGroupsOrContactsByIds = (type, ids) => {
            $dropdown.find(`[data-type="${type}"]`).trigger('click');
            const dropdown = $dropdown.data('dropdown');
            const $list = dropdown.$menu.find(`[data-list=${type}]`);
            const $li = $list.find('> [data-id]');
            if (!$li.length) {
                return;
            }

            let query_str = '';
            ids.forEach(id => {
                query_str += `[data-id="${id}"],`;
            });
            if (query_str) {
                query_str = query_str.slice(0, -1);
            }

            $li.filter(query_str).find(':checkbox').prop('checked', true);
            updateSelectUsersByGroups();
            updateNotifyCounter();
            updateDropdownTitle();
        };

        return {
            selectAllUsers,
            selectGroupsOrContactsByIds
        }
    }

    isValid () {
        this.resetErrors();

        const bindResetError = ($control) => {
            $control.one('change input keypress', () => this.resetErrors());
        };

        const $textarea = this.$form.find('#js-announcement-textarea');
        if (!$textarea.val().trim()) {
            $textarea.redactor('focus.end');
            bindResetError($textarea.redactor('core.box').addClass('state-error'));
            return false
        }

        if ($('#js-announcement-ttl-date').val()) {
            const $time =  $('#js-announcement-ttl-time');
            if (!$time.val()) {
                $time.addClass('state-error');
                bindResetError($time);
                return false
            }
        }


        return true;
    }
    resetErrors () {
        this.$form.find('.state-error').removeClass('state-error');
        this.$form.find('.state-error-hint').remove();
        $('#js-announcement-error').addClass('hidden');
    }
    handleError (error) {
        let $control = this.$form.find(`[name="${error.field}"]`);
        if (error.field === 'data[text]') {
            $control = $control.redactor('core.box');
        }

        $control.addClass('state-error');
        if (error.error_description) {
            $(`<div class="state-error-hint">${error.error_description}<div>`).insertAfter($control);
        }
    }
    handleSaveError (response) {
        if (typeof response === 'object') {
            console.error(`${response.status}: ${response.responseText || response.statusText}`);
        }
        $('#js-announcement-error').removeClass('hidden');
    }

    setTextarea (text = '') {
        const $textarea = this.$form.find('#js-announcement-textarea');
        $textarea.val(text).redactor('code.set', text);
    }

    getFormData () {
        let form_data = this.$form.serializeArray();
        // prepare data
        form_data = form_data.reduce((acc, { name, value }) => {
            const prop_data = name.match(new RegExp('(?<parent_prop>\\w+)\\[(?<prop>\\S+)\\]'));
            if (isNaN(prop_data.groups.prop)) {
                if (!acc[prop_data.groups.parent_prop]) {
                    acc[prop_data.groups.parent_prop] = {};
                }
                acc[prop_data.groups.parent_prop][prop_data.groups.prop] = value;

            } else if (prop_data.groups.prop) {
                if (!acc[prop_data.groups.parent_prop]) {
                    acc[prop_data.groups.parent_prop] = [];
                }
                acc[prop_data.groups.parent_prop].push(value);

            } else {
                acc[name] = value;
            }

            return acc;
        } , {});

        form_data.data.is_pinned = (this.$form.find('[name="data[is_pinned]"]').is(':checked') ? '1' : '0');

        const ttl_date = $('#js-announcement-ttl-date').val();
        if (ttl_date) {
            form_data.data.ttl_datetime = `${ttl_date} ${$('#js-announcement-ttl-time').val()}:00`;
        }
        if (
            (form_data.group_ids && form_data.group_ids.length) ||
            (!form_data.contact_ids && this.editing_data && this.editing_data.access_contact_ids)
        ) {
            form_data.contact_ids = [null];
        }
        if (!form_data.group_ids && this.editing_data && this.editing_data.access_group_ids) {
            form_data.group_ids = [null];
        }

        return form_data;
    }
    resetForm () {
        this.$form[0].reset();
        this.setTextarea('');
    }

    stopPreview () {
        const with_remove_item = !this.editing_data;
        if (with_remove_item) {
            const $item = this.$announcement_groups.find('.js-announcement-preview-item');
            if (!$item.length) {
                return;
            }
            $item.closest('.js-announcement-preview-group').remove();
            $item.remove();
            this.toggleGroupCollapseExpandButton($item, true);
        }
        const $button = $('#js-preview-announcement');
        $button.text($button.data('active-text'));
    }

    toggleGroupCollapseExpandButton ($item, length_one_less = false) {
        const $group_items = $item.closest('.js-announcement-group');
        const count_items = $group_items.find('.js-wa-announcement').length - (length_one_less ? 1 : 0);

        $group_items.removeClass('is-collapsed');

        if (count_items <= 1) {
            $group_items.removeClass('is-expanded');
        } else {
            $group_items.addClass('is-expanded');
        }
    }

    renderAnnouncementItem (data, without_remove_preview_class = false, use_response_data = false) {
        const $template = $($('#js-announcement-preview-template').html());
        const $announcement_list = this.$announcement_groups.find('.js-announcement-list');

        let $item = null;
        if (data && data.id) {
            $item = $announcement_list.find(`[data-app-id="${data.app_id}"][data-id="${data.id}"]`);
        }
        if (!$item || !$item.length) {
            const $group_items = this.$announcement_groups.find(`[data-app-id="${$template.data('app-id')}"][data-contact-id="${$template.data('contact-id')}"]`);
            if ($group_items.length) {
                $item = $template.find('.js-announcement-list').children();
                $group_items.find('.js-announcement-list').prepend($item);
            } else {
                this.$announcement_groups.prepend($template);
                $item = $template.find('.js-wa-announcement');
            }
        }

        $item.closest('.js-announcement-group').find('.js-announcement-count').remove();
        this.initTooltip($template);

        // fill item
        const $content = $item.find('.js-announcement-content');
        const setContent = (content) => {
            $content.html(content);
        }
        const { text, is_pinned } = data && use_response_data ? data : this.getFormData().data;
        $item.find('.js-announcement-pinned').toggleClass('hidden', is_pinned !== '1');
        text && setContent(text);

        if (data) {
            if (!without_remove_preview_class) {
                $item.closest('.js-announcement-preview-group')
                    .removeClass('js-announcement-preview-group wa-announcement-preview-group');
                $item.removeClass('js-announcement-preview-item wa-announcement-preview-item');
            }
            $item.attr('data-app-id', data.app_id);
            $item.attr('data-id', data.id);
            $item.find('.js-announcement-time').text(data.humandatetime);
        }

        this.toggleGroupCollapseExpandButton($item);

        return $item;
    }
    renderAnnouncementItemWithAnimation (data) {
        const $item = this.renderAnnouncementItem(data);
        $item.addClass('wa-announcement-animate-bg');
        setTimeout(() => $item.removeClass('wa-announcement-animate-bg'), 1000);

        return $item;
    }

    showForm () {
        const dfd = $.Deferred();
        this.$form.slideDown(() => {
            this.$form.addClass('visible');
            this.$form.find('#js-announcement-textarea').redactor('focus.end');

            dfd.resolve();
        });

        return dfd.promise();
    }

    hideForm () {
        this.stopPreview();
        const dfd = $.Deferred();
        this.$form.slideUp(() => {
            this.$form.removeClass('visible');
            $('#js-announcement-fields-more').find('svg').addClass('fa-caret-down').removeClass('fa-caret-up');
            $('#js-announcement-fields').slideUp();
            this.notify_users_dropdown && this.notify_users_dropdown.selectAllUsers();
            dfd.resolve();
        });
        $('.js-new-announcement').removeClass('is-open');

        return dfd.promise();
    }
    setDataForm (response_data) {
        if (!response_data) {
            return;
        }
        this.editing_data = response_data;
        const { text, is_pinned, ttl_datetime, access, access_contact_ids, access_group_ids } = response_data;

        this.setTextarea(text);
        this.$form.find('[name="data[is_pinned]"]').prop('checked', is_pinned === '1');
        if (ttl_datetime) {
            const [, date, time] = ttl_datetime.match(/(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})/);
            this.$form.find('#js-announcement-ttl-date').val(date);
            this.$form.find('#js-announcement-ttl-time').val(time);
        }

        if (access === 'limited') {
            if (access_group_ids.length) {
                this.notify_users_dropdown.selectGroupsOrContactsByIds('groups', access_group_ids);
            }
            if (access_contact_ids.length) {
                this.notify_users_dropdown.selectGroupsOrContactsByIds('contacts', access_contact_ids);
            }
        }
    }
    hideAndResetForm (callback) {
        const _callback = () => {
            if (typeof callback === 'function') {
                callback();
            }
        }
        if (!this.$form.hasClass('visible')) {
            _callback();
            return;
        }

        this.hideForm().then(() => {
            $('.wa-announcement-wrapper').prepend(this.$form.detach());
            $('#js-publish-announcement').removeClass('hidden');
            $('#js-update-announcement').addClass('hidden');
            $('#js-cancel-announcement-form').addClass('hidden');
            _callback();
            this.resetForm();
            this.editing_data = null;
        })
    }
}
