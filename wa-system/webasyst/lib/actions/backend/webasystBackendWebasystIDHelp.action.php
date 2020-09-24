<?php

class webasystBackendWebasystIDHelpAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign([
            'webasyst_id_settings_url' => $this->getWebasystIDSettingsUrl(),
            'webasyst_id_auth_url' => $this->getWebasystIDAuthUrl(),
            'connected_to_webasyst_id' => $this->isConnectedToWebasystID(),
            'is_super_admin' => wa()->getUser()->isAdmin('webasyst')
        ]);
    }

    protected function getWebasystIDSettingsUrl()
    {
        $user = $this->getUser();

        // notice, user can be not authorized yet, in this case in dialog not show "Connect" link/button
        if (!$user->isAuth()) {
            return '';
        }

        return wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/waid/';
    }

    /**
     * Get link to authorize current user into webasyst ID that will be shown in Webasyst ID announcement banner
     * This method returns empty string in case when announcement can't be shown
     * @return string
     * @throws waException
     */
    protected function getWebasystIDAuthUrl()
    {
        if (!$this->isConnectedToWebasystID()) {
            return '';
        }

        $user = $this->getUser();

        // notice, user can be not authorized yet, in this case in dialog not show "Connect" link/button
        if (!$user->isAuth()) {
            return '';
        }

        // user already bound with webasyst contact id
        $webasyst_contact_id = $user->getWebasystContactId();
        if ($webasyst_contact_id) {
            return '';
        }

        // announcement closed by x link
        if ($user->getSettings('webasyst', 'webasyst_id_announcement_close')) {
            return '';
        }

        $auth = new waWebasystIDWAAuth();
        return $auth->getUrl();
    }

    /**
     * Is installation connected to webasyst ID
     * @return bool
     */
    protected function isConnectedToWebasystID()
    {
        // client (installation) not connected
        $auth = new waWebasystIDWAAuth();
        return $auth->isClientConnected();
    }
}
