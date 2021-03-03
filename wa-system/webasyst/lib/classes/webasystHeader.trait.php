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

        $sort = explode(',', $user->getSettings('', 'apps'));

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

        $backend_header_notification = isset($options['backend_header_notification']) && is_array($options['backend_header_notification']) ?
            $options['backend_header_notification'] : [];

        // announcement
        $user = wa()->getUser();
        $announcement_model = new waAnnouncementModel();
        $apps = $user->getApps();
        $data = $announcement_model->getByApps($user->getId(), array_keys($apps), $user['create_datetime']);

        if ($backend_header_notification && isset($apps['installer'])) {
            $virtual_id = intval($announcement_model->select('MAX(id)')->fetchField()) + 1;
            foreach ($backend_header_notification as $notification) {
                $data[] = [
                    'id' => $virtual_id,
                    'app_id' => 'installer',
                    'text' => $notification,
                    'datetime' => date('Y-m-d H:i:s')
                ];
                $virtual_id++;
            }
        }

        $announcements = array();
        $announcements_apps = array();
        foreach ($data as $row) {
            // show no more than 1 message per application
            if ($one_per_app && !empty($announcements_apps[$row['app_id']])) {
                continue;
            }
            $announcements_apps[$row['app_id']] = true;
            $row['app'] = $apps[$row['app_id']];
            $announcements[] = $row;
        }
        return $announcements;
    }
}
