<?php

class waDispatch
{
    protected $config;
    protected $system;

    public function __construct(waSystem $system)
    {
        $this->config = $system->getConfig();
        $this->system = $system;
    }

    public function dispatch()
    {
        try {
            if (waConfig::get('is_template')) {
                return;
            }

            // Request URL with no '?' and GET parameters
            $request_url = $this->config->getRequestUrl(true, true);

            $environment = $this->system->getEnv();
            if ($environment !== 'cli') {
                if ($request_url === 'robots.txt' || $request_url === 'favicon.ico' || $request_url == 'apple-touch-icon.png') {
                    $this->dispatchStatic($request_url);
                }
            }

            if ($environment == 'backend') {
                $this->dispatchBackend($request_url);
            } else {
                $this->dispatchFrontend($request_url);
            }

        } catch (Exception $e) {
            if (!waSystemConfig::isDebug() && !in_array($e->getCode(), array(404, 403))) {
                $log = array(wa()->getConfig()->getRequestUrl());
                if (waRequest::method() == 'post') {
                    $log[] = "POST ".wa_dump_helper(ref(waRequest::post()));
                }
                $log[] = "Uncaught exception ".get_class($e).":";
                $log[] = $e->getMessage()." (".$e->getCode().")";
                $log[] = $e instanceof waException ? $e->getFullTraceAsString() : $e->getTraceAsString();
                waLog::log(join("\n", $log));
            }
            if (class_exists('waException')) {
                if (!$e instanceof waException) {
                    $e = new waException($e->getMessage(), $e->getCode(), $e);
                }
                $e->sendResponseCode();
            }
            print $e;
        }
    }

    private function dispatchStatic($file)
    {
        $this->redirectToHttps(waRouting::getDomainConfig('ssl_all'));
        $domain = waRequest::server('HTTP_HOST');
        $u = trim($this->config->getRootUrl(false, true), '/');
        if ($u) {
            $domain .= '/'.$u;
        }
        $path = waConfig::get('wa_path_data').'/public/site/data/'.$domain.'/'.$file;
        if (!file_exists($path)) {
            if (substr($domain, 0, 4) == 'www.') {
                $domain2 = substr($domain, 4);
            } else {
                $domain2 = 'www.'.$domain;
            }
            $path = waConfig::get('wa_path_data').'/public/site/data/'.$domain2.'/'.$file;
        }

        // check alias
        if (!file_exists($path)) {
            $routes = $this->config->getConfigFile('routing');
            if (!empty($routes[$domain]) && is_string($routes[$domain])) {
                $path = waConfig::get('wa_path_data').'/public/site/data/'.$routes[$domain].'/'.$file;
            } elseif (!empty($routes[$domain2]) && is_string($routes[$domain2])) {
                $path = waConfig::get('wa_path_data').'/public/site/data/'.$routes[$domain2].'/'.$file;
            }
        }

        if (file_exists($path)) {
            $file_type = waFiles::getMimeType($file);
            header("Content-type: {$file_type}");
            @readfile($path);
        } else {
            header("HTTP/1.0 404 Not Found");
        }
        exit;
    }

    private function dispatchBackend($request_url)
    {
        $this->redirectToHttps(waRouting::getDomainConfig('ssl_all'));

        // Publicly available dashboard?
        $url = explode("/", $request_url);
        if (ifset($url[1]) == 'dashboard') {
            wa('webasyst', 1)->getFrontController()->execute(null, 'dashboard', 'tv');
            return;
        }

        // Access to help information action about webasyst ID
        if (waRequest::get('module') === 'backend' && waRequest::get('action') === 'webasystIDHelp') {
            wa('webasyst', 1)->getFrontController()->execute(null, waRequest::get('module'), waRequest::get('action'));
            return;
        }

        // Access to backend without being logged in as is_user > 0
        // -> show login form
        if (!$this->system->getUser()->isAuth()) {
            wa('webasyst', 1)->getFrontController()->execute(null, 'login', waRequest::get('action'), true);
            return;
        }

        // Determine active application
        $url = explode("/", $request_url);
        $app = isset($url[1]) && ($url[1] != 'index.php') ? $url[1] : 'webasyst';
        if (!$app) {
            $app = 'webasyst';
        }

        if (!$this->system->appExists($app)) {
            if (wa('webasyst', 1)->event('backend_dispatch_miss', $app)) {
                return;
            }
            throw new waException("Page not found", 404);
        }

        // Make sure user has access to active app
        if ($app != 'webasyst' && !$this->system->getUser()->getRights($app, 'backend')) {
            throw new waRightsException('Access to this app denied', 403);
        }

        // Init system and app
        wa('webasyst');
        $wa_app = wa($app, 1);

        // Check CSRF protection token if current active app enabled it
        if ($wa_app->getConfig()->getInfo('csrf') && waRequest::method() == 'post') {
            if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
                $csrf_exception_message = _ws('Anti-CSRF protection.');

                if (!strlen(waRequest::post('_csrf'))) {
                    $csrf_exception_message .= "\n" . _ws('This may be caused by a server error, or by a limitation on the allowed number of POST variables or their values size. Try to increase the values of “max_input_vars” and “post_max_size” parameters in PHP configuration or other similar parameters in you web server configuration.');
                }

                throw new waException($csrf_exception_message, 403);
            }
        }

        // Pass through to FrontController of an active app
        $wa_app->getFrontController()->dispatch();
    }

    private function dispatchFrontend($request_url)
    {
        // Payment callback?
        if (!strncmp($request_url, 'payments.php/', 13)) {
            $url = substr($request_url, 13);
            if (preg_match('~^([a-z0-9_]+)~i', $url, $m) && !empty($m[1])) {
                $module_id = $m[1];
                waRequest::setParam('module_id', $module_id);
                waRequest::setParam('no_domain_www_redirect', true);
                wa('webasyst', 1)->getFrontController()->execute(null, 'payments');
            }
            return;
        }

        $this->redirectToHttps(waRouting::getDomainConfig('ssl_all'));

        // Sitemap?
        if (preg_match('/^sitemap-?([a-z0-9_]+)?(-([0-9]+))?.xml$/i', $request_url, $m)) {
            $app_id = isset($m[1]) ? $m[1] : 'webasyst';
            if ($this->system->appExists($app_id)) {
                wa($app_id, 1);
                $class = $app_id.'SitemapConfig';
                if (class_exists($class)) {
                    /** @var $sitemap waSitemapConfig */
                    $sitemap = new $class();
                    $sitemap->display(ifempty($m[3], 1));
                    return;
                }
            }

            throw new waException("Page not found", 404);
        }

        // Shipping callback?
        if (false && !strncmp($request_url, 'shipping.php/', 13)) {
            $url = substr($request_url, 13);
            if (preg_match('~^([a-z0-9_]+)~i', $url, $m) && !empty($m[1])) {
                $module_id = $m[1];
                waRequest::setParam('module_id', $module_id);
                waRequest::setParam('no_domain_www_redirect', true);
                wa('webasyst', 1)->getFrontController()->execute(null, 'shipping');
            }
            return;
        }

        // Redirect to HTTPS if set up in domain params
        if (!waRequest::isHttps() && waRouting::getDomainConfig('ssl_all')) {
            $domain = $this->system->getRouting()->getDomain(null, true);
            $url = 'https://'.$this->system->getRouting()->getDomainUrl($domain).'/'.$this->config->getRequestUrl();
            $this->system->getResponse()->redirect($url, 301);
            return;
        }

        // Captcha?
        if (preg_match('/^([a-z0-9_]+)?\/?captcha\.php$/i', $request_url, $m)) {
            $app_id = isset($m[1]) ? $m[1] : 'webasyst';
            if ($this->system->appExists($app_id)) {
                $captcha = wa($app_id, 1)->getCaptcha(array('app_id' => $app_id));
                $captcha->display();
                return;
            }

            throw new waException("Page not found", 404);
        }

        // Oauth?
        if (!strncmp($request_url, 'oauth.php', 9)) {
            $app_id = $this->system->getStorage()->get('auth_app');
            if (!$app_id) {
                $app_id = waRequest::get('app', null, 'string');
            }
            if ($app_id && !$this->system->appExists($app_id)) {
                throw new waException("Page not found", 404);
            }
            $app_system = wa($app_id, 1);
            if (!class_exists($app_id.'OAuthController')) {
                $app_system = wa('webasyst', 1);
            }
            $app_system->getFrontController()->execute(null, 'OAuth');
            return;
        }

        // Push?
        if (preg_match('~^push\.php\/([a-z0-9_]+)\/~i', $request_url, $m)) {
            $push_adapters = wa()->getPushAdapters();
            if (!empty($m[1]) && !empty($push_adapters[$m[1]])) {
                $action = preg_replace('~^push\.php\/([a-z0-9_]+)\/~i', '', $request_url);
                $push = $push_adapters[$m[1]];
                $push->dispatch($action);
                return;
            }

            throw new waException("Page not found", 404);
        }

        // One-time auth app token?
        if (!strncmp($request_url, 'link.php/', 9)) {
            $token = strtok(substr($request_url, 9), '/?');
            $token = urldecode($token);
            if ($token) {
                $app_token_model = new waAppTokensModel();
                $row = $app_token_model->getById($token);
                if ($row) {
                    if ($row['expire_datetime'] && strtotime($row['expire_datetime']) < time()) {
                        $app_token_model->purge();
                    } else {
                        wa($row['app_id'], true)->getConfig()->dispatchAppToken($row);
                        return;
                    }
                }
            }

            throw new waException("Page not found", 404);
        }

        // Therefore, this is a regular frontend pageview.
        // Run it through the routing, redirecting to backend if no routing is set up.
        $route_found = $this->system->getRouting()->dispatch();
        if (!$route_found) {
            $this->system->getResponse()->redirect($this->config->getBackendUrl(true), 302);
            return;
        }

        $this->redirectToHttps(waRequest::param('ssl_all'));

        // Active application determined by the routing
        $app = waRequest::param('app', null, 'string');
        if (!$app) {
            $app = 'webasyst';
        }

        // Is this a logout?
        $logout_url = waRequest::get('logout', null, 'string');
        if ($logout_url !== null) {
            $contact_id = $this->system->getUser()->getId();
            if ($contact_id) {
                // logout user
                $this->system->getUser()->logout();

                // logging logout
                if (!class_exists('waLogModel')) {
                    wa('webasyst');
                }
                $log_model = new waLogModel();
                $log_model->insert(array(
                    'app_id'     => $app,
                    'contact_id' => $contact_id,
                    'datetime'   => date("Y-m-d H:i:s"),
                    'params'     => 'frontend',
                    'action'     => 'logout',
                ));
            } else {
                // We destroy session even if user is not logged in.
                // This clears session-based pseudo-auth for many apps.
                wa()->getStorage()->destroy();
                // Do not allow custom URL in this case
                // because of redirection-based phishing attacks
                $logout_url = null;
            }

            // Make sure redirect is to the same domain
            if (!empty($logout_url)) {
                $domain = $this->system->getRouting()->getDomain(null, true);
                $next_domain = @parse_url($logout_url, PHP_URL_HOST);
                if ($next_domain && $domain !== $next_domain) {
                    $logout_url = null;
                }
            }
            // make redirect after logout
            if (empty($logout_url)) {
                $logout_url = $this->config->getRequestUrl(false, true);
            }
            $this->system->getResponse()->redirect($logout_url);
            return;
        }

        // Initialize active application
        $app_system = wa($app, 1);

        // Access to a secure area of the frontend without being logged in
        // -> show login form
        if (!$this->system->getUser()->isAuth() && (waRequest::param('secure') || waRequest::param('auth'))) {
            $auth = $this->system->getAuthConfig();
            if (!empty($auth['app'])) {
                $app_system = wa($auth['app'], 1);
            }
            $app_system->login();
            return;
        }

        // CSRF protection for secure parts of the frontend
        if (waRequest::param('secure') && waRequest::method() == 'post' && $app_system->getConfig()->getInfo('csrf')) {
            if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
                throw new waException('CSRF Protection', 403);
            }
        }

        // All seems fine, pass the request to FrontController of an active app
        $app_system->getFrontController()->dispatch();
    }

    public function dispatchCli($argv)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        $params = array();
        $app = $argv[1];
        $class = $app.ucfirst(ifset($argv[2], 'help'))."Cli";
        $argv = array_slice($argv, 3);
        while ($arg = array_shift($argv)) {
            if (mb_substr($arg, 0, 2) == '--') {
                $key = mb_substr($arg, 2);
            } elseif (mb_substr($arg, 0, 1) == '-') {
                $key = mb_substr($arg, 1);
            } else {
                $params[] = $arg;
                continue;
            }
            $params[$key] = trim(array_shift($argv));
        }
        waRequest::setParam($params);
        // Load system
        waSystem::getInstance('webasyst');

        if (!$this->system->appExists($app)) {
            throw new waException("App ".$app." not found", 404);
        }

        // Load app
        waSystem::getInstance($app, null, true);
        $class_exists = class_exists($class);
        $event_params = array(
            'app' => $app,
            'class' => $class,
            'exists' => $class_exists,
        );
        wa('webasyst')->event('cli_started', $event_params);

        $successful_execution = false;
        if ($class_exists) {
            try {
                /** @var $cli waCliController */
                $cli = new $class();
                $cli->run();
                $successful_execution = true;
            } catch (Exception $e) {
                $event_params['exception'] = $e;
                if (!$e instanceof waException) {
                    $e = new waException($e);
                }
                waLog::log($e, 'cli.log');
            }
        } else {
            waLog::log(new waException("Class ".$class." not found"), 'cli.log');
        }

        $event_params['successful_execution'] = $successful_execution;
        wa('webasyst')->event('cli_finished', $event_params);
    }

    /**
     * Redirect to HTTPS if set up in domain or route params
     * @param mixed $ssl_all
     * @throws waException
     */
    private function redirectToHttps($ssl_all)
    {
        if (!waRequest::isHttps() && $ssl_all) {
            $domain = $this->system->getRouting()->getDomain(null, true, false);
            $url = 'https://'.$this->system->getRouting()->getDomainUrl($domain).'/'.$this->config->getRequestUrl();
            $this->system->getResponse()->redirect($url, 301);
            return;
        }
    }
}