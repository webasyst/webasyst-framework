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

        if (!empty($this->params['single_app_user'])) {
            return $this->executeSingleAppUserNavigation();
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
            'custom_params'   => $this->params['custom'],
            'is_user_connected_to_waid' => !!(new waContactWaidModel())->get($user->getId()),
        ] + $this->getCalendarData());

        if ($this->single_app_mode) {
            $template_path = 'templates/actions/backend/BackendHeaderSingleApp.html';
        } else {
            $this->assignNotificationsData(['backend_header_notification' => $header_notification]);
            $template_path = 'templates/actions/backend/BackendHeader.html';
        }
        $this->setTemplate(wa()->getAppPath($template_path, 'webasyst'));
    }

    /**
     * Implements {$wa->headerSingleAppUser()} smarty helper.
     * Used by apps in single-app mode to render simplified WA navigation if they desire.
     */
    protected function executeSingleAppUserNavigation()
    {
        $wa = wa();
        $view = $wa->getView();
        $user = $wa->getUser();
        $request_uri = waRequest::server('REQUEST_URI');
        $backend_url = $wa->getConfig()->getBackendUrl(true);
        list( , , , $header_notification, $header_user_area) = $this->execBackendHeaderEvent();

        $view->assign([
            'backend_url'    => $wa->getConfig()->getBackendUrl(true),
            'header_single_app_user' => $header_user_area['single_app'],
            'root_url'       => $wa->getRootUrl(),
            'backend_url'    => $backend_url,
            'request_uri'    => $request_uri,
            'user'           => $user,
            'is_user_connected_to_waid' => !!(new waContactWaidModel())->get($user->getId()),
        ]);

        $this->assignNotificationsData(['backend_header_notification' => $header_notification]);

        $this->setTemplate($wa->getAppPath('templates/actions/backend/BackendHeaderSingleAppUser.html', 'webasyst'));
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
