<?php

class webasystSettingsWaIDAction extends webasystSettingsViewAction
{
    /**
     * @var waWebasystIDClientManager
     */
    protected $cm;

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
            'upgrade_all' => (bool)$this->getRequest()->get('upgrade_all'),
            'webasyst_id_auth_url' => $this->getWebasystIDAuthUrl(),
            'is_user_bound_to_webasyst_id' => (bool)wa()->getUser()->getWebasystContactId(),
        ]);
    }

    protected function getConnectedUsers()
    {
        $col = new waContactsCollection("search/is_user=1");
        $t = $col->addJoin('wa_contact_waid');
        $col->addField("{$t}.create_datetime", "waid_create_datetime");
        $col->addField("{$t}.login_datetime", "waid_login_datetime");
        $col->addField("{$t}.token", "waid_token");

        $count = $col->count();
        $users = $col->getContacts("firstname,middlename,lastname,name,login,is_user,email,photo_url_32", 0, $count);

        $this->workupUsers($users);

        $tm = new waWebasystIDAccessTokenManager();

        foreach ($users as &$user) {
            $access_token = $user['waid_token'];
            $info = $tm->extractTokenInfo($access_token);
            $user['webasyst_id'] = '';
            if (!empty($info['email'])) {
                $user['webasyst_id'] = $info['email'];
            }
        }
        unset($user);

        return [$users, $count];
    }

    protected function getNotConnectedUsers()
    {
        $col = new waContactsCollection("search/is_user=1");
        $col->addLeftJoin('wa_contact_waid', null, ':table.contact_id IS NULL');
        $col->orderBy('name');
        $count = $col->count();
        $users = $col->getContacts("firstname,middlename,lastname,name,login,is_user,email,photo_url_32", 0, $count);
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

        return [$users, $count];
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
        $cm = new waContactModel();
        return $cm->countByField(['is_user' => 1]);
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
