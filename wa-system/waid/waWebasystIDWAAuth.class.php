<?php

/**
 * Class waWebasystIDAuth
 *
 * OAuth2 adapter for auth into WA backend by Webasyst ID service
 */
class waWebasystIDWAAuth extends waWebasystIDAuthAdapter
{
    /**
     * On-demand instance, use getter getClientManager
     * @see getClientManager
     * @var waWebasystIDClientManager
     */
    protected $cm;

    /**
     * @return waWebasystIDClientManager
     */
    protected function getClientManager()
    {
        if (!$this->cm) {
            $this->cm = new waWebasystIDClientManager();
        }
        return $this->cm;
    }

    /**
     * Auth method
     * It can throw waWebasystIDAuthException on some this oauth2 related issues
     * It can standard waException on some unexpected situations
     * And finally on success must return access token params, with which waOAuthController will be work further
     *
     * @return array $params - access token params
     *      - string $params['access_token'] [required] - access token itself (jwt)
     *      - int    $params['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $params['token_type'] [optional] - "bearer"
     *      - ... and maybe some other fields from Webasyst ID server
     *
     * @throws waException
     * @throws waWebasystIDAuthException
     * @throws waWebasystIDAccessDeniedAuthException
     *
     * If thrown waWebasystIDAuthException it is legit auth error, need to handle it
     */
    public function auth()
    {
        // error from webasyst ID server
        $error = waRequest::get('error');

        // auth code from webasyst ID server
        $code = waRequest::get('code');

        // auth server returns something be callback url
        if ($error || $code) {
            return $this->processAuthResponse();
        }

        // otherwise it is beginning of auth process, adapter didn't ask webasyst ID server yet
        // redirect to provider auth page
        $request_url = $this->getRedirectUri();
        wa()->getResponse()->redirect($request_url);
    }

    /**
     * Callback url - url of controller that will process response from oauth provider service
     * @param bool $absolute
     * @return string
     * @throws waException
     */
    public function getCallbackUrl($absolute = true)
    {
        $callback_url = parent::getCallbackUrl($absolute);

        if ($this->isBackendAuth()) {
            $callback_url .= '&backend_auth=' . waRequest::get('backend_auth');
        } elseif ($invite_auth = $this->isInviteAuth()) {
            $callback_url .= '&invite_token=' . $invite_auth;
        }

        $referrer_url = $this->getReferrerUrl();
        if ($referrer_url) {
            $callback_url .= '&referrer_url=' . $referrer_url;
        }

        // all other get params leave as it is

        $ignore = ['provider', 'type', 'backend_auth', 'invite_token', 'referrer_url', 'code', 'state'];
        $ignore = array_flip($ignore);
        foreach (waRequest::get() as $key => $value) {
            if (!isset($ignore[$key]) && $value) {
                $callback_url .= '&' . $key . '=' . $value;
            }
        }

        return $callback_url;
    }

    /**
     * Get url to auth to backend of installation by webasyst ID oauth
     * @param array $params - key-value map of params to mixin into url
     *      $params['referrer_url'] [optional] - url of referrer, i.e. url where come back after oauth flow. If missed (or empty) will be current request url
     * @return string
     * @throws waException
     */
    public function getBackendAuthUrl(array $params = [])
    {
        $params['backend_auth'] = 1;
        return $this->getAuthUrl($params);
    }

    /**
     * @param string $invite_token
     * @param array $params - key-value map of params to mixin into url
     *      $params['referrer_url'] [optional] - url of referrer, i.e. url where come back after oauth flow. If missed (or empty) will be current request url
     * @return string
     * @throws waException
     */
    public function getInviteAuthUrl($invite_token, array $params = [])
    {
        $params['invite_token'] = $invite_token;
        return $this->getAuthUrl($params);
    }

    public function getBindUrl(array $params = [])
    {
        return $this->getAuthUrl($params);
    }

    /**
     * Get url to auth to installation by webasyst ID oauth (backend_auth, invite)
     * @param array $params - key-value map of params to mixin into url
     *      $params['referrer_url'] [optional] - url of referrer, i.e. url where come back after oauth flow. If missed (or empty) will be current request url
     * @return string
     * @throws waException
     */
    protected function getAuthUrl(array $params = [])
    {
        $referrer_url = isset($params['referrer_url']) ? $params['referrer_url'] : null;

        if (!$referrer_url) {
            $url = wa()->getConfig()->getRequestUrl(false, false);
            $url = ltrim($url, '/');
            $domain = wa()->getConfig()->getDomain();

            if (waRequest::isHttps()) {
                $referrer_url = "https://{$domain}/{$url}";
            } else {
                $referrer_url = "http://{$domain}/{$url}";
            }
        }

        $referrer_url = waUtils::urlSafeBase64Encode($referrer_url);

        $params['referrer_url'] = $referrer_url;

        if ($this->getClientManager()->isBackendAuthForced()) {
            $params['mode'] = 'forced';
        }

        $params_str = http_build_query($params);

        return $this->getUrl() . '&' . $params_str;
    }

    /**
     * Is backend auth or primary auth (to bind user to Webasyst ID)
     * @return bool
     */
    public function isBackendAuth()
    {
        return (bool)waRequest::get('backend_auth');
    }

    /**
     * If auth into backend and if need to "remember me" into backend
     * @return bool
     */
    public function isRememberMe()
    {
        return waRequest::get('backend_auth') == 2;
    }

    /**
     * Is auth (sing up) into backend by invite link
     * @return string - if invite auth returns not empty invite auth token, otherwise empty string
     */
    public function isInviteAuth()
    {
        return waRequest::get('invite_token');
    }

    /**
     * @return array|null $dispatch - if array that must be of format
     *      string $dispatch['app'] -
     *      string $dispatch['module']
     *      string $dispatch['action'] [optional]
     *      ...other key-value pairs that would be interpreted by concrete app
     */
    public function getDispatchParams()
    {
        $dispatch = waRequest::get('dispatch');
        if (!$dispatch) {
            return null;
        }

        $dispatch = waUtils::urlSafeBase64Decode($dispatch);
        if (!is_string($dispatch) || strlen($dispatch) <= 0) {
            return null;
        }

        parse_str($dispatch, $result);
        if (!isset($result['app']) && !isset($result['module'])) {
            return null;
        }

        return $result;
    }

    /**
     * @return bool
     * @throws waDbException
     * @throws waException
     */
    public function isClientConnected()
    {
        return $this->getClientManager()->isConnected();
    }

    protected function getCredentials()
    {
        return $this->getClientManager()->getCredentials();
    }

    protected function getAuthScope()
    {
        return 'profile license:bind';
    }

    /**
     * Url for get auth code
     * @return string
     * @throws waException
     */
    protected function getAuthCodeUrl()
    {
        if (!$this->isClientConnected()) {
            return '';
        }
        return parent::getAuthCodeUrl();
    }

    /**
     * @return string
     * @throws waDbException
     * @throws waException
     */
    protected function getAccessTokenUrl()
    {
        if (!$this->isClientConnected()) {
            return '';
        }
        return parent::getAccessTokenUrl();
    }

    /**
     * @inheritDoc
     */
    public function getAccessToken($code, array $params = [])
    {
        if (!$this->isClientConnected()) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'client_not_connected',
                    'error_message' => 'Client is not connected'
                ]
            ];
        }

        return parent::getAccessToken($code);
    }

    public function getType()
    {
        return self::TYPE_WA;
    }

    /**
     * Save access token params to current user
     * Extract Webasyst ID contact id from access token and bind current user with Webasyst ID contact,
     *      so we can authorize in backend by Webasyst ID
     *
     * Save in `wa_contact_data` by fields:
     *      - 'webasyst_token_params' to store token params itself
     *      - 'webasyst_contact_id' to bind with Webasyst ID contact
     *
     * @param waContact $user
     * @param array $params - here is access token params with expected format:
     *      - string $params['access_token'] [required] - access token itself (jwt)
     *      - string $params['refresh_token'] [required] - refresh token to refresh access token
     *      - int    $params['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $params['token_type'] [optional] - "bearer"
     *      - ... and maybe some other fields from Webasyst ID server
     * @param bool $force - force renew binding even if this is conflict with existing binding
     * @return array $result
     *      bool  $result['status'] - bind status
     *
     *      string $result['details']['error_code']                         - if $result['status'] === FALSE
     *      string $result['details']['error_message']                      - if $result['status'] === FALSE
     *
     *      array $result['details']['webasyst_contact_info']               - info about webasyst contact, always returns
     *      array $result['details']['bound_contact_info'] [optional]       - info about bound contact (that is conflicted with current user), returns in case of conflict
     *      array $result['details']['current_user_info'] [optional]        - info about current user, returns in case of conflict
     *
     *      info is array of format:
     *          string $info['name']
     *          string $info['userpic']
     *          array $info['email'] - list of emails (list of assoc arrays)
     *          array $info['phone'] - list of phones (list of assoc arrays)
     *
     * @throws waException
     */
    public function bindUserWithWebasystContact(waContact $user, $params, $force = false)
    {
        // Extract Webasyst contact
        $m = new waWebasystIDAccessTokenManager();
        $token_info = $m->extractTokenInfo($params['access_token']);
        $contact_id = $token_info['contact_id'];

        // Found contact that already bound with this Webasyst contact
        $cwm = new waContactWaidModel();
        $bound_contact_id = $cwm->getBoundWithWebasystContact($contact_id);
        $bound_contact = new waContact($bound_contact_id);

        // one contact from current DB must be bound only with one Webasyst contact
        $is_conflict = $bound_contact->exists() && $user->getId() != $bound_contact->getId();

        $webasyst_contact_info = $this->getUserData($params);

        if ($is_conflict && !$force) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'already_bound',
                    'error_message' => _ws('Already bound'),
                    'webasyst_contact_info' => $webasyst_contact_info,
                    'bound_contact_info' => $this->getContactInfo(new waContact($bound_contact_id)),
                    'current_user_info' => $this->getContactInfo($user)
                ]
            ];
        }

        if ($is_conflict && $force) {
            $bound_contact->unbindWaid();
        }

        $user->bindWithWaid($contact_id, $params);

        return [
            'status' => true,
            'details' => [
                'webasyst_contact_info' => $webasyst_contact_info,
            ]
        ];
    }


    public function bindWithWebasystContact($params, $force = false)
    {
        return $this->bindUserWithWebasystContact(wa()->getUser(), $params, $force);
    }
}
