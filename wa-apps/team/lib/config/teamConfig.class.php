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

    /**
     * @param array $data - if here array of format ['token_info' => <array>, 'auth_result'] => <array>] it is response from webasyst ID auth
     * @throws waException
     */
    public function dispatchAppToken($data)
    {
        $app_tokens_model = new waAppTokensModel();

        $webasyst_id_auth_result = null;
        if (isset($data['token_info'])) {
            // Return after successfull from WAID auth gets here:
            // see waWebasystIDWAAuthController->processInviteAuth().
            // User have seen the invite form (see teamInviteFrontendAction)
            // and selected WAID auth instead of login and password.
            // User successfully finished WAID aith, got redirected back here.
            // Rest of logic is handled in teamInviteFrontendAction called below.
            $webasyst_id_auth_result = isset($data['auth_result']) ? $data['auth_result'] : null;
            $data = $data['token_info'];
        }

        // Unknown token type?
        if ($data['type'] != 'user_invite' && $data['type'] != 'waid_invite') {
            $app_tokens_model->deleteById($data['token']);
            throw new waException("Page not found", 404);
        }

        // Make sure contact is still ok
        $contact = new waContact($data['contact_id']);
        if (!$contact->exists() || $contact['is_user'] < 0) {
            $app_tokens_model->deleteById($data['token']);
            throw new waException("Page not found", 404);
        }

        if ($data['type'] == 'waid_invite') {
            //
            // Token type 'waid_invite' is called by WAID server via API. No user interaction here, no browser.
            // We need to convert contact to backend user,
            // bind this user with WAID provided and give simple API code response.
            //
            $token_data = json_decode($data['data'], true);
            if (!is_array($token_data)) {
                $token_data = [];
            }
            $login = waUtils::getRandomHexString(12);
            $password = waContact::generatePassword();
            teamHelper::convertToBackendUser($contact['id'], $token_data, $login, $password);

            $webasyst_contact_id = waRequest::post('webasyst_contact_id', null, 'int');
            if ($webasyst_contact_id) {
                $contact_waid_model = new waContactWaidModel();
                $contact_waid_model->set($contact['id'], $webasyst_contact_id, []);
            }

            // Update contact with WAID data if any received
            $this->updateContactFromPost($contact);

            $app_tokens_model->deleteById($data['token']);
            wa()->getResponse()->setStatus(204);
            wa()->getResponse()->sendHeaders();
            return;
        }

        wa('webasyst');
        $controller = wa()->getDefaultController();
        $controller->setAction(new teamInviteFrontendAction($data, $webasyst_id_auth_result));
        $controller->run();
    }

    // helper for dispatchAppToken()
    protected function updateContactFromPost($contact)
    {
        $call_save = false;
        foreach([
            'firstname',
            'middlename',
            'lastname',
            'email',
            'phone',
        ] as $f) {
            $value = waRequest::post($f, null, 'string');
            if ($value) {
                $contact[$f] = $value;
                $call_save = true;
            }
        }

        if ($call_save) {
            $contact->save();
        }

        $photo_url = waRequest::post('photo', null, 'string');
        if ($photo_url) {
            $data = file_get_contents($photo_url);
            if ($data !== false) {
                $path = tempnam(sys_get_temp_dir(), 'contactphoto');
                file_put_contents($path, $data);
                $contact->setPhoto($path);
            }
        }

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

        $force = $force || !file_exists($path.'/team_thumbnail_generator');
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
        $logs = parent::explainLogs($logs);

        $app_info = wa()->getAppInfo();
        if ($app_info['id'] != 'team') {
            wa('team', true);
        }

        self::explainContactLogItems($logs);
        self::explainTeamSpecificLogItems($logs);

        if ($app_info['id'] != 'crm') {
            wa($app_info['id'], true);
        }

        return $logs;
    }

    protected function explainTeamSpecificLogItems(&$logs)
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

        $contacts = array();
        $groups = array();
        $events = array();
        $calendars = array();

        $gm = new waGroupModel();
        $cem = new waContactEventsModel();
        $ccm = new waContactCalendarsModel();

        foreach ($logs as $l_id => $l) {
            if (isset($actions[$l['action']])) {
                if ($actions[$l['action']]['format'] == 'event') {
                    if (!empty($l['params']) && empty($events[$l['params']])) {
                        $event = $cem->getById($l['params']);
                        $events[$l['params']] = $event ? $event['summary'] : '';
                    }
                } elseif ($actions[$l['action']]['format'] == 'calendar') {
                    if (!empty($l['params']) && empty($calendars[$l['params']])) {
                        $calendar = $ccm->getById($l['params']);
                        $calendars[$l['params']] = $calendar ? $calendar['name'] : '';
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
                        if ($group) {
                            $groups[$l['params']] = $group['name'];
                        }
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

    }

    protected function explainContactLogItems(&$log_items)
    {
        foreach ($log_items as &$log_item) {
            if ($this->isContactDeleteLogItem($log_item)) {
                $this->explainDeleteContactLogItem($log_item);
            }
        }
        unset($log_item);

        $this->explainLogItemsWithSubjectContactIds($log_items);
    }

    protected function isContactDeleteLogItem($log_item)
    {
        $is_team = isset($log_item['app_id']) && $log_item['app_id'] === 'team';
        $is_delete_contact = isset($log_item['action']) && $log_item['action'] === 'contact_delete';
        return $is_team && $is_delete_contact;
    }

    protected function explainLogItemsWithSubjectContactIds(&$log_items)
    {
        $subject_contact_ids = array();

        foreach ($log_items as $log_item) {
            $is_crm = isset($log_item['app_id']) && $log_item['app_id'] === 'team';
            if ($is_crm && wa_is_int($log_item['subject_contact_id']) && $log_item['subject_contact_id'] > 0) {
                $subject_contact_ids[] = $log_item['subject_contact_id'];
            }
        }

        if (!$subject_contact_ids) {
            return;
        }

        $subject_contact_ids = array_unique($subject_contact_ids);
        $col = new waContactsCollection('id/' . join(',', $subject_contact_ids));

        $contacts = $col->getContacts('id,name,firstname,lastname,middlename,company,is_user,login,email', 0, count($subject_contact_ids));

        $contact_names = $this->extractContactNames($contacts);
        $contact_logins = waUtils::getFieldValues($contacts, 'login', true);

        $team_app_url = wa()->getAppUrl('team');
        $team_contact_url_by_login = "{$team_app_url}u/%s";
        $team_contact_url_by_id = "{$team_app_url}id/%d";

        foreach ($log_items as &$log_item) {
            $is_team = isset($log_item['app_id']) && $log_item['app_id'] === 'team';
            if ($is_team && wa_is_int($log_item['subject_contact_id']) && $log_item['subject_contact_id'] > 0) {

                $contact_id = $log_item['subject_contact_id'];
                $is_contact_exists = isset($contact_names[$contact_id]);
                $contact_login = !empty($contact_logins[$contact_id]) ? $contact_logins[$contact_id] : null;

                if (!$is_contact_exists) {
                    $contact_link = null;
                    $contact_name = sprintf(_w('"%s"'), _w('Deleted contact') . ' ' . $contact_id);
                } else {
                    if ($contact_login) {
                        $contact_link = sprintf($team_contact_url_by_login, $contact_login);
                    } else {
                        $contact_link = sprintf($team_contact_url_by_id, $contact_id);
                    }
                    $contact_name = trim($contact_names[$contact_id]);
                    if (strlen($contact_name) <= 0) {
                        $contact_name = '(' . _w("no name") . ')';
                    }
                }

                if ($contact_link) {
                    $log_item['params_html'] = sprintf("<a href='%s'>%s</a>", $contact_link, htmlspecialchars($contact_name));
                } else {
                    $log_item['params_html'] = htmlspecialchars($contact_name);
                }

            }
        }
        unset($log_item);
    }

    protected function explainDeleteContactLogItem(&$log_item)
    {
        $is_not_empty_params = isset($log_item['params']) && is_scalar($log_item['params']) && strlen(trim($log_item['params'])) > 0;
        if (!$is_not_empty_params) {
            return;
        }

        $params = json_decode($log_item['params'], true);

        if (wa_is_int($params)) {
            $count = $params;
            $log_item['action_name'] = _w('has deleted');
            $log_item['params_html'] = _w('%d contact', '%d contacts', $count);
            return;
        }

        if (!is_array($params)) {
            return;
        }

        $contact_names = $params;

        $contact_names = array_values($contact_names);

        $max_n = 5;
        $count = count($contact_names);

        // wrap around quotes (take into account localization)
        $n = min($max_n, $count);
        for ($i = 0; $i < $n; $i++) {
            $name = $contact_names[$i];
            $name = trim($name);
            if (strlen($name) <= 0) {
                $name = '(' . _w("no name") . ')';
            }
            $contact_names[$i] = sprintf(_w('"%s"'), htmlspecialchars($name));
        }

        if ($count > 1) {
            $log_item['action_name'] = _w('has deleted contacts');
        } else {
            $log_item['action_name'] = _w('has deleted contact');
        }

        if ($count <= $max_n) {
            $log_item['params_html'] = join(', ', $contact_names);
        } elseif ($count > $max_n) {
            $slice_of_contact_names = array_slice($contact_names, 0, $max_n);
            $log_item['params_html'] = sprintf(_w('%s and %s more'), join(', ', $slice_of_contact_names), $count - $max_n);
        }
    }

    /**
     * Extract formatted contact names from list of contacts
     * @param array $contacts
     * @return array
     */
    public function extractContactNames($contacts)
    {
        $names = array();
        foreach ($contacts as $index => $contact) {
            $names[$index] = waContactNameField::formatName($contact);
        }
        return $names;
    }

    /**
     * @return string[] - key-value from alias (name) to size
     */
    public function getProfileCoverSizeAliases()
    {
        return [
            '100x100' => 'preview',
            '1408x440' => 'full'
        ];
    }
}
