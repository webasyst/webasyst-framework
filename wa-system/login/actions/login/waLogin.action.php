<?php

/**
 * Class waLoginAction
 *
 * Abstract action for login to frontend
 *
 * Must be called waFrontendLoginAction
 * But for backward compatibility with old Shop (and other apps) MUST be called waLoginAction
 *
 */
abstract class waLoginAction extends waBaseLoginAction
{
    /**
     * @var waDomainAuthConfig
     */
    protected $auth_config;

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->auth_config = waDomainAuthConfig::factory();
    }

    public function execute()
    {
        // Backward compatibility
        if (wa()->getRequest()->get('send_confirmation')) {
            $this->sendConfirmation();
            return;
        }
        parent::execute();
    }

    /**
     * Backward compatibility
     */
    protected function sendConfirmation()
    {
        $auth = wa()->getAuth();
        $login = $this->getData('login');
        $user_info = $auth->getByLogin($login);
        if (!$user_info) {
            die(_ws('Invalid login'));
        }

        $resend_confirmation_url = $this->auth_config->getSignUpUrl(array(
            'get' => array(
                'send_confirmation' => '1',
                'login' => $login
            )
        ));
        $this->redirect($resend_confirmation_url);
    }

    protected function checkAuthConfig()
    {
        if (!$this->auth_config->getAuth()) {
            throw new waException(_ws('Page not found'), 404);
        }
    }

    protected function saveReferer()
    {
        if (!waRequest::param('secure')) {
            $referer = waRequest::server('HTTP_REFERER');
            $root_url = wa()->getRootUrl(true);
            if ($root_url != substr($referer, 0, strlen($root_url))) {
                $this->getStorage()->del('auth_referer');
                return;
            }
            $referer = substr($referer, strlen($this->getConfig()->getHostUrl()));
            if (!in_array($referer, array(
                wa()->getRouteUrl('/login'),
                wa()->getRouteUrl('/forgotpassword'),
                wa()->getRouteUrl('/signup')
            ))) {
                $this->getStorage()->set('auth_referer', $referer);
            }
        }
    }

    protected function afterAuth()
    {
        if ($this->needRedirects()) {
            $this->redirectAfterAuth();
            return;
        }
        $this->assign('auth_status', 'ok');
        $this->assign('contact', $this->getUser());
    }

    protected function redirectAfterAuth()
    {
        $url = $this->getStorage()->get('auth_referer');
        $this->getStorage()->del('auth_referer');
        if (!$url) {
            $url = waRequest::param('secure') ? $this->getConfig()->getCurrentUrl() : wa()->getAppUrl();
        }
        $this->redirect($url);
    }

    /**
     * @param array $options
     * @return null
     */
    protected function getFormRenderer($options = array())
    {
        static $renderer;
        if (!$renderer) {
            $renderer = new waFrontendLoginForm($options);
        }
        return $renderer;
    }
}
