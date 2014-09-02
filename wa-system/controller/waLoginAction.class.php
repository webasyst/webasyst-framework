<?php

abstract class waLoginAction extends waViewAction
{
    public function execute()
    {

        // check remember enabled
        if (waRequest::method() == 'get') {
            $this->view->assign('remember', waRequest::cookie('remember', 1));
            $this->saveReferer();
        }

        if (wa()->getAuth()->isAuth()) {
            $this->afterAuth();
        }


        // check XMLHttpRequest (ajax)
        $this->checkXMLHttpRequest();

        if (wa()->getEnv() == 'frontend') {
            $this->checkAuthConfig();
        }

        $auth = wa()->getAuth();

        $error = '';
        // try auth
        try {
            if ($auth->auth()) {
                $this->logAction('login', wa()->getEnv());
                $this->afterAuth();
            }
        } catch (waException $e) {
            $error = $e->getMessage();
        }
        $this->view->assign('error', $error);
        // assign auth options
        $this->view->assign('options', $auth->getOptions());
        wa()->getResponse()->setTitle(_ws('Log in'));
    }

    protected function checkAuthConfig()
    {
        // check auth config
        $auth = wa()->getAuthConfig();
        if (!isset($auth['auth']) || !$auth['auth']) {
            throw new waException(_ws('Page not found'), 404);
        }
        /*
        // check auth app and url
        $login_url = wa()->getRouteUrl((isset($auth['app']) ? $auth['app'] : '').'/login');
        if (wa()->getConfig()->getRequestUrl(false) != $login_url) {
            $this->redirect($login_url);
        }
        */
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


    protected function checkXMLHttpRequest()
    {
        // Voodoo magic: reload page when user performs an AJAX request after session died.
        if (waRequest::isXMLHttpRequest() && (waRequest::param('secure') || wa()->getEnv() == 'backend')) {
            //
            // The idea behind this is quite complicated.
            //
            // When browser expects JSON and gets this response then the error handler is called.
            // Default error handler (see wa.core.js) looks for the wa-session-expired header
            // and reloads the page when it's found.
            //
            // On the other hand, when browser expects HTML, it's most likely to insert it to the DOM.
            // In this case <script> gets executed and browser reloads the whole layout to show login page.
            // (This is also the reason to use 200 HTTP response code here: no error handler required at all.)
            //
            header('wa-session-expired: 1');
            echo _ws('Session has expired. Please reload current page and log in again.').'<script>window.location.reload();</script>';
            exit;
        }
    }

    protected function afterAuth()
    {
        $url = $this->getStorage()->get('auth_referer');
        $this->getStorage()->del('auth_referer');
        if (!$url) {
            $url = waRequest::param('secure') ? $this->getConfig()->getCurrentUrl() : wa()->getAppUrl();
        }
        $this->redirect($url);
    }
}
