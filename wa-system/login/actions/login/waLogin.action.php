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
    protected $env = 'frontend';

    /**
     * waLoginAction constructor.
     * @param null $params
     */
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->auth_config = waDomainAuthConfig::factory();
    }

    /**
     * Entry point of action
     * @throws waAuthException
     * @throws waException
     */
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

    /**
     * Need be turn ON auth in domain auth config -- if OFF throw exception that stops action
     * @return mixed|void
     * @throws waException
     */
    protected function checkAuthConfig()
    {
        if (!$this->auth_config->getAuth()) {
            throw new waException(_ws('Page not found'), 404);
        }
    }

    /**
     * Save referrer, to redirect there after logging in (in afterAuth() method)
     * Ignore auth related URLs - cause we do not need redirect back to that urls
     * @return mixed|void
     */
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

    /**
     * After auth logic
     * Need redirects - redirects
     * No need - just send some vars to client
     * @return mixed|void
     */
    protected function afterAuth()
    {
        if ($this->needRedirects()) {
            $this->redirectAfterAuth();
            return;
        }
        $this->assign('auth_status', 'ok');
        $this->assign('contact', $this->getUser());
    }

    /**
     * Redirect back to previous remembered page
     * @see saveReferer
     */
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
     * Login form
     * @param array $options
     * @return null|waFrontendLoginForm
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
