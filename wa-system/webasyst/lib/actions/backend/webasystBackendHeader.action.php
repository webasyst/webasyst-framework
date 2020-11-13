<?php

class webasystBackendHeaderAction extends waViewAction
{
    public function execute()
    {
        if (wa()->getEnv() == 'frontend') {
            throw new waException('Unavailable from the frontend');
        }

        $this->view = wa('webasyst')->getView();
        $user = wa()->getUser();
        $apps = $user->getApps();
        $current_app = wa()->getApp();
        $counts = wa()->getStorage()->read('apps-count');
        $date = _ws(waDateTime::date('l')).', '.trim(str_replace(date('Y'), '', waDateTime::format('humandate')), ' ,/');

        $announcement_model = new waAnnouncementModel();
        $announcements = array();
        if ($current_app != 'webasyst') {
            $data = $announcement_model->getByApps($user->getId(), array_keys($apps), $user['create_datetime']);
            foreach ($data as $row) {
                // show no more than 1 message per application
                if (isset($announcements[$row['app_id']]) && count($announcements[$row['app_id']]) >= 1) {
                    continue;
                }
                $announcements[$row['app_id']][] = $row['text'].' <span class="hint">'.waDateTime::format('humandatetime', $row['datetime']).'</span>';
            }
        }

        /**
         * @event backend_header
         * @return array[string]array $return[%plugin_id%] array of html output
         * @return array[string][string]string $return[%plugin_id%]['header_top'] html output
         * @return array[string][string]string $return[%plugin_id%]['header_bottom'] html output
         */
        $params = array();
        $backend_header = wa()->event(array('webasyst', 'backend_header'), $params);

        $header_top = $header_middle = $header_bottom = array();
        foreach ($backend_header as $app_id => $header) {
            if (is_array($header)) {
                if (!empty($header['header_top'])) {
                    $header_top[] = $header['header_top'];
                }
                if (!empty($header['header_bottom'])) {
                    $header_bottom[] = $header['header_bottom'];
                }
            } elseif (is_string($header)) {
                $header_middle[] = $header;
            }
        }

        $push_params = array(
            'current_app_info' => wa()->getAppInfo(),
        );
        $backend_push = wa()->event(array('webasyst', 'backend_push'), $push_params);
        $include_wa_push = false;

        if (!empty($backend_push) && is_array($backend_push)) {
            foreach ($backend_push as $product_id => $value) {
                if (!empty($value)) {
                    $include_wa_push = true;
                    break;
                }
            }
        }

        $app_settings_model = new waAppSettingsModel();

        $this->view->assign(array(
            'root_url'        => wa()->getRootUrl(),
            'backend_url'     => wa()->getConfig()->getBackendUrl(true),
            'webasyst_id_settings_url' => $this->getWebasystIDSettingsUrl(),
            'company_name'    => htmlspecialchars($app_settings_model->get('webasyst', 'name', 'Webasyst'), ENT_QUOTES, 'utf-8'),
            'company_url'     => $app_settings_model->get('webasyst', 'url', wa()->getRootUrl(true)),
            'date'            => $date,
            'user'            => $user,
            'header_items'    => $this->getHeaderItems(),
            'reuqest_uri'     => waRequest::server('REQUEST_URI'),
            'current_app'     => $current_app,
            'counts'          => $counts,
            'wa_version'      => wa()->getVersion('webasyst'),
            'announcements'   => $announcements,
            'header_top'      => $header_top,
            'header_middle'   => $header_middle,
            'header_bottom'   => $header_bottom,
            'include_wa_push' => $include_wa_push,
            'webasyst_id_auth_banner' => $this->getWebasystIDAuthBanner(),
            'show_connection_banner' => $this->showConnectionBanner(),
            'current_domain' => $this->getCurrentDomain()
        ));

        $this->setTemplate(wa()->getAppPath('templates/actions/backend/BackendHeader.html', 'webasyst'));
    }

    protected function getCurrentDomain()
    {
        $domain = wa()->getConfig()->getDomain();
        return waIdna::dec($domain);
    }

    /**
     * @return array
     * @throws waDbException
     * @throws waException
     */
    protected function getHeaderItems()
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

        $auth = new waWebasystIDWAAuth();
        $auth_url = $auth->getUrl();

        return [
            'url' => $auth_url
        ];
    }
}
