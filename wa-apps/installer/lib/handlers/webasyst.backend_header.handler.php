<?php

/**
 * Hearing
 */
class installerWebasystBackend_headerHandler extends waEventHandler
{
    private $cache = [];

    public function execute(&$params)
    {
        return $this->getAnnouncements([
            'current_app_id' => !empty($params['current_app']) ? $params['current_app'] : wa()->getConfig()->getApplication(),
            'ui_version' => !empty($params['ui_version']) ? $params['ui_version'] : wa()->whichUI()
        ]);
    }

    /**
     * @param array $params
     *      $params['current_app_id'] [required]
     * @return array $result
     *      string $result[<hook_name>]
     * @throws SmartyException
     * @throws waException
     */
    protected function getAnnouncements(array $params = [])
    {
        if (!wa()->getUser()->getRights('installer')) {
            return [
                'header_top' => '',
                'notification' => '',
            ];
        }

        $current_app_id = $params['current_app_id'];

        $top_header_list = $this->getTopHeaderList($current_app_id);
        $notification_list = $this->getNotificationList($current_app_id);

        // Disable certain plugins if told to
        $slugs = $this->getSlugsToSwitchOff($top_header_list, $notification_list);
        $this->switchOffSlugs($slugs);

        // top header list by default only for UI 1.3
        // but if there is a force special flag in announcement then it also applicable for UI 2.0
        if ($params['ui_version'] === '2.0') {
            $top_header_list = array_filter($top_header_list, function ($a) {
                return !empty($a['ui2.0']);
            });
        }

        // which list to which template send to render
        $rendering = [
            'header_top' => [
                'template' => wa('installer')->getAppPath('lib/handlers/templates/webasyst.backend_header.top_header.announcement.html', 'installer'),
                'list' => $top_header_list
            ],
            'notification' => [
                'template' => wa('installer')->getAppPath('lib/handlers/templates/webasyst.backend_header.notification.announcement.html', 'installer'),
                'list' => $notification_list
            ]
        ];

        $result = [];
        foreach ($rendering as $hook_name => $data) {
            $result[$hook_name] = '';
            if ($data['list']) {
                $result[$hook_name] = $this->renderTemplate($data['template'], [
                    'announcements' => $data['list'],
                    'current_app_id' => $current_app_id,
                    'ui_version' => $params['ui_version']
                ]);
            }
        }

        return $result;
    }

    protected function getSlugsToSwitchOff($top_header_list, $notification_list)
    {
        $slugs = [];
        foreach(array_merge($top_header_list, $notification_list) as $a) {
            if (!empty($a['switch_off']) && is_array($a['switch_off'])) {
                $slugs += array_flip($a['switch_off']);
            }
        }
        return array_keys($slugs);
    }

    protected function switchOffSlugs($slugs)
    {
        if (!$slugs) {
            return;
        }

        $old_app_id = wa()->getApp();
        wa('installer', true);

        $something_changed = false;
        $installer = new waInstallerApps();

        foreach($slugs as $slug) {
            if (preg_match('~^([^/]+)/plugins/([^/]+)$~', $slug, $matches)) {
                list($_, $app_id, $plugin_id) = $matches;
                try {
                    // Make sure plugin is actually turned on before trying to disable it
                    if (!wa()->appExists($app_id)) {
                        continue;
                    }
                    $app_plugins = wa($app_id)->getConfig()->getPlugins();
                    if (!isset($app_plugins[$plugin_id])) {
                        continue;
                    }

                    // Disable plugin
                    $something_changed = true;
                    $installer->updateAppPluginsConfig($app_id, $plugin_id, false);
                    (new waLogModel())->add('item_disable', [
                        'type' => 'plugins',
                        'id'   => sprintf('%s/%s', $app_id, $plugin_id),
                        'reason' => 'license',
                    ], null, 0);
                } catch (waException $e) {
                }
            } else {
                // Yaarrr!
            }
        }

        if ($something_changed) {
            installerHelper::flushCache();
        }

        wa($old_app_id, true);
    }

    /**
     * @param $template
     * @param array $assign
     * @return string
     * @throws SmartyException
     * @throws waException
     */
    private function renderTemplate($template, array $assign = [])
    {
        $view = wa('installer')->getView();
        $old_vars = $view->getVars();
        $view->clearAllAssign();
        $view->assign($assign);
        $html = $view->fetch($template);
        $view->clearAllAssign();
        $view->assign($old_vars);
        return $html;
    }

    /**
     * @param string $current_app
     * @return array $announcements
     *      string      $announcements[<key>]['html']
     *      bool        $announcements[<key>]['always_open']
     *      string|null $announcements[<key>]['app_id']
     * @see installerAnnouncementList::getTopHeaderList
     */
    private function getTopHeaderList($current_app)
    {
        return $this->getFromCache("list_{$current_app}", function () use($current_app) {
            return (new installerAnnouncementList)->withFilteredByApp($current_app);
        })->getTopHeaderList();
    }

    /**
     * @param $current_app
     * @return array $announcements
     *      string      $announcements[<key>]['html']
     *      bool        $announcements[<key>]['always_open']
     *      string|null $announcements[<key>]['app_id']
     * @see installerAnnouncementList::getNotificationList
     */
    private function getNotificationList($current_app)
    {
        return $this->getFromCache("list_{$current_app}", function () use($current_app) {
            return (new installerAnnouncementList)->withFilteredByApp($current_app);
        })->getNotificationList();
    }

    private function getFromCache($key, $loader)
    {
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $loader();
        }
        return $this->cache[$key];
    }
}
