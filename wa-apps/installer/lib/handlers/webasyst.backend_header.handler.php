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
            'current_app_id' => !empty($params['current_app']) ? $params['current_app'] : wa()->getConfig()->getApplication()
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
            $view = wa('installer')->getView();
            $view->assign(array(
                'announcements'  => $data['list'],
                'current_app_id' => $current_app_id,
            ));

            $result[$hook_name] = $view->fetch($data['template']);
        }

        return $result;
    }

    /**
     * @param $current_app
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
