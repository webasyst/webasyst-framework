<?php

class teamConfig extends waAppConfig
{
    const CALENDAR_DAYS = 34;
    const ROWS_PER_PAGE = 30;

    // see also a hack in teamFrontController->dispatch()
    public function getRouting($route = array())
    {
        if ($this->routes === null) {
            $path = $this->getConfigPath('routing.backend.php', true, $this->application);
            if (!file_exists($path)) {
                $path = $this->getConfigPath('routing.backend.php', false, $this->application);
            }
            if (file_exists($path)) {
                $this->routes = include($path);
            } else {
                $this->routes = array();
            }

            $this->routes = array_merge($this->getPluginRoutes($route), $this->routes);
        }
        return $this->routes;
    }

    protected function getPluginRoutes($route)
    {
        /**
         * Extend routing via plugin routes
         * @event routing
         * @param array $routes
         * @return array $routes routes collected for every plugin
         */
        $result = wa()->event(array($this->application, 'routing_backend'), $route);
        $all_plugins_routes = array();
        foreach ($result as $plugin_id => $routing_rules) {
            if (!$routing_rules) {
                continue;
            }
            $plugin = str_replace('-plugin', '', $plugin_id);
            foreach ($routing_rules as $url => & $route) {
                if (!is_array($route)) {
                    list($route_ar['module'], $route_ar['action']) = explode('/', $route);
                    $route = $route_ar;
                }
                if (!array_key_exists('plugin', $route)) {
                    $route['plugin'] = $plugin;
                }
                $all_plugins_routes[$url] = $route;
            }
            unset($route);
        }
        return $all_plugins_routes;
    }

    public function getUsernameFormats()
    {
        $res = $this->getOptionWithDefault('user_name_formats', array());
        if (is_array($res)) {
            foreach ($res as &$r) {
                $r['name'] = _w($r['name']);
            }
            unset($r);
        }
        return $res;
    }

    /**
     * Upper bound of event date in sync (import). Measured in months from current date
     * @return int
     */
    public function getExternalCalendarSyncMaxDateOffset()
    {
        $value = (int)$this->getOptionWithDefault('external_calendar_sync_max_date_offset', 6);
        if ($value <= 0) {
            $value = 1;
        }
        return $value;
    }

    protected function getOptionWithDefault($name, $default = null)
    {
        $value = $this->getOption($name);
        if ($value === null) {
            return $default;
        }
        return $value;
    }

    public function init()
    {
        parent::init();

        // Remove outdated tokens from time to time
        if (mt_rand(0, 500) === 0) {
            $app_token_model = new waAppTokensModel();
            $app_token_model->purge();
        }
    }

    public function dispatchAppToken($data)
    {
        $app_tokens_model = new waAppTokensModel();

        // Unknown token type?
        if ($data['type'] != 'user_invite') {
            $app_tokens_model->deleteById($data['token']);
            throw new waException("Page not found", 404);
        }

        // Make sure contact is still ok
        $contact = new waContact($data['contact_id']);
        if (!$contact->exists() || $contact['is_user'] < 0) {
            $app_tokens_model->deleteById($data['token']);
            throw new waException("Page not found", 404);
        }

        wa()->getStorage()->open();
        $data_data = (array)json_decode($data['data'], true);
        if (empty($data_data['session_id'])) {
            // First-time use of the token:
            // bind it to current session
            $data_data['session_id'] = session_id();
            $data['data'] = json_encode($data_data);
            $app_tokens_model->updateById($data['token'], array(
                'data' => $data['data'],
            ));
        } elseif ($data_data['session_id'] != session_id()) {
            // Only allow one single bound session to access the page
            throw new waException("Page not found", 404);
        }

        wa('webasyst');
        $controller = wa()->getDefaultController();
        $controller->setAction(new teamInviteFrontendAction($data));
        $controller->run();
    }

    public function checkUpdates()
    {
        $result = parent::checkUpdates();
        $this->createThumbnailGenerator();
        return $result;
    }

    /**
     * Make sure there's a script in wa-data that generates contact avatars.
     */
    public function createThumbnailGenerator($force = false)
    {
        $path = wa()->getDataPath('photos', true, 'contacts');

        $force = $force || !file_exists($path.'/thumbnail_generator');
        if (!$force) {
            return;
        }

        touch($path.'/team_thumbnail_generator');
        waFiles::copy($this->getPath('system').'/contact/data/.htaccess', $path.'/.htaccess');
        waFiles::write(
            $path.'/thumb.php',
            '<?php
            $file = realpath(dirname(__FILE__)."/../../../../")."/wa-system/contact/data/thumb.php";
            if (file_exists($file)) {
                include($file);
            } else {
                header("HTTP/1.0 404 Not Found");
            }
        ');
    }

    public function explainLogs($logs)
    {
        $actions = array(
            'user_group_add' => array(
                'label'  => _w(
                    '<span class="activity-action gray">added</span> %s <span class="activity-action gray">to group</span> %s'
                ),
                'format' => 'user,group'
            ),
            'user_group_remove' => array(
                'label' => _w(
                    '<span class="activity-action gray">removed</span> %s <span class="activity-action gray">from group</span> %s'
                ),
                'format' => 'user,group'
            ),
            'user_invite'       => array('label' => '%s', 'format' => 'user,group'),
            'group_add'         => array('label' => '%s%s', 'format' => 'user,group'),
            'group_edit'        => array('label' => '%s%s', 'format' => 'user,group'),
            'event_add'         => array('label' => '%s', 'format' => 'event'),
            'event_edit'        => array('label' => '%s', 'format' => 'event'),
            'calendar_add'      => array('label' => '%s', 'format' => 'calendar'),
            'calendar_edit'     => array('label' => '%s', 'format' => 'calendar'),
        );
        $contacts = $groups = $events = $calendars = array();
        $gm = new waGroupModel();
        $cem = new waContactEventsModel();
        $ccm = new waContactCalendarsModel();
        foreach ($logs as $l_id => $l) {
            if (isset($actions[$l['action']])) {
                if ($actions[$l['action']]['format'] == 'event') {
                    if (!empty($l['params']) && empty($events[$l['params']])) {
                        $event = $cem->getById($l['params']);
                        $events[$l['params']] = $event['summary'];
                    }
                } elseif ($actions[$l['action']]['format'] == 'calendar') {
                    if (!empty($l['params']) && empty($calendars[$l['params']])) {
                        $calendar = $ccm->getById($l['params']);
                        $calendars[$l['params']] = $calendar['name'];
                    }
                } else {
                    if (!empty($l['contact_id'])) {
                        $c = new waContact($l['contact_id']);
                        if ($c->exists()) {
                            $contacts[$l['contact_id']] = array($c->getName(), $c->get('login'));
                        } else {
                            $contacts[$l['contact_id']] = array(sprintf(_w("Contact with id = %s doesn't exist"), $l['contact_id']), '');
                        }
                    }
                    if (!empty($l['subject_contact_id']) && empty($contacts[$l['subject_contact_id']])) {
                        $c = new waContact($l['subject_contact_id']);
                        if ($c->exists()) {
                            $contacts[$l['subject_contact_id']] = array($c->getName(), $c->get('login'));
                        } else {
                            $contacts[$l['subject_contact_id']] = array(sprintf(_w("Contact with id = %s doesn't exist"), $l['subject_contact_id']), '');
                        }
                    }
                    if (!empty($l['params']) && empty($groups[$l['params']])) {
                        $group = $gm->getById($l['params']);
                        $groups[$l['params']] = $group['name'];
                    }
                }
            }
        }
        if ($contacts) {
            $app_url = wa()->getConfig()->getBackendUrl(true).$l['app_id'].'/';
            foreach ($logs as &$l) {

                if (isset($actions[$l['action']])) {
                    if ($actions[$l['action']]['format'] == 'event') {
                        $l['params_html'] = sprintf_wp(
                            $actions[$l['action']]['label'],
                            '<a href="'.$app_url.'calendar/">'.
                            htmlspecialchars(ifempty($events[$l['params']], $l['params'])).
                            '</a>'
                        );
                    } elseif ($actions[$l['action']]['format'] == 'calendar') {
                        $l['params_html'] = sprintf_wp(
                            $actions[$l['action']]['label'],
                            '<a href="'.$app_url.'calendar/">'.
                            htmlspecialchars(ifempty($calendars[$l['params']], $l['params'])).
                            '</a>'
                        );
                    } else {
                        $param1 = null;
                        if ($l['subject_contact_id']) {
                            if (!empty($contacts[$l['subject_contact_id']][0])) {
                                $param1 = '<a href="'.$app_url.'id/'.$l['subject_contact_id'].'/">'.
                                    htmlspecialchars($contacts[$l['subject_contact_id']][0]).
                                    '</a>';
                            } else {
                                $param1 = '<span class="small">[ id: '.$l['subject_contact_id'].', '._w(' deleted').' ]</span>';
                            }
                        }
                        if (!empty($groups[$l['params']])) {
                            $param2 = '<a href="'.$app_url.'group/'.$l['params'].'/">'.
                                htmlspecialchars($groups[$l['params']]).
                                '</a>';
                        } else {
                            $param2 = '<span class="small">[ id: '.$l['params'].', '._w('deleted ').' ]</span>';
                        }
                        $l['params_html'] = sprintf_wp($actions[$l['action']]['label'], $param1, $param2);
                    }
                }
                unset($l);
            }
        }
        return $logs;
    }
}
