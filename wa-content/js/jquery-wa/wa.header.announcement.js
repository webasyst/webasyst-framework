class WaHeaderAnnouncement {
    constructor () {
        this.$form = $('#js-form-new-announcement');
        this.$announcement_groups = $('#js-announcement-groups');
        this.url_api = backend_url + 'webasyst/announcements/';
        this.loading_html = '<span class="js-loading"><i class="fas fa-spinner wa-animation-spin"></i></span>';
        this.editing_data = null;
        this.notify_users_dropdown = null;
        this.sms_counter_timer_id = null;
        this.count_sms_per_user = null;
        this.onChangeRedactorStack = [];

        this.init();
        this.bindEvents();
    }

    init () {
        this.redactor = this.initRedactor();
        this.initTooltip(this.$announcement_groups);
        this.notify_users_dropdown = this.initNotifyUsersDropdown();
        this.initAIWrite();
        this.initAISpellcheck();
        this.initToggleAIWriteOrSpellcheck();
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
                    that.$form.find('[name="data[is_notify_email]"]').prop('checked', true);
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
                $self.removeAttr('title');
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

        $('input[name="data[is_notify_sms]"]').on('change', function () {
            that.calcSMSPerUser(true);
        });

        $('#wa-header').on('click', '.js-show-hidden-announcements', function() {
            $.get(backend_url+'webasyst/announcements/loadMore/?dashboardtmpl=1&limit=30', function(r) {
                that.$announcement_groups.append(r);
                that.$announcement_groups.find('.js-announcement-toggle-group').click();
            });
        });

        that.onChangeRedactor(() => {
            that.calcSMSPerUser();
        })
    }

    initRedactor () {
        return $('#js-announcement-textarea').redactor({
            toolbarFixed: false,
            minHeight: 80,
            maxHeight: 100,
            lang: $('#js-announcement-textarea').data('lang'),
            buttons: ['bold', 'italic', 'underline', 'deleted', 'link'],
            focus: true,
            linkSize: 1000,
            callbacks: {
                change: () => {
                    this.onChangeRedactorStack.forEach(cb => cb());
                }
            }
        }).data('redactor');
    }
    onChangeRedactor (callback) {
        typeof callback === 'function' && this.onChangeRedactorStack.push(callback);
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

            this.updateSMSTotal();
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

        if ($('#js-announcement-datetime-date').val()) {
            const $time =  $('#js-announcement-datetime-time');
            if (!$time.val()) {
                $time.addClass('state-error');
                bindResetError($time);
                return false
            }
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
    resetErrors (only_message = false) {
        if (!only_message) {
            this.$form.find('.state-error').removeClass('state-error');
            this.$form.find('.state-error-hint:not(#js-announcement-error)').remove();
        }
        $('#js-announcement-error').addClass('hidden');
    }
    handleError (error) {
        if (error?.field) {
            let $control = this.$form.find(`[name="${error.field}"]`);
            if (error.field === 'data[text]') {
                $control = $control.redactor('core.box');
            }
            if (error.field === 'data[datetime]') {
                $control = this.$form.find('#js-announcement-datetime-date,#js-announcement-datetime-time');
            }

            $control.addClass('state-error');
            if (error.error_description) {
                $(`<div class="state-error-hint">${error.error_description}<div>`).insertAfter($control.last());
            }
        } else {
            this.handleSaveError(error?.error_description || error?.error);
        }
    }
    handleSaveError (r) {
        let error_msg = null;
        if (typeof r === 'string') {
            error_msg = r;
        } else if (typeof r === 'object' && r.status) {
            error_msg = `${r.status}: ${r.responseText || r.statusText}`;
        }

        const $error = $('#js-announcement-error');
        if (error_msg) {
            $error.html(error_msg);
        }
        $error.removeClass('hidden');
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
        const dt_date = $('#js-announcement-datetime-date').val();
        if (dt_date) {
            form_data.data.datetime = `${dt_date} ${$('#js-announcement-datetime-time').val()}:00`;
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
            if (data.is_unpublished) {
                $content.addClass('hint');
            }
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
        const { text, is_pinned, datetime, ttl_datetime, access, access_contact_ids, access_group_ids } = response_data;

        this.setTextarea(text);
        this.$form.find('[name="data[is_pinned]"]').prop('checked', is_pinned === '1');
        setDateTime(ttl_datetime, this.$form.find('#js-announcement-ttl-date'), this.$form.find('#js-announcement-ttl-time'));
        setDateTime(datetime, this.$form.find('#js-announcement-datetime-date'), this.$form.find('#js-announcement-datetime-time'));

        if (access === 'limited') {
            if (access_group_ids.length) {
                this.notify_users_dropdown.selectGroupsOrContactsByIds('groups', access_group_ids);
            }
            if (access_contact_ids.length) {
                this.notify_users_dropdown.selectGroupsOrContactsByIds('contacts', access_contact_ids);
            }
        }

        function setDateTime(datetime, $date_field, $time_field) {
            if (!datetime) {
                return;
            }
            const [, date, time] = datetime.match(/(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})/);
            $date_field.val(date);
            $time_field.val(time);
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

    calcSMSPerUser (immediate = false) {
        const that = this;
        const is_notify = that.$form.find('[name="data[is_notify_sms]"]').is(':checked');
        const $counter = that.$form.find('.js-sms-counter-value');
        const $loading = that.$form.find('.js-sms-counter-loading');

        that.$form.find('.js-sms-counter').toggleClass('hidden', !is_notify);

        if (that.sms_counter_timer_id) {
            clearInterval(that.sms_counter_timer_id);
        }

        if (!is_notify) {
            $counter.hide().children().text('0');
            $loading.hide();
            return;
        }

        const calc = (cb) => {
            const $textarea = that.$form.find('#js-announcement-textarea');

            $counter.hide();
            $loading.show();
            $.post(this.url_api + 'countSMS', { text: $textarea.val() }, function (r) {
                $loading.hide();
                if (r.status === 'ok') {
                    that.count_sms_per_user = r.data;
                    that.updateSMSTotal();
                }
                if (typeof cb === 'function') {
                    cb();
                }
            });
        };

        if (immediate) {
            calc();
            return;
        }

        that.sms_counter_timer_id = setTimeout(() => {
            calc(() => that.sms_counter_timer_id = null);
        }, 1000);
    }

    updateSMSTotal () {
        if (typeof this.count_sms_per_user !== 'number') {
            return;
        }

        const is_all_users = !!$('#toggle-groups-or-users').find('[data-type="all"].selected').length;
        const count_users = $(`#notify-to-users-list [data-has-phone="1"]${is_all_users ? '' : ' input:checked'}`).length;
        const total_sms = count_users * this.count_sms_per_user;

        const $counter = this.$form.find('.js-sms-counter-value');
        $counter.show().children().text(total_sms);
    }

    initAIWrite () {
        const $wrapper = $('#js-wa-announcement-ai-write-dropdown');
        const $prompt = $wrapper.find('.js-prompt');
        const $submit = $wrapper.find('.js-submit');
        const $repeat_submit = $wrapper.find('.js-repeat-submit');
        const $error = $wrapper.find('.js-error');
        const redactor = this.redactor;
        const resetData = () => {
            $submit.removeClass(['is-submit', 'is-error', 'is-success']);
            $submit.prop('disabled', true);
            $repeat_submit.hide();
            $error.empty();
        };

        $wrapper.waDropdown({
            hover: false,
            hide: false,
            close: () => {
                resetData();
                $submit.css('width', 'auto');
                $prompt.val('');
            }
        });

        const submit = async (repeat = false) => {
            if (!repeat && $submit.hasClass('is-submit')) {
                $wrapper.data('dropdown').hide();
                return;
            }
            resetData();
            $submit.css('width', $submit.css('width'));
            $submit.addClass('is-submit');
            $submit.animate({ width: '35px' }, 50);

            await new Promise((resolve) => setTimeout(() => resolve(), 1000));
            const text = $prompt.val();
            const emotion = $wrapper.find('.js-emotion').val(); // TODO: soon remove
            $.post(this.url_api + 'ai/write', { text, emotion }, (r) => {
                if (r.errors) {
                    $error.html(r.errors.error_description || r.errors.error);
                    $submit.addClass('is-error');
                } else if (r.data) {
                    const html = r.data;
                    redactor.code.set(html);
                    $submit.addClass('is-success');
                }
            }).always(() => {
                $repeat_submit.show();
                $submit.prop('disabled', false);
            });
        };

        $prompt.on('input', function() {
            const is_empty = !$(this).val().trim();
            $submit.prop('disabled', is_empty);
            $repeat_submit.prop('disabled', is_empty);
        });

        $submit.on('click', (e) => {
            e.preventDefault();
            submit();
        });
        $repeat_submit.on('click', (e) => {
            e.preventDefault();
            submit(true);
        });
    }

    initAISpellcheck () {
        const $submit = $('#js-wa-announcement-ai-spellcheck');

        const submit = () => {
            this.resetErrors(true);
            $submit.prop('disabled', true);
            const $loading = $submit.find('.webasyst-magic-wand-ai').addClass('shimmer');
            const text = this.redactor.code.get();
            $.post(this.url_api + 'ai/spellcheck', { text }, (r) => {
                if (r.errors) {
                    this.handleError(r.errors);
                } else if (r.data) {
                    this.redactor.code.set(r.data);
                }
            }).always(() => {
                $loading.removeClass('shimmer');
                $submit.prop('disabled', false);
            });
        };

        $submit.on('click', (e) => {
            e.preventDefault();
            submit();
        });
    }

    initToggleAIWriteOrSpellcheck () {
        const that = this;
        const updateAvailabilitySubmit = () => {
            const show_ai_write = that.redactor.code.html.replace(/<[^>]*>/g, '').trim().length <= 10;
            $('#js-wa-announcement-ai-write-dropdown .dropdown-toggle').toggle(show_ai_write);
            $('#js-wa-announcement-ai-spellcheck').toggle(!show_ai_write);
        };
        updateAvailabilitySubmit();
        that.onChangeRedactor(updateAvailabilitySubmit);
    }
}

class WaAnnouncementReactions {
    constructor (opts) {
        opts = opts || {}
        this.url_api = backend_url + 'webasyst/announcements/';
        this.user_id = opts.user_id;
        this.highlighting_class = opts.highlighting_class;
        this.reaction_list = opts.reaction_list;
        this.templates = opts.templates || {};

        this.$announcement_groups = $('#js-announcement-groups');
        this.$current_announcement = $();

        this.init();
    }

    init () {
        const that = this;
        const dropdown_add = this.initReactionListDropdown();

        that.$announcement_groups.on('click', '.js-announcement-add-reaction', function () {
            that.$current_announcement = $(this).closest('.js-wa-announcement');
            if (dropdown_add.is_opened) {
                return;
            }

            $(this).before(dropdown_add.$wrapper.detach());
            setTimeout(() => {
                dropdown_add.toggleMenu(true);
                $('body').one('click', function () {
                    dropdown_add.toggleMenu(false)
                });
            });
        });

        that.$announcement_groups.on('click', '.js-announcement-reaction', function () {
            that.$current_announcement = $(this).closest('.js-wa-announcement');

            const emoji = $(this).find('[data-emoji]').data('emoji');
            const users = $(this).data('users') || [];
            that.updateReactionCount(emoji, users);
        });

        dropdown_add.on.change = (e, target) => {
            const $item = $(target);
            const emoji = $item.text();

            const $reaction_list = that.$current_announcement.find('.js-announcement-reaction-list');
            const $reaction = $reaction_list.find(`[data-emoji="${emoji}"]`).parent();
            const users = $reaction.data('users') || [];
            if (!users.includes(that.user_id)) {
                that.updateReactionCount(emoji, users);
            }
        };
    }

    updateReactionCount (emoji, users) {
        const that = this;
        const announcement_id = that.$current_announcement.data('id');

        if (users.includes(that.user_id)) {
            that.removeReaction(announcement_id, emoji).then((r) => that.renderReactions(r));
        } else {
            that.addReaction(announcement_id, emoji).then((r) => that.renderReactions(r));
        }
    }

    renderReactions (reactions) {
        if (!reactions) return;

        const that = this;
        const $reaction_list = that.$current_announcement.find('.js-announcement-reaction-list');

        // remove missing reactions
        $reaction_list.children().each(function () {
            if (!reactions[$(this).find('[data-emoji]').text()]) {
                $(this).remove();
            }
        })

        // update exists or create
        for (const emoji in reactions) {
            const users = reactions[emoji] || [];
            let $r = $reaction_list.find(`[data-emoji="${emoji}"]`).parent();

            if ($r.length) {
                $r.removeClass(that.highlighting_class);
                if (!users.length) {
                    $r.remove();
                } else {
                    const $counter = $r.find('[data-count]');
                    $counter.data('count', users.length).text(users.length);
                    $r.data('users', users);
                    if (users.includes(that.user_id)) {
                        $r.addClass(that.highlighting_class);
                    }
                }
            } else {
                $r = $(that.templates['reaction_item'])
                    .data('users', users);

                $r
                    .find('[data-emoji]')
                    .attr('data-emoji', emoji)
                    .text(emoji);

                $r.find('[data-count]')
                    .data('count', users.length)
                    .text(users.length);

                if (users.includes(that.user_id)) {
                    $r.addClass(that.highlighting_class);
                }
                $reaction_list.append($r);
            }
        }
    }

    initReactionListDropdown () {
        const dropdown_html = `<div class="dropdown">
            <div class="dropdown-body">
                <ul class="menu">
                    ${this.reaction_list.map(e => {
                        return `<li><a href="javascript:void(0)">${e}</a></li>`;
                    }).join('')}
                </ul>
            </div>
        </div>`;

        return $(dropdown_html).waDropdown({
            hover: false,
            items: '.menu > li > a'
        }).data('dropdown');
    }

    // API
    removeReaction (id, emoji) {
        return this.apiRequest ('removeReaction/', id, emoji);
    }

    addReaction (id, emoji) {
        return this.apiRequest ('addReaction/', id, emoji);
    }

    apiRequest (action, id, emoji) {
        const dfd = $.Deferred();

        $.post(this.url_api + action, { id, emoji } , (r) => {
            if (r?.status === 'ok') {
                dfd.resolve(r.data.reactions);
            }
        });

        return dfd.promise();
    }
}

class WaAnnouncementComments {
    constructor () {
        this.url_api = backend_url + 'webasyst/announcements/';
        this.$announcement_groups = $('#js-announcement-groups');
        this.$announcement = $();
        this.$wrapper = $();
        this.$place_for_errors = $();

        $.wa_announcemnt_comments = this.init();
    }

    init () {
        const that = this;

        that.$announcement_groups.on('click', '.js-announcement-toggle-comments', function () {
            const $self = $(this);
            if ($self.find('.js-loading').length) {
                return false;
            }
            if (that.$wrapper.length) {
                that.closeAndDestroyForm();
                return false;
            }

            const $icon = $self.find('.js-icon').hide();
            const $loading = $('<span class="js-loading"><i class="fas fa-spinner fa-spin text-gray"></i></span>').prependTo($self);

            that.$announcement = $(this).closest('.js-wa-announcement');
            const announcement_id = that.$announcement.data('id');
            that.showComments(announcement_id).then(() => {
                $loading.remove();
                $icon.show();
            });
        });

        return that;
    }

    showComments (announcement_id) {
        return this.fetchComments(announcement_id).then(html => {
            const $comments_block = $(html).hide();

            this.$announcement.append($comments_block);
            setTimeout(() => $comments_block.slideDown());

            this.counter().setValue($comments_block.find('[data-id]').length);
        })
    }

    initBlock ({ $wrapper, templates, locales }) {
        const that = this;
        that.$wrapper = $wrapper;
        that.templates = templates || {};
        that.locales = locales || {};

        const $form = $wrapper.find('form');

        // main form
        that.bindEditForm({
            $form,
            onSuccess: (html) => {
                that.$wrapper.find('.js-no-comments').remove();
                that.$wrapper.find('ul.list').append(html);
                that.counter().increment();
            },
            onClose: () => {
                that.closeAndDestroyForm()
            }
        });

        // edit comment
        that.$wrapper.on('click', '.js-announcement-comment-edit', function (e) {
            e.preventDefault();
            const $comment = $(this).closest('[data-id]');
            if ($comment.hasClass('is-editing')) {
                return false;
            }

            $comment.addClass('is-editing');
            const $comment_text = $comment.find('.js-announcement-comment-text').hide();
            const $edit_form = $(that.templates['edit_from']);

            $edit_form.prepend(`<input type="hidden" name="id" value="${$comment.data('id')}">`);
            $edit_form.find('[name=text]').val($comment_text.html());
            $edit_form.insertAfter($comment_text);

            // comment form
            that.bindEditForm({
                $form: $edit_form,
                onSuccess: (new_html) => {
                    $edit_form.remove();
                    $comment.removeClass('is-editing');
                    $comment.replaceWith(new_html);
                },
                onClose: () => {
                    $edit_form.remove();
                    $comment.removeClass('is-editing');
                    $comment_text.show();
                }
            });
        });

        // delete comment
        that.$wrapper.on('click', '.js-announcement-comment-remove', function () {
            const $comment = $(this).closest('[data-id]');

            $.waDialog.confirm({
                title: that.locales['confirm_delete'],
                text: '',
                success_button_title: that.locales['delete'],
                success_button_class: 'danger',
                cancel_button_title: that.locales['cancel'],
                cancel_button_class: 'light-gray',
                onSuccess: () => {
                    that.removeComment($comment.data('id'))
                        .then(() => {
                            $comment.hide(100, () => $comment.remove())
                            that.counter().decrement();
                        });
                }
            })
        });
    }

    bindEditForm ({ $form, onSuccess, onClose }) {
        const that = this;
        that.$place_for_errors = $form.find('.js-place-for-errors');

        that.initRedactor($form);

        // save comment
        $form.on('submit', function (e) {
            e.preventDefault();
            if (!isValid()) {
                return false;
            }

            that.saveComment($(this))
                .then(comment_html => {
                    $form.find('[name=text]').redactor('code.set', '');
                    if (typeof onSuccess === 'function') {
                        onSuccess(comment_html);
                    }
                });
        });

        // close form
        $form.find('.js-close-form').one('click', () => {
            if (typeof onClose === 'function') {
                onClose();
            }
        });

        function isValid () {
            resetErrors();

            const $textarea = $form.find('[name=text]');
            if (!$textarea.val() || !$textarea.val().trim()) {
                $textarea.redactor('core.box').addClass('state-error');
                $textarea.redactor('core.editor').one('input', () => resetErrors());
                return false
            }

            return true;

            function resetErrors () {
                $form.find('.state-error').removeClass('state-error');
                $form.find('.state-error-hint').hide().empty();
            }
        }
    }

    initRedactor ($form) {
        const $textarea = $form.find('[name=text]');
        $textarea.redactor({
            toolbarFixed: false,
            minHeight: 40,
            maxHeight: 100,
            lang: $textarea.data('lang'),
            buttons: ['bold', 'italic', 'underline', 'deleted', 'link'],
            focus: true
        });
        setTimeout(() => $textarea.redactor('focus.end'), 100);
    }

    closeAndDestroyForm ($wrapper) {
        $wrapper = $wrapper || this.$wrapper;
        $wrapper.slideUp();
        setTimeout(() => {
            $wrapper.remove();
            $wrapper.length = 0;
        }, 500);
    }

    counter () {
        const $counter = this.$announcement.find('.js-announcement-toggle-comments .js-count');
        return {
            setValue: (val) => $counter.text(val),
            increment: () => $counter.text(parseInt($counter.text()) + 1),
            decrement: () => $counter.text(Math.max(parseInt($counter.text()), 0) - 1)
        }
    }

    // API
    fetchComments (id) {
        const dfd = $.Deferred();

        $.get(this.url_api + `comments/?id=${id}`, function (r) {
            if (r) {
                dfd.resolve(r);
            }
        }).fail((r) => {
            this.handleSaveError(r);
        });

        return dfd.promise();
    }

    saveComment ($form) {
        const dfd = $.Deferred();

        this.$place_for_errors.hide().empty();
        const $submit = $form.find('[type=submit]');
        const $loading_icon = $submit.find('.js-loading');

        $submit.prop('disabled', true);
        $loading_icon.show();

        $.post(this.url_api + 'saveComment/', $form.serialize(), (r) => {
            if (r) {
                dfd.resolve(r);
            }
        }).always(() => {
            $submit.prop('disabled', false);
            $loading_icon.hide();
        }).fail((r) => {
            this.handleSaveError(r);
        });

        return dfd.promise();
    }

    removeComment (comment_id) {
        const dfd = $.Deferred();

        $.get(this.url_api + `removeComment/?id=${comment_id}`, (r) => {
            if (r?.status === 'ok') {
                dfd.resolve();
            }
        }).fail((r) => {
            this.handleSaveError(r);
        });

        return dfd.promise();
    }

    handleSaveError (r) {
        let error_msg = null;
        if (typeof r === 'string') {
            error_msg = r;
        } else if (typeof r === 'object' && r.status) {
            error_msg = `${r.status}: ${r.responseText || r.statusText}`;
        }

        if (error_msg) {
            this.$place_for_errors.text(error_msg).show();
        }
    }
}
