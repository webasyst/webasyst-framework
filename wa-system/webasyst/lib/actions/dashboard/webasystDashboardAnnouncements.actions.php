<?php
/**
 * Endpoints for wa_announcements on WA dashboard.
 * {$wa_backend_url}/webasyst/announcements/<action>/
 */
class webasystDashboardAnnouncementsActions extends waActions
{
    use webasystHeaderTrait;

    protected function preExecute()
    {
        if (!wa()->getUser()->getRights('team', 'edit_announcements')) {
            if ($this->action !== 'read' && $this->action !== 'loadMore') {
                throw new waRightsException();
            }
        }
    }

    public function createAction()
    {
        $data = $this->getDataFromPost();
        $errors = [];
        if (!isset($data['text'])) {
            $errors[] = [
                'field' => 'data[text]',
                'error_code' => 'required',
                'error_description' => _ws("This field is required"),
            ];
        }
        $data['datetime'] = $this->sanitizeDatetime(ifset($data, 'datetime', null));
        $data['ttl_datetime'] = $this->sanitizeDatetime(ifset($data, 'ttl_datetime', null), false);
        $data['is_pinned'] = empty($data['is_pinned']) ? 0 : 1;

        $data['link'] = $this->sanitizeUrl(ifset($data, 'link', null));
        if ($data['link']) {
            $data['data'] = waUtils::jsonEncode(['link' => $data['link']]);
        }
        unset($data['link']);

        if ($errors) {
            $this->displayJson(null, $errors);
            return;
        }

        $should_notify_contacts = !empty($data['is_notify']);
        unset($data['is_notify']);

        $group_ids = $this->getGroupIdsFromPost();
        if ($group_ids) {
            $data['access'] = 'limited';
        }

        $row = [
          'app_id' => 'webasyst',
          'contact_id' => wa()->getUser()->getId(),
          'type' => 'markdown',
        ] + $data;

        $announcement_model = new waAnnouncementModel();
        $id = $announcement_model->insert($row);
        if ($group_ids) {
            $announcement_rights_model = new waAnnouncementRightsModel();
            $announcement_rights_model->set($id, $group_ids);
        }

        $announcement = $announcement_model->getById($id);
        $prepared_announcement = $this->prepareOutput($announcement);
        $this->displayJson($prepared_announcement);

        if ($should_notify_contacts) {
            $this->notifyContacts([
                'text' => $prepared_announcement['text'],
            ] + $announcement + $prepared_announcement);
        }
    }

    public function updateAction()
    {
        $id = $this->getIdFromPost();
        $data = $this->getDataFromPost();

        $announcement_model = new waAnnouncementModel();
        $announcement = $announcement_model->getByField([
            'app_id' => 'webasyst',
            'type' => 'markdown',
            'id' => $id,
        ]);
        if (!$announcement) {
            $this->displayJson(null, [[
                'error_code' => 'not_found',
                'error_description' => _ws('Record not found.'),
            ]]);
            return;
        }
        if ($announcement['contact_id'] != wa()->getUser()->getId() && !wa()->getUser()->isAdmin()) {
            $this->displayJson(null, [[
                'error_code' => 'access_denied',
                'error_description' => _ws('Access denied'),
            ]]);
            return;
        }

        $errors = [];
        if (isset($data['text'])) {
            if (!is_scalar($data['text'])) {
                $data['text'] = '';
            }
            $data['text'] = (string) $data['text'];
            if (!strlen($data['text'])) {
                $errors[] = [
                    'field' => 'data[text]',
                    'error_code' => 'required',
                    'error_description' => _ws("This field is required"),
                ];
            }
        }
        if (isset($data['datetime'])) {
            $data['datetime'] = $this->sanitizeDatetime(ifset($data, 'datetime', null));
        }
        if (isset($data['ttl_datetime'])) {
            $data['ttl_datetime'] = $this->sanitizeDatetime(ifset($data, 'ttl_datetime', null), false);
        }
        if (isset($data['is_pinned'])) {
            $data['is_pinned'] = empty($data['is_pinned']) ? 0 : 1;
        }

        $data['link'] = $this->sanitizeUrl(ifset($data, 'link', null));
        if ($data['link']) {
            $data['data'] = waUtils::jsonEncode(['link' => $data['link']]);
        }
        unset($data['link']);

        if ($errors) {
            $this->displayJson(null, $errors);
            return;
        }

        $group_ids = $this->getGroupIdsFromPost();
        if ($group_ids !== null) {
            $data['access'] = $group_ids ? 'limited' : 'all';
            $announcement_rights_model = new waAnnouncementRightsModel();
            $announcement_rights_model->set($id, $group_ids);
        }

        $should_notify_contacts = !empty($data['is_notify']);
        unset($data['is_notify']);

        if ($data) {
            $row = [
              'app_id' => 'webasyst',
              'type' => 'markdown',
            ] + $data;
            $announcement_model->updateById($id, $row);
        }
        $announcement = $announcement_model->getById($id);
        $prepared_announcement = $this->prepareOutput($announcement);
        $this->displayJson($prepared_announcement);

        if ($should_notify_contacts) {
            $this->notifyContacts([
                'text' => $prepared_announcement['text'],
            ] + $announcement + $prepared_announcement);
        }
    }

    public function deleteAction()
    {
        $id = $this->getIdFromPost();
        $announcement_model = new waAnnouncementModel();

        $announcement = $announcement_model->getByField([
            'app_id' => 'webasyst',
            'type' => 'markdown',
            'id' => $id,
        ]);
        if (!$announcement) {
            $this->displayJson(null, [[
                'error_code' => 'not_found',
                'error_description' => _ws('Record not found.'),
            ]]);
            return;
        }
        if ($announcement['contact_id'] != wa()->getUser()->getId() && !wa()->getUser()->isAdmin()) {
            $this->displayJson(null, [[
                'error_code' => 'access_denied',
                'error_description' => _ws('Access denied'),
            ]]);
            return;
        }

        $announcement_model->deleteById($id);
        $this->displayJson(null);
    }

    public function readAction()
    {
        $id = $this->getIdFromPost();
        $announcement_model = new waAnnouncementModel();

        $announcement = $announcement_model->getByField([
            'app_id' => 'webasyst',
            'type' => 'markdown',
            'id' => $id,
        ]);
        if (!$announcement) {
            $this->displayJson(null, [[
                'error_code' => 'not_found',
                'error_description' => _ws('Record not found.'),
            ]]);
            return;
        }

        $this->displayJson($this->prepareOutput($announcement));
    }

    public function loadMoreAction()
    {
        $before_id = waRequest::request('before_id', null, 'int');
        $limit = waRequest::request('limit', 15, 'int');

        $backend_url = $this->getConfig()->getBackendUrl(true);
        $announcement_model = new waAnnouncementModel();
        $data = $announcement_model->getByAppsBefore(wa()->getUser()->getId(), array_keys(wa()->getUser()->getApps() + ['webasyst' => true]), $limit, $before_id);
        $notifications = [];
        $notifications_load_more_url = null;
        if ($data) {

            $sanitizer = new waHtmlSanitizer();
            foreach($data as &$notification) {
                $notification['text'] = $sanitizer->sanitize($notification['text']);
            }
            unset($notification);

            $notifications = $this->getAnnouncements([
                'data' => $data,
            ]);
            if (count($data) >= $limit) {
                $min_id = min(array_column($data, 'id'));
                $notifications_load_more_url = $backend_url."webasyst/announcements/loadMore/?before_id={$min_id}&limit={$limit}";
            }
        }

        $this->display([
            'root_url'           => wa()->getRootUrl(),
            'apps'               => wa()->getUser()->getApps(),
            'backend_url'        => $backend_url,
            'user'               => wa()->getUser(),
            'notifications'      => $notifications,
            'notifications_load_more_url' => $notifications_load_more_url,
        ], 'templates/actions/dashboard/DashboardAnnouncementLoadMore.html');
    }

    protected function getIdFromPost()
    {
        $id = waRequest::request('id', 0, 'int');
        if (!$id) {
            wa()->getResponse()->setStatus(400);
            $this->displayJson(null, [[
                'error_code' => 'no_id_provided',
                'error_description' => 'id is required',
            ]]);
            exit;
        }
        return $id;
    }

    protected function getDataFromPost()
    {
        $data = waRequest::post('data', null, 'array');
        if (!$data || !is_array($data)) {
            wa()->getResponse()->setStatus(400);
            $this->displayJson(null, [[
                'error_code' => 'no_data_provided',
                'error_description' => 'data parameter is required and must be an array',
            ]]);
            exit;
        }
        $data = array_intersect_key($data, [
            'text' => '',
            'link' => '',
            'datetime' => '',
            'ttl_datetime' => '',
            'is_pinned' => '0',
            'is_notify' => false,
        ]);
        return $data;
    }

    protected function getGroupIdsFromPost()
    {
        $group_ids = waRequest::post('group_ids', [], 'array_int');
        $contact_ids = waRequest::post('contact_ids', [], 'array_int');
        if (!$group_ids && !$contact_ids) {
            return null;
        }
        $group_ids = array_filter((array)$group_ids);
        if ($contact_ids) {
            $contact_ids = array_filter((array)$contact_ids);
            $group_ids = array_merge($group_ids, array_map(function($id) {
                return -$id;
            }, $contact_ids));
        }
        return $group_ids;
    }

    protected function sanitizeDatetime($datetime, $default_current=true)
    {
        if ($datetime) {
            $ts = @strtotime($datetime);
            if ($ts) {
                $timezone = wa()->getUser()->getTimezone();
                $default_timezone = waDateTime::getDefaultTimeZone();
                if ($timezone && $timezone != $default_timezone) {
                    $date_time = new DateTime($datetime, new DateTimeZone($timezone));
                    $date_time->setTimezone(new DateTimeZone($default_timezone));
                    return $date_time->format('Y-m-d H:i:s');
                } else {
                    return date('Y-m-d H:i:s', $ts);
                }
            }
        }
        return $default_current ? date('Y-m-d H:i:s') : null;
    }

    protected function sanitizeUrl($url)
    {
        if ($url && (new waUrlValidator())->isValid($url)) {
            return $url;
        }
        return null;
    }

    protected function prepareOutput($announcement_data)
    {
        $announcement_data += [
            'humandatetime' => waDateTime::format('humandatetime', $announcement_data['datetime'])
        ];

        foreach(['datetime', 'ttl_datetime'] as $k) {
            if (!empty($announcement_data[$k])) {
                $announcement_data[$k] = waDateTime::format('Y-m-d H:i:s', $announcement_data[$k]);
            }
        }

        $sanitizer = new waHtmlSanitizer();
        $announcement_data['text'] = $sanitizer->sanitize($announcement_data['text']);

        if ($announcement_data['access'] !== 'all') {
            list(
                $announcement_data['access_group_ids'],
                $announcement_data['access_contact_ids']
            ) = (new waAnnouncementRightsModel())->getIds($announcement_data['id']);
        }

        return $announcement_data;
    }

    protected function notifyContacts($announcement)
    {
        if ($announcement['access'] == 'all') {
            $recipients = self::getAnnouncementNotificationRecipients();
        } else {
            $recipients = self::getAnnouncementNotificationRecipients($announcement['access_group_ids'], $announcement['access_contact_ids']);
        }
        unset($recipients[wa()->getUser()->getId()]);
        $recipients = array_filter($recipients, function($c) {
            return !empty($c['email']) || !empty($c['phone']);
        });
        if (!$recipients) {
            return;
        }

        $sms_message = null;
        if (waSMS::adapterExists()) {
            try {
                $sms_message = self::formatSmsNotification($announcement, wa()->getUser());
            } catch (Exception $e) {
                waLog::log('Unable to notify users about announcement: '.$e->getMessage()."\n:".$e->getTraceAsString(), 'sms.log');
            }
        }

        try {
            list($email_subject, $email_body) = self::formatEmailNotification($announcement, wa()->getUser());
        } catch (Exception $e) {
            waLog::log('Unable to notify users about announcement: '.$e->getMessage()."\n:".$e->getTraceAsString(), 'mail.log');
        }

        foreach($recipients as $c) {
            if ($c['phone'] && $sms_message) {
                try {
                    $sms = new waSMS();
                    $sms->send($c['phone'], $sms_message);
                } catch (Exception $e) {
                }
            }

            if ($c['email'] && !empty($email_subject) && !empty($email_body)) {
                try {
                    $m = new waMailMessage($email_subject);
                    $m->setBody($email_body);
                    $m->setTo($c['email']);
                    $m->send();
                } catch (Exception $e) {
                }
            }

        }
    }

    protected static function formatSmsNotification(array $announcement, waContact $from)
    {
        $sanitizer = new waHtmlSanitizer();
        $text = $sanitizer->toPlainText($announcement['text']);
        return $from['name'].": ".$text;
    }

    protected static function formatEmailNotification(array $announcement, waContact $from)
    {
        $wa = wa('webasyst');
        $view = $wa->getView();

        $site_domain = waIdna::dec(wa()->getConfig()->getDomain());
        $host_url = wa()->getConfig()->getHostUrl();

        $logo = (new webasystLogoSettings([ 'absolute_urls' => true ]))->get();
        unset($logo['gradients']);

        $view->assign([
            'host_url' => $host_url,
            'site_domain' => $site_domain,
            'announcement' => $announcement,
            'sender_user' => $from,
            'app_name' => wa()->accountName(),
            'app_icon' => empty($logo['image']['thumbs']['64x64']['url']) ? null : $logo['image']['thumbs']['64x64']['url'],
        ]);
        $body = $view->fetch($wa->getAppPath('templates/mail/AnnouncementNotification.html', 'webasyst'));

        $subject = sprintf_wp("Announcement from %s", wa()->accountName());

        if (!empty($announcement['is_pinned'])) {
            $subject = '⚡ ' . $subject;
        }

        return [$subject, $body];
    }

    /**
     * List of contacts: potential recipients for Email or SMS notifications
     * if notification is enabled for a new announcement.
     */
    protected static function getAnnouncementNotificationRecipients($group_ids=null, $contact_ids=null)
    {

        try {
            wa('team');
            $groups = teamHelper::getVisibleGroups() + teamHelper::getVisibleLocations();
            $contacts = teamUser::getList('users', array(
                'fields' => 'id,name,email,phone',
            ));
        } catch (waException $e) {
            return [];
        }

        if ($group_ids !== null || $contact_ids !== null) {
            $contact_ids = ifset($contact_ids, []);
            if ($group_ids !== null) {
                $group_ids = array_filter($group_ids, function($id) use ($groups) {
                    return isset($groups[$id]);
                });
                if ($group_ids)  {
                    $user_groups_model = new waUserGroupsModel();
                    $rows = $user_groups_model->getByField('group_id', $group_ids, true);
                    foreach($rows as $row) {
                        $contact_ids[] = $row['contact_id'];
                    }
                }
            }
            $contacts = array_intersect_key($contacts, array_flip($contact_ids));
        }
        foreach ($contacts as $id => &$c) {
            $c['email'] = ifset($c, 'email', 0, null);
            $c['phone'] = ifset($c, 'phone', 0, 'value', null);
        }
        unset($c);
        return $contacts;
    }
}
