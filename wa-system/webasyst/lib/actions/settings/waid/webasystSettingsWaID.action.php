<?php

class webasystSettingsWaIDAction extends webasystSettingsViewAction
{
    const USERS_LIMIT = 200;

    /**
     * @var waWebasystIDClientManager
     */
    protected $cm;
    protected $users;

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->cm = new waWebasystIDClientManager();
    }

    public function execute()
    {
        $connected_users = [];
        $connected_users_count = 0;

        $not_connected_users = [];
        $not_connected_users_count = 0;

        $is_connected = $this->cm->isConnected();
        if ($is_connected) {
            list($connected_users, $connected_users_count) = $this->getConnectedUsers();
            list($not_connected_users, $not_connected_users_count) = $this->getNotConnectedUsers();
        }

        $this->view->assign([
            'is_connected' => $is_connected,
            'is_backend_auth_forced' => $this->cm->isBackendAuthForced(),
            'connected_users' => $connected_users,
            'connected_users_count' => $connected_users_count,
            'not_connected_users' => $not_connected_users,
            'not_connected_users_count' => $not_connected_users_count,
            'users_count' => $this->getUsersCount(),
            'block_show' => $this->getUsersCount() !== self::USERS_LIMIT,
            'upgrade_all' => (bool)$this->getRequest()->get('upgrade_all'),
            'webasyst_id_auth_url' => $this->getWebasystIDAuthUrl(),
            'is_user_bound_to_webasyst_id' => (bool)wa()->getUser()->getWebasystContactId(),
        ]);
    }

    protected function getConnectedUsers()
    {
        $users = array_filter($this->getUsers(), function ($us) {
            return !empty($us['contact_id']);
        });

        $this->workupUsers($users);
        $tm = new waWebasystIDAccessTokenManager();
        class_exists('waContactPhoneField');
        $phone_formatter = new waContactPhoneFormatter();

        foreach ($users as &$user) {
            $access_token = $user['waid_token'];
            $info = $tm->extractTokenInfo($access_token);
            $user['webasyst_id'] = '';
            if (!empty($info['email']) || !empty($info['phone'])) {
                $user['webasyst_id'] = [
                    'email' => $info['email'] ?? '',
                    'phone' => empty($info['phone']) ? '' : $phone_formatter->format(waContactPhoneField::cleanPhoneNumber($info['phone'])),
                ];
            }
            $user['two_fa_mode'] = ifempty($info['two_fa_mode'], false);
            $user['two_fa_time'] = intval(ifempty($info['two_fa_time'])) ? date('Y-m-d H:i:s', intval($info['two_fa_time'])) : false;
        }
        unset($user);

        return [$users, count($users)];
    }

    protected function getNotConnectedUsers()
    {
        $users = array_filter($this->getUsers(), function ($us) {
            return empty($us['contact_id']);
        });
        $this->workupUsers($users);

        $csm = new waContactSettingsModel();
        $user_ids = array_keys($users);
        $waid_invite_datetime_rows = $csm->getByField(['contact_id' => $user_ids, 'name' => 'waid_invite_datetime'], 'contact_id');
        foreach ($users as &$user) {
            $user_id = $user['id'];
            $user['waid_invite_datetime'] = null;
            if (isset($waid_invite_datetime_rows[$user_id])) {
                $user['waid_invite_datetime'] = $waid_invite_datetime_rows[$user_id]['value'];
            }
        }
        unset($user);

        if (isset($users[$this->getUserId()])) {
            $user = $users[$this->getUserId()];
            unset($users[$this->getUserId()]);
            $users = [$this->getUserId() => $user] + $users;
        }

        return [$users, count($users)];
    }

    protected function workupUsers(&$users)
    {
        $team_exists = wa()->appExists('team');
        foreach ($users as &$user) {
            $user['name'] = waContactNameField::formatName($user);
            $user['link'] = $team_exists ? wa()->getAppUrl('team') . "u/{$user['login']}/info/" : '';
            $user['is_current_contact'] = $user['id'] == $this->getUserId();
        }
        unset($user);
    }

    protected function getUsersCount()
    {
        if (empty($this->users)) {
            $this->users = $this->getUsers();
        }

        return count($this->users);
    }

    private function getUsers()
    {
        if (isset($this->users)) {
            return $this->users;
        }
        $fields = [
            'name',
            'firstname',
            'middlename',
            'lastname',
            'login',
            'email',
            'is_user',
            'photo_url_32'
        ];
        $col = new waContactsCollection('search/is_user=1');
        $table_alias = $col->addLeftJoin('wa_contact_waid');
        $col->addField("{$table_alias}.contact_id", 'contact_id');
        $col->addField("{$table_alias}.create_datetime", 'waid_create_datetime');
        $col->addField("{$table_alias}.login_datetime", 'waid_login_datetime');
        $col->addField("{$table_alias}.token", 'waid_token');
        $col->orderBy('name');
        $this->users = $col->getContacts(implode(',', $fields), 0, self::USERS_LIMIT);

        return $this->users;
    }

    protected function getWebasystIDAuthUrl()
    {
        if ($this->cm->isConnected()) {
            $auth = new waWebasystIDWAAuth();
            return $auth->getUrl();
        }
        return '';
    }
}
