<?php

trait webasystHeaderTrait
{
    /**
     * @return array
     * @throws waException
     */
    public function getHeaderItems()
    {
        $user = wa()->getUser();
        $apps = wa()->getApps(true);
        $right_model = new waContactRightsModel();

        $is_admin = $user->isAdmin();
        if (!$is_admin) {
            $rights = $right_model->getApps(-$user->getId(), 'backend', true, false);
            foreach ($apps as $app_id => $app_info) {
                if (!isset($rights[$app_id])) {
                    unset($apps[$app_id]);
                }
            }
        }

        $header_items = array();
        foreach ($apps as $app_id => $app_info) {
            if ($app_id !== 'webasyst' && !isset($app_info['header_items'])) {
                $header_items[$app_id] = $app_info;
            }
            if (isset($app_info['header_items']) && is_array($app_info['header_items'])) {
                foreach ($app_info['header_items'] as $item_id => $item_info) {
                    // Add version
                    if (empty($item_info['version']) && !empty($app_info['version'])) {
                        $item_info['version'] = $app_info['version'];
                    }

                    // Check rights
                    if (!$is_admin && !empty($item_info['rights'])) {
                        $access_is_allowed = true;
                        $app_rights = $right_model->get($user->getId(),$app_id);
                        $needed_rights = $item_info['rights'];
                        if (is_array($needed_rights)) {
                            foreach ($needed_rights as $_r) {
                                if ((ifempty($app_rights[$_r], 0) < 1)) {
                                    $access_is_allowed = false;
                                    break;
                                }
                            }
                        } else {
                            $access_is_allowed = (ifempty($app_rights[$needed_rights], 0) >= 1);
                        }

                        if (!$access_is_allowed) {
                            continue;
                        }
                    }

                    $item_info['app_id'] = $app_id;
                    $item_id = ($app_id === $item_id) ? $item_id : $app_id.'.'.$item_id;
                    $header_items[$item_id] = $item_info;
                }
            }
        }

        $user_settings = $user->getSettings('', 'apps');
        $user_settings = ifempty($user_settings, '');
        $sort = explode(',', $user_settings);

        // By default, the Settings app, if available
        // for current user, is in fifth place in apps list
        if (!in_array('webasyst.settings', $sort) && array_key_exists('webasyst.settings', $header_items)) {
            array_splice($sort, 4, 0, array('webasyst.settings'));
        }

        $sorted_header_items = array();
        foreach ($sort as $item_id) {
            if (isset($header_items[$item_id])) {
                $sorted_header_items[$item_id] = $header_items[$item_id];
                unset($header_items[$item_id]);
            }
        }

        foreach ($header_items as $item_id => $item) {
            $sorted_header_items[$item_id] = $item;
        }

        return $sorted_header_items;
    }

    /**
     * Get list of announcements
     * @param array $options
     *      - bool $options['one_per_app'] - show no more than 1 message per application. Default is TRUE
     *      - string[] $options['backend_header_notification'] - merge into notifications from backend_header.notification event
     * @return array $announcements
     *      $announcements[<id>] [*] - all fields from wa_announcement table
     *      $announcements[<id>]['app'] - application info
     * @throws waException
     */
    public function getAnnouncements(array $options = [])
    {
        $one_per_app = true;
        if (array_key_exists('one_per_app', $options)) {
            $one_per_app = boolval($options['one_per_app']);
        }
        if ($one_per_app) {
            $options['limit_rows_app'] = ifset($options, 'limit_rows_app', 10);
        }

        $backend_header_notification = [];
        if (!empty($options['backend_header_notification'])) {
            if (is_array($options['backend_header_notification'])) {
                $backend_header_notification = $options['backend_header_notification'];
            } else {
                list(
                    $_,
                    $_,
                    $_,
                    $backend_header_notification,
                    $_
                ) = $this->execBackendHeaderEvent();
            }
        }

        // announcement
        $user = wa()->getUser();
        $announcement_model = new waAnnouncementModel();
        $apps = $user->getApps() + ['webasyst' => wa()->getAppInfo('webasyst') + ['img' => null]];

        if (isset($options['data']) && is_array($options['data'])) {
            $data = $options['data'];
        } else {
            $data = $announcement_model->getByApps($user->getId(), array_keys($apps), $user['create_datetime']);

            $sanitizer = new waHtmlSanitizer();
            foreach($data as &$notification) {
                $notification['text'] = $sanitizer->sanitize($notification['text']);
            }
            unset($notification);

            if ($backend_header_notification && isset($apps['installer'])) {
                $empty_row = $announcement_model->getEmptyRow();
                $virtual_id = intval($announcement_model->select('MAX(id)')->fetchField()) + 1;
                foreach ($backend_header_notification as $notification) {
                    array_unshift($data, [
                        'id' => $virtual_id,
                        'app_id' => 'installer',
                        'text' => $notification,
                        'datetime' => date('Y-m-d H:i:s'),
                        'is_virtual' => true,
                    ] + $empty_row);
                    $virtual_id++;
                }
            }
        }

        $contact_ids = array_keys(array_flip(array_filter(array_map(function($row) {
            return ifempty($row['contact_id']);
        }, $data))));
        if ($contact_ids) {
            $collection = new waContactsCollection('id/'.join(',', $contact_ids));
            $contacts = $collection->getContacts('id,name,photo_url_32');
        }

        $announcements = array();
        foreach ($data as &$row) {

            if (!isset($apps[$row['app_id']])) {
                // app is not available for current user
                continue;
            }

            if (!empty($row['data'])) {
                $row['data'] = json_decode($row['data'], true);
            }
            $key = $row['app_id'];
            $row['app'] = $apps[$row['app_id']];
            if (!empty($row['contact_id']) && !empty($contacts[$row['contact_id']])) {
                $row['contact'] = $contacts[$row['contact_id']];
                $key .= '//'.$row['contact_id'];
            }

            // group messages by application if asked to
            if ($one_per_app) {
                if (empty($announcements[$key])) {
                    $row['rows'] = [];
                    $announcements[$key] = $row;
                }
                if (count($announcements[$key]['rows']) < $options['limit_rows_app']) {
                    unset($row['rows'], $row['app'], $row['contact']);
                    $announcements[$key]['rows'][] = $row;
                }
            } else {
                $announcements[] = $row;
            }

        }
        unset($row);

        return $announcements;
    }

    /**
     * Run the webasyst.backend_header event hook, process and return the results.
     * Helper for ->getAnnouncements(), and also used in webasystBackendHeaderAction.
     * @since 3.0.0
     */
    protected function execBackendHeaderEvent()
    {
        $current_app = wa()->getApp();
        $ui_version = wa()->whichUI($current_app);

        $is_from_template = waConfig::get('is_template');
        waConfig::set('is_template', null);

        /**
         * @event backend_header
         * @param string $current_app
         * @return array[string]array $return[%plugin_id%] array of html output
         * @return array[string][string]string $return[%plugin_id%]['header_top'] html output (will be rendered only in UI v1.3)
         * @return array[string][string]string $return[%plugin_id%]['header_middle'] html output (will be rendered only in UI v1.3)
         * @return array[string][string]string $return[%plugin_id%]['header_bottom'] html output (will be rendered only in UI v1.3)
         * @return array[string][string]string $return[%plugin_id%]['notification'] html output (will be rendered only in UI v2.0, "under the bell")
         */
        $backend_header = wa()->event(array('webasyst', 'backend_header'), ref([
            'current_app' => $current_app,
            'ui_version' => $ui_version
        ]));

        waConfig::set('is_template', $is_from_template);

        $header_top = [];
        $header_middle = [];
        $header_bottom = [];
        $header_notification = [];

        $header_user_area = [
            'main' => [],
            'aux' => [],
        ];

        foreach ($backend_header as $app_id => $header) {
            if (is_array($header)) {

                // header_top place allowed either for 1.3
                //  or 2.0 but if event result returned by installer app (special case)
                if (($ui_version === '1.3' || ($ui_version === '2.0' && $app_id === 'installer')) && !empty($header['header_top'])) {
                    $header_top[] = $header['header_top'];
                }

                if ($ui_version === '1.3' && !empty($header['header_middle'])) {
                    $header_bottom[] = $header['header_middle'];
                }
                if (!empty($header['header_bottom'])) {
                    $header_bottom[] = $header['header_bottom'];
                }

                if ($ui_version === '2.0' && !empty($header['notification'])) {
                    $header_notification[] = $header['notification'];
                }

                // header_user_area allowed for 2.0
                if ($ui_version == '2.0' && !empty($header['user_area'])) {
                    if (isset($header['user_area']['main'])) {
                        $header_user_area['main'][] = $header['user_area']['main'];
                    }
                    if (isset($header['user_area']['aux'])) {
                        $header_user_area['aux'][] = $header['user_area']['aux'];
                    }
                }

            } elseif (is_string($header) && $ui_version === '1.3') {
                $header_middle[] = $header;
            }
        }

        return [
            $header_top,
            $header_middle,
            $header_bottom,
            $header_notification,
            $header_user_area,
        ];
    }

    protected function getWebasystIDSettingsUrl()
    {
        return wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/waid/';
    }

    /**
     * Is installation connected to webasyst ID
     * @return bool
     * @throws waDbException
     * @throws waException
     */
    protected function isConnectedToWebasystID()
    {
        // client (installation) not connected
        $auth = new waWebasystIDWAAuth();
        return $auth->isClientConnected();
    }

    protected function showConnectionBanner()
    {
        $is_connected = $this->isConnectedToWebasystID();
        if ($is_connected) {
            return false;
        }

        $is_closed = wa()->getUser()->getSettings('webasyst', 'webasyst_id_announcement_close');
        if ($is_closed) {
            return false;
        }

        return wa()->getUser()->isAdmin('webasyst');
    }

    protected function getWebasystIDAuthBanner()
    {
        $is_closed = wa()->getUser()->getSettings('webasyst', 'webasyst_id_announcement_close');
        if ($is_closed) {
            return null;
        }

        $is_connected = $this->isConnectedToWebasystID();
        if (!$is_connected) {
            return null;
        }

        // user is bound with webasyst contact id already
        $user = $this->getUser();
        $webasyst_contact_id = $user->getWebasystContactId();
        if ($webasyst_contact_id) {
            return null;
        }

        $auth_url = (new waWebasystIDWAAuth)->getUrl();
        $phone_formatted = (new waContactPhoneField('phone', ''))->format(wa()->getUser()->get('phone', 'default'), 'value');
        return [
            'url' => $auth_url,
            'phone' => $phone_formatted,
        ];
    }

    protected function getCalendarData()
    {
        if (wa()->whichUI() === '1.3' || !wa()->appExists('team') || !wa()->getUser()->getRights('team', 'backend')) {
            return [];
        }

        wa('team');
        $calendars = array_filter(teamCalendar::getCalendars(false), function($c) {
            return !$c['is_limited'] || teamHelper::hasRights('edit_events_in_calendar.'.$c['id']);
        });

        $contact_events_model = new teamWaContactEventsModel();
        $event = $contact_events_model->getEventByContact($this->getUserId());
        $current_status = empty($event) ? [] : current(array_values($event));

        return compact('calendars', 'current_status');
    }

    protected function getTeams()
    {
        $gm = new waGroupModel();
        if (wa()->getUser()->isAdmin()) {
            return $gm->getAll('id');
        }

        $ids = $this->getUser()->getGroups();
        return $gm->getById($ids);
    }
}
