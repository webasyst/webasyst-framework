<?php
/**
 * Own profile editor for users who don't have access to Team app.
 */
class webasystProfilePageAction extends waViewAction
{
    public function execute()
    {
        $user = wa()->getUser();
        $user->load();

        /*
         * @event backend_personal_profile
         */
        $params = array(
            'user' => $user,
            'top' => $user->getTopFields(),
        );
        $backend_personal_profile = wa()->event(array('webasyst', 'backend_personal_profile'), $params);

        // Redirect to old Contacts app if user has access to it
        if (wa()->appExists('contacts') && wa()->getUser()->getRights('contacts', 'backend')) {
            wa('contacts', 1)->getResponse()->redirect(wa()->getUrl()."#/contact/{$user['id']}/");
        }

        $this->view->assign(array(
            'backend_personal_profile' => $backend_personal_profile,
            'top' => $params['top'],
            'user' => $user,
            'webasyst_id_auth_url' => $this->getWebasystIDAuthUrl($user),
            'customer_center_auth_url' => $this->getCustomerCenterAuthUrl(),
            'webasyst_id_email' => $this->getWebasystIDEmail()
        ));
    }

    /**
     * @param waContact $user
     * @return string
     * @throws waDbException
     * @throws waException
     */
    protected function getWebasystIDAuthUrl($user)
    {
        // if installation is not connected yet
        $m = new waWebasystIDClientManager();
        if (!$m->isConnected()) {
            return '';
        }

        // profile is already bound with webasyst ID
        if ($user->getWebasystContactId() > 0) {
            return '';
        }

        $auth = new waWebasystIDWAAuth();
        return $auth->getUrl();
    }

    /**
     * @return bool
     * @throws waException
     */
    protected function getCustomerCenterAuthUrl()
    {
        $access_token = $this->getWebasystAuthAccessToken();
        if (!$access_token) {
            return '';
        }
        return wa()->getConfig()->getBackendUrl(true) . '?module=profile&action=customer';
    }

    /**
     * Email of webasyst ID contact
     * @return mixed|string
     * @throws waDbException
     * @throws waException
     */
    protected function getWebasystIDEmail()
    {
        $access_token = $this->getWebasystAuthAccessToken();
        if (!$access_token) {
            return '';
        }
        $atm = new waWebasystIDAccessTokenManager();
        $info = $atm->extractTokenInfo($access_token);
        return $info['email'];
    }

    /**
     * Get access token if supports 'auth' scope
     * @return array|mixed
     * @throws waDbException
     * @throws waException
     */
    protected function getWebasystAuthAccessToken()
    {
        $token_params = $this->getUser()->getWebasystTokenParams();
        if ($token_params) {
            $access_token = $token_params['access_token'];
            $atm = new waWebasystIDAccessTokenManager();
            $supports = $atm->isScopeSupported('auth', $access_token);
            if ($supports) {
                return $access_token;
            }
        }
        return [];
    }


}
