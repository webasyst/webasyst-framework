<?php

class webasystSettingsWaIDAction extends webasystSettingsViewAction
{
    /**
     * @var waContactsCollection
     */
    protected $collection;

    public function __construct($params = null)
    {
        parent::__construct($params);

        $col = new waContactsCollection("search/is_user=1");
        $t = $col->addJoin('wa_contact_waid');
        $col->addField("{$t}.create_datetime", "waid_create_datetime");
        $col->addField("{$t}.login_datetime", "waid_login_datetime");
        $this->collection = $col;
    }

    public function execute()
    {
        $this->view->assign([
            'is_connected' => $this->isConnected(),
            'connected_users' => $this->getAllConnectedUsers(),
            'connected_users_count' => $this->getConnectedUsersCount(),
            'users_count' => $this->getUsersCount()
        ]);
    }

    protected function isConnected()
    {
        $manager = new waWebasystIDClientManager();
        return $manager->isConnected();
    }

    protected function getAllConnectedUsers()
    {
        $users = $this->collection->getContacts("firstname,middlename,lastname,name,login,is_user,email,photo_url_32");

        $team_exists = wa()->appExists('team');

        foreach ($users as &$user) {
            $user['name'] = waContactNameField::formatName($user);
            $user['link'] = $team_exists ? wa()->getAppUrl('team')."u/{$user['login']}/info/" : '';
        }
        unset($user);

        return $users;
    }

    /**
     * @return waContactsCollection
     */
    protected function getCollection()
    {
        $col = new waContactsCollection("search/is_user=1");
        $t = $col->addJoin('wa_contact_waid');
        $col->addField("{$t}.create_datetime", "waid_create_datetime");
        $col->addField("{$t}.login_datetime", "waid_login_datetime");
        return $col;
    }

    protected function getConnectedUsersCount()
    {
        return $this->collection->count();
    }

    protected function getUsersCount()
    {
        $cm = new waContactModel();
        return $cm->countByField(['is_user' => 1]);
    }
}
