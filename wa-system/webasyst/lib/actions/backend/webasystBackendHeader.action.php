<?php

class webasystBackendHeaderAction extends waViewAction
{
    use webasystHeaderTrait;

    protected $params = [];

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
        parent::__construct($params);
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
         * @return array[string][string]string $return[%plugin_id%]['header_top'] html output (will be rendered only in UI v1.3)
         * @return array[string][string]string $return[%plugin_id%]['header_middle'] html output (will be rendered only in UI v1.3)
         * @return array[string][string]string $return[%plugin_id%]['header_bottom'] html output (will be rendered only in UI v1.3)
         * @return array[string][string]string $return[%plugin_id%]['notification'] html output (will be rendered only in UI v2.0, "under the bell")
         */
        $params = array();
        $backend_header = wa()->event(array('webasyst', 'backend_header'), $params);

        $header_top = [];
        $header_middle = [];
        $header_bottom = [];
        $header_notification = [];

        foreach ($backend_header as $app_id => $header) {
            if (is_array($header)) {
                if (!empty($header['header_top'])) {
                    $header_top[] = $header['header_top'];
                }
                if (!empty($header['header_middle'])) {
                    $header_bottom[] = $header['header_middle'];
                }
                if (!empty($header['header_bottom'])) {
                    $header_bottom[] = $header['header_bottom'];
                }
                if (!empty($header['notification'])) {
                    $header_notification[] = $header['notification'];
                }
            } elseif (is_string($header)) {
                $header_middle[] = $header;
            }
        }

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
            'company_name'    => htmlspecialchars($app_settings_model->get('webasyst', 'name', 'Webasyst'), ENT_QUOTES, 'utf-8'),
            'logo'            => (new webasystLogoSettings())->get(),
            'company_url'     => $app_settings_model->get('webasyst', 'url', wa()->getRootUrl(true)),
            'date'            => $date,
            'user'            => $user,
            'header_items'    => $this->getHeaderItems(),
            'current_app'     => $current_app,
            'counts'          => $counts,
            'wa_version'      => wa()->getVersion('webasyst'),
            'announcements'   => $announcements,
            'notifications'   => $this->getAnnouncements(['backend_header_notification' => $header_notification]),
            'header_top'      => $header_top,
            'header_middle'   => $header_middle,
            'header_bottom'   => $header_bottom,
            'include_wa_push' => $include_wa_push,
            'webasyst_id_auth_banner' => $this->getWebasystIDAuthBanner(),
            'show_connection_banner'  => $this->showConnectionBanner(),
            'current_domain'  => $this->getCurrentDomain(),
            'app_info'        => $app_info,
            'custom_params'   => $this->params['custom']
        ]);

        $this->setTemplate(wa()->getAppPath('templates/actions/backend/BackendHeader.html', 'webasyst'));
    }

    protected function getCurrentDomain()
    {
        $domain = wa()->getConfig()->getDomain();
        return waIdna::dec($domain);
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
