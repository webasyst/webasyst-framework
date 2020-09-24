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
    protected $auth_result = [];

    public function __construct($params = null)
    {
        parent::__construct($params);

        $this->provider_id = isset($params['provider_id']) ? $params['provider_id'] : null;
        $this->auth_result = isset($params['result']) && is_array($params['result']) ? $params['result'] : [];
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
        // we has result injected in params (see waOAuthController)
        $this->view->assign([
            'result' => $this->auth_result,
            'redirect_url' => $this->getReferrerUrl()
        ]);

        $this->template = wa()->getAppPath('templates/actions/oauth/', 'webasyst').'OAuthWebasystID.html';
    }

    protected function defaultCase()
    {
        $this->template = wa()->getAppPath('templates/actions/oauth/', 'webasyst').'OAuth.html';
    }

    protected function getReferrerUrl()
    {
        $url = $this->getRequest()->get('referrer_url', '', waRequest::TYPE_STRING_TRIM);

        if (waUtils::isUrlSafeBase64Encoded($url)) {
            $url = waUtils::urlSafeBase64Decode($url);
        }

        return strlen($url) > 0 ? $url : wa()->getConfig()->getBackendUrl(true);
    }
}
