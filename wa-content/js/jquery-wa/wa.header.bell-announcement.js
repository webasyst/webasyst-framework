class BellAnnouncement {
    static is_setted_seen = false;

    constructor () {
        this.$notification_wrapper = $('.js-notification-wrapper,.js-wa-announcement');
        this.$notification_close_selector = '.js-announcement-close';

        this.initToggleByBell();
        this.closeNotification();
    }

    static setSeen () {
        if (BellAnnouncement.is_setted_seen) {
            return;
        }

        $.post(backend_url + '?module=settings&action=save', {
            app_id: 'webasyst',
            name: 'wa_announcement_seen',
            value: 'now()'
        }, (r) => {
            BellAnnouncement.is_setted_seen = (r && r.status === 'ok');
        });
    }

    initToggleByBell () {
        /* Notification Actions */
        const $notifications_bell = $('.js-notifications-bell');
        $notifications_bell.on('click', function (e, params) {
            e.preventDefault();
            params = params || { disable_set_seen: false };

            $(this).toggleClass('wa-animation-bell');
            setTimeout(() => $(this).toggleClass('wa-animation-bell'), 1000);

            if (!params.disable_set_seen && $('#js-show-all-notifications').length) {
                $('#js-show-all-notifications').trigger('click');
                return false;
            }

            const $notifications_wrapper = $(this).next('.js-notification-wrapper');
            $notifications_wrapper.toggle().removeClass('hidden');
            if (!$notifications_wrapper.hasClass('hidden')) {
                const $notifications_dropdown = $('#wa-notifications-dropdown');
                $notifications_dropdown.is(':hidden') && $notifications_dropdown.show();
            }

            if (!params.disable_set_seen && $notifications_wrapper.is(':visible')) {
                BellAnnouncement.setSeen();
            }
        });
    }

    /**
     * @description Close Announcement notification
     */
    closeNotification () {
        const that = this;
        const $wa_notifications_bell = $('.js-notifications-bell');
        const $wa_announcement_counter = $wa_notifications_bell.find('.badge');
        const wa_notifications = that.$notification_wrapper.find('li.js-wa-announcement');
        let counter = wa_notifications.length;

        // TODO: depreacated
        // hidden_alert_ids.forEach(alert => {
        //     wa_notifications.filter(`[data-id="${alert}"]`).remove();
        //     counter--
        // })

        if (counter > 0) {
            $wa_announcement_counter.text(counter);
        }else{
            $wa_announcement_counter.remove();
        }

        $('#wa_announcement,#wa-header-user-area').on('click', that.$notification_close_selector, function (e) {
            e.stopPropagation();
            e.preventDefault()

            let $close = $(this),
                $notification_block = $close.closest('.js-wa-announcement,.js-announcement-group'),
                app_id = $close.data('app-id') || $notification_block.data('app-id'),
                contact_id = $notification_block.data('contact-id');

            if ($notification_block.length) {
                const key = $notification_block.data('key');
                if (key) {
                    $notification_block.closest('.js-announcement-group').remove();
                } else {
                    $notification_block.remove();
                }
                let counter = that.$notification_wrapper.find('li.js-wa-announcement').length;

                if (!$('.js-announcement-group.is-unread-group').length) {
                    $('#js-show-all-notifications').remove();
                }

                if (key && app_id === 'installer') {
                    $.post(`${backend_url}installer/?module=announcement&action=hide`, { key, app_id }, function(response) {
                        if (response === 'ok') {
                            let $system_notification_wrapper = $('.js-wa-announcement-wrap');
                            let system_notification_count = $system_notification_wrapper.find('.js-wa-announcement').length;
                            if (system_notification_count <= 0) {
                                $wa_announcement_counter.text(counter);
                                if (!counter) {
                                    $wa_announcement_counter.remove();
                                }
                                $system_notification_wrapper.closest('.js-wa-announcement').remove();
                            }
                        }
                    });
                } else {
                    const payload = {
                        app_id,
                        name: 'announcement_close',
                        value: 'now()',
                        ...(contact_id ? { contact_id } : {})
                    };
                    $.post(`${backend_url}?module=settings&action=save`, payload, response => {
                        if (response && response.status === 'ok') {
                            if (counter === 0) {
                                $wa_announcement_counter.remove();
                            }else{
                                $wa_announcement_counter.text(counter);
                            }
                        }
                    });
                }

                if (counter) {
                    $wa_announcement_counter.text(counter)
                }else{
                    $wa_announcement_counter.remove();
                }
            } else {
                $wa_announcement_counter.remove();
            }

        });
    }
};
