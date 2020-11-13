<?php

/**
 * Class webasystOAuthAction
 *
 * 2 different cases:
 *  For Webasyst ID oauth
 *      Close oauth popup and reload page that opened it OR redirect back to referrer_url
 *      Also in case of binding conflict (webasyst ID auth) render form for choose what to do
 *  For other oauth (default case)
 *      Close oauth popup
 */
class webasystOAuthAction extends waViewAction
{
    protected $provider_id;

    /**
     * @var array $auth_result - if it is webasyst ID auth then format of array:

     *  if auth flow not terminated by system error
     *      - string $auth_result['type'] - 'backend', 'invite', 'bind'
     *      - array $auth_result['result']
     *          - bool $auth_result['result']['status']
     *          - array $auth_result['result']['details']
     *
     * if system error happens
     *      - string $auth_result['type'] - 'error'
     *      - string $auth_result['error_msg'] - message of system error
     *
     * If user click cancel link in webasyst ID auth form
     *      - string $auth_result['type'] - 'access_denied'
     *
     */
    protected $auth_result = [];

    /**
     * If $this->auth_result['type'] is 'invite'
     * @var string
     */
    protected $invite_token = '';

    /**
     * webasystOAuthAction constructor.
     * @param array|null $params
     *      string $params['provider_id']
     *      array $params['result'] [optional] - see format of array in property $this->auth_result
     *      string $params['invite_token'] [optional] - if webasyst ID auth and 'invite' type
     */
    public function __construct($params = null)
    {
        parent::__construct($params);

        $this->provider_id = isset($params['provider_id']) ? $params['provider_id'] : null;
        $this->auth_result = isset($params['result']) && is_array($params['result']) ? $params['result'] : [];
        $this->auth_result['type'] = isset($this->auth_result['type']) ? $this->auth_result['type'] : '';
        $this->invite_token = isset($params['invite_token']) ? $params['invite_token'] : '';
    }

    public function execute()
    {
        if ($this->provider_id === waWebasystIDAuthAdapter::PROVIDER_ID) {
            $this->webasystIDAuthCase();
        } else {
            $this->defaultCase();
        }
    }

    protected function webasystIDAuthCase()
    {
        $cm = new waWebasystIDClientManager();
        $is_backend_auth_forced = $cm->isBackendAuthForced();

        $type = $this->auth_result['type'];

        // will need in case if backend user not bound while backend auth by webasyst ID is forced
        $auth = new waWebasystIDWAAuth();

        $redirect = $this->getRedirectInfo();

        $try_again_auth_url = '';
        if ($type === 'backend') {
            $try_again_auth_url = $auth->getBackendAuthUrl([
                'referrer_url' => $redirect['url'],         // need specify explicitly to not loose initial referrer url
                'change_user' => 1                          // will logged out from webasyst ID if go by this link
            ]);
        } elseif ($type === 'invite') {
            $try_again_auth_url = $auth->getInviteAuthUrl($this->invite_token, [
                'referrer_url' => $redirect['url'],         // need specify explicitly to not loose initial referrer url
                'change_user' => 1                          // will logged out from webasyst ID if go by this link
            ]);
        } elseif ($type === 'bind') {
            $try_again_auth_url = $auth->getBindUrl([
                'referrer_url' => $redirect['url'],         // need specify explicitly to not loose initial referrer url
                'change_user' => 1                          // will logged out from webasyst ID if go by this link
            ]);
        }

        $forced_login_url = wa()->getConfig()->getBackendUrl(true) . '?force_login_form=1';

        $this->view->assign([
            'result' => $this->auth_result, // we has result injected in params (see waOAuthController and waWebasystIDWAAuthController)
            'redirect' => $redirect,
            'is_backend_auth_forced' => $is_backend_auth_forced,
            'try_again_auth_url' => $try_again_auth_url,
            'domain' => wa()->getConfig()->getDomain(),
            'forced_login_url' => $forced_login_url
        ]);

        $this->template = wa()->getAppPath('templates/actions/oauth/', 'webasyst').'OAuthWebasystID.html';
    }

    protected function defaultCase()
    {
        $this->template = wa()->getAppPath('templates/actions/oauth/', 'webasyst').'OAuth.html';
    }

    /**
     * @return array $redirect
     *      - string $redirect['url']
     *      - string $redirect['error']['code'] [optional]
     *      - string $redirect['error']['message'] [optional]
     * @throws waException
     */
    protected function getRedirectInfo()
    {
        $url = $this->getRequest()->get('referrer_url', '', waRequest::TYPE_STRING_TRIM);

        if (waUtils::isUrlSafeBase64Encoded($url)) {
            $url = waUtils::urlSafeBase64Decode($url);
        }

        if (strlen($url) <= 0) {
            $url = wa()->getConfig()->getBackendUrl(true);
        }

        $auth = new waWebasystIDWAAuth();
        $dispatch_params = $auth->getDispatchParams();
        if (!$dispatch_params) {
            return [
                'url' => $url
            ];
        }

        $event_result = isset($this->auth_result['result']['details']['event_result']) ? $this->auth_result['result']['details']['event_result'] : [];
        $event_result = is_array($event_result) ? $event_result : [];
        foreach ($event_result as $listener_id => $result) {
            if ($listener_id != $dispatch_params['app'] || !isset($result['dispatch'])) {
                continue;
            }

            $parsed = $this->parseDispatchStructure($result['dispatch']);
            if (!$parsed) {
                continue;
            }

            if (empty($parsed['url'])) {
                $parsed['url'] = $url;
            }

            return $parsed;
        }

        return [
            'url' => $url
        ];
    }

    private function parseDispatchStructure($redirect)
    {
        if (!is_array($redirect)) {
            return null;
        }

        if (isset($redirect['url'])) {
            return [
                'url' => $redirect['url']
            ];
        }

        $error = [];

        if (isset($redirect['error'])) {
            if (isset($redirect['error']['code'])) {
                $error['code'] = $redirect['error']['code'];
            }
            if (isset($redirect['error']['message'])) {
                $error['message'] = $redirect['error']['message'];
            }
        }

        if ($error) {
            return [
                'error' => $error,
            ];
        }

        return null;
    }
}
