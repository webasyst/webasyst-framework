<?php

class webasystBackendHeaderAction extends waViewAction
{
    use webasystHeaderTrait;

    protected $params = [];
    protected $single_app_mode = false;

    /**
     * webasystBackendHeaderAction constructor.
     * @param array $params
     *      array  $params['custom']            some custom data for injecting into the webasyst main header
     *      string $params['custom']['main']    html content that will be shown in the main section instead of an app list
     *      string $params['custom']['aux']     html content that will be shown inside user area
     */
    public function __construct($params = null)
    {
        $params = is_array($params) ? $params : [];
        $params['custom'] = isset($params['custom']) && is_array($params['custom']) ? $params['custom'] : [];
        $params['custom'] = waUtils::extractValuesByKeys($params['custom'], ['main', 'aux'], false, '');
        $params['custom'] = waUtils::toStrArray($params['custom']);

        $single_app_mode_app_id = wa()->isSingleAppMode();
        if ($single_app_mode_app_id) {
            if ($single_app_mode_app_id !== wa()->getApp()) {
                return $this->jsRedirect(wa()->getAppUrl($single_app_mode_app_id));
            }
            $this->single_app_mode = true;
        }

        parent::__construct($params);
    }

    protected function jsRedirect($url)
    {
        echo '<script>window.location = '.json_encode($url).';</script>';
        exit;
    }

    public function execute()
    {
        if (wa()->getEnv() == 'frontend') {
            throw new waException('Unavailable from the frontend');
        }

        $this->view = wa('webasyst')->getView();
        $user = wa()->getUser();
        $apps = $user->getApps();
        $current_app = wa()->getApp();
        $ui_version = wa()->whichUI($current_app);
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

        list(
            $header_top,
            $header_middle,
            $header_bottom,
            $header_notification,
            $header_user_area
        ) = $this->execBackendHeaderEvent();

        $app_info = wa()->getAppInfo();

        $push_params = array(
            'current_app_info' => $app_info,
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

        $request_uri = waRequest::server('REQUEST_URI');
        $backend_url = wa()->getConfig()->getBackendUrl(true);

        $notifications = $this->getAnnouncements(['backend_header_notification' => $header_notification]);
        $announcement_seen = wa()->getUser()->getSettings('webasyst', 'wa_announcement_seen');
        $new_notification_group_id_to_id = [];

        $notifications_count = 0;
        if ($announcement_seen) {
            $announcement_seen_ts = strtotime($announcement_seen);
        }
        $has_new_notifications = false;
        $has_old_notifications = false;
        foreach($notifications as $n) {
            if (!empty($n['is_virtual']) || empty($n['datetime'])) {
                continue;
            }
            foreach ($n['rows'] as $row) {
                $notifications_count++;
                if (!empty($announcement_seen_ts) && strtotime($row['datetime']) > $announcement_seen_ts) {
                    $has_new_notifications = true;
                    $new_notification_group_id_to_id[$n['id']][$row['id']] = 1;
                } else {
                    $has_old_notifications = true;
                }
            }
        }

        $total_count = $announcement_model->countByField([
            'app_id' => array_keys($user->getApps() + ['webasyst' => 1]),
        ]);

        $notifications_load_more_url = $backend_url."webasyst/announcements/loadMore/";
        if ($notifications_count >= $total_count) {
            $notifications_load_more_url = null;
        }

        // force show from installer
        if (!$has_new_notifications && !empty($notifications)) {
            $has_new_notifications = !empty($notifications['installer']['is_virtual']);
        }

        $this->view->assign([
            'root_url'        => wa()->getRootUrl(),
            'backend_url'     => $backend_url,
            'request_uri'     => $request_uri,
            'webasyst_id_settings_url' => $this->getWebasystIDSettingsUrl(),
            'company_name'    => htmlspecialchars($app_settings_model->get('webasyst', 'name', _ws('My company')), ENT_QUOTES, 'utf-8'),
            'logo'            => (new webasystLogoSettings())->get(),
            'company_url'     => $app_settings_model->get('webasyst', 'url', wa()->getRootUrl(true)),
            'date'            => $date,
            'user'            => $user,
            'header_items'    => $this->getHeaderItems(),
            'current_app'     => $current_app,
            'counts'          => $counts,
            'wa_version'      => wa()->getVersion('webasyst'),
            'announcements'   => $announcements,
            'notifications'   => $notifications,
            'has_new_notifications' => $has_new_notifications,
            'has_old_notifications' => $has_old_notifications,
            'notifications_load_more_url' => $notifications_load_more_url,
            'new_notification_group_id_to_id' => $new_notification_group_id_to_id,
            'header_top'      => $header_top,
            'header_middle'   => $header_middle,
            'header_bottom'   => $header_bottom,
            'header_user_area' => $header_user_area,
            'include_wa_push' => $include_wa_push,
            'webasyst_id_auth_banner' => $this->getWebasystIDAuthBanner(),
            'show_connection_banner'  => $this->showConnectionBanner(),
            'current_domain'  => $this->getCurrentDomain(),
            'app_info'        => $app_info,
            'frontend_links'  => $this->getFrontendLinks(),
            'custom_params'   => $this->params['custom']
        ] + $this->getCalendarData());

        if ($this->single_app_mode) {
            $template_path = 'templates/actions/backend/BackendHeaderSingleApp.html';
        } else {
            $template_path = 'templates/actions/backend/BackendHeader.html';
        }
        $this->setTemplate(wa()->getAppPath($template_path, 'webasyst'));
    }

    protected function getCurrentDomain()
    {
        $domain = wa()->getConfig()->getDomain();
        return waIdna::dec($domain);
    }

    protected function getFrontendLinks()
    {
        $result = [];
        $routing = wa()->getRouting();
        $domains = $routing->getDomains();
        $current_domain = wa()->getConfig()->getDomain();
        foreach($domains as $domain) {
            if ($current_domain === $domain && waRequest::isHttps()) {
                $protocol = 'https://';
            } else {
                $protocol = 'http://';
            }
            $routes = $routing->getRoutes($domain);
            $app_by_url = waUtils::getFieldValues($routes, 'app', 'url');
            if (isset($app_by_url['*'])) {
                $result[waIdna::dec($domain)] = $protocol.rtrim($domain, '/').'/';
            } else {
                $url = array_search('site', $app_by_url);
                if ($url) {
                    $result[waIdna::dec($domain)] = $protocol.rtrim($domain, '/').'/'.rtrim($url, '*');
                }
            }
        }
        return $result;
    }
}
