<?php

class waAPIController
{

    /**
     * Format of the response - xml or json, by default JSON
     */
    protected $format = 'JSON';

    protected $known_formats = array('XML', 'JSON');


    protected function response($response, $code = null)
    {
        if ($code) {
            wa()->getResponse()->setStatus($code);
        }
        if ($this->format == 'XML') {
            wa()->getResponse()->addHeader('Content-type', 'text/xml; charset=utf-8');
        } elseif ($this->format == 'JSON') {
            $callback = (string)waRequest::get('callback', false);
            // for JSONP
            if ($callback) {
                wa()->getResponse()->setStatus(200);
                wa()->getResponse()->addHeader('Content-type', 'text/javascript; charset=utf-8');
                echo $callback.'(';
            } else {
                wa()->getResponse()->addHeader('Content-type', 'application/json; charset=utf-8');
            }
        }
        wa()->getResponse()->sendHeaders();
        echo waAPIDecorator::factory($this->format)->decorate($response);
        if (!empty($callback)) {
            echo ');';
        }
    }

    public function dispatch()
    {
        // Make sure API is enabled
        if (wa('webasyst')->getConfig()->getOption('disable_api')) {
            $msg = wa('webasyst')->getConfig()->getOption('disable_api_message');
            throw new waAPIException('disabled', ifempty($msg, 'API is disabled'), 404);
        }

        // Redirect to HTTPS if set up in domain params
        if (!waRequest::isHttps() && waRouting::getDomainConfig('ssl_all')) {
            $domain = wa()->getRouting()->getDomain(null, true);
            $url = 'https://'.wa()->getRouting()->getDomainUrl($domain).'/'.wa()->getConfig()->getRequestUrl();
            wa()->getResponse()->redirect($url, 301);
            return;
        }

        $request_url = trim(wa()->getConfig()->getRequestUrl(true, true), '/');
        if ($request_url === 'api.php/auth') {
            $user = wa()->getUser();
            if (waRequest::post('cancel')) {
                $url = waRequest::get('redirect_uri', '', 'string');
                if (waRequest::get('response_type', 'code', 'string') == 'token') {
                    wa()->getResponse()->redirect($url.'#error=access_denied');
                } else {
                    if ($url) {
                        wa()->getResponse()->redirect($url.(strpos($url, '?') === false ? '?' : '&').'error=access_denied');
                    } else {
                        throw new waAPIException('access_denied', "You've denied access to ".htmlspecialchars(waRequest::get('client_name')), 403);
                    }
                }
            } else if (!$user->isAuth()) {
                wa()->getFrontController()->execute(null, 'login');
            } else {
                wa()->getFrontController()->execute(null, 'api', 'auth');
            }
        } elseif ($request_url == 'api.php/token') {
            wa()->getFrontController()->execute(null, 'api', 'token');
        } elseif ($request_url == 'api.php/revoke') {
            $this->checkToken();
            wa()->getFrontController()->execute(null, 'api', 'revoke');
        } elseif ($request_url == 'api.php/token-headless') {
            wa()->getFrontController()->execute(null, 'api', 'tokenHeadless');
        } elseif ($request_url === 'api.php') {
            $this->execute(waRequest::get('app'), waRequest::get('method'));
        } else {
            $parts = explode('/', $request_url);
            if (count($parts) == 3) {
                $this->execute($parts[1], $parts[2]);
            } elseif (count($parts) == 2 && strpos($parts[1], '.') !== false) {
                $parts = explode('.', $parts[1], 2);
                $this->execute($parts[0], $parts[1]);
            } else {
                throw new waAPIException('invalid_request', 'Malformed request or server misconfiguration. Request URL: '.htmlspecialchars($request_url), 400);
            }
        }
    }


    protected function execute($app, $method_name)
    {
        if ($format = waRequest::get('format')) {
            $format = strtoupper($format);
            if (!in_array($format, array('JSON', 'XML'))) {
                $this->response(array(
                    'error'             => 'invalid_request',
                    'error_description' => 'Invalid response format: '.$format
                ));
                return;
            }
            $this->format = $format;
        }
        // check access token and scope
        $token = $this->checkToken();

        // check app access
        if (!waSystem::getInstance()->appExists($app)) {
            throw new waAPIException('app_not_installed', 'App is not installed ('.$app.')', 400, [
                'app' => $app
            ]);
        }
        if (wa()->getUser()->getRights($app, 'backend') <= 0) {
            throw new waAPIException('access_denied', 403);
        }

        // check scope
        $scope = explode(',', $token['scope']);
        if (!in_array($app, $scope)) {
            throw new waAPIException('access_denied', 403);
        }

        // init app
        waSystem::getInstance($app, null, true);

        $class_name = $app.implode('', array_map('ucfirst', explode('.', $method_name)))."Method";

        if (!class_exists($class_name)) {
            throw new waAPIException('invalid_method', 'Unknown method: '.$app.'.'.htmlspecialchars($method_name), 404);
        }

        /**
         * Execute method of the API
         * @var waAPIMethod $method
         */
        $method = new $class_name();
        $this->response($method->getResponse());
    }

    protected function checkToken()
    {
        $token = waRequest::request('access_token', null, 'string');
        if (!$token) {
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                $token = ifset($headers, 'Authorization', null);
            }
            if (!$token) {
                $token = waRequest::server('HTTP_AUTHORIZATION', null, 'string');
            }
            if ($token) {
                $token = preg_replace('~^(Bearer\s)~ui', '', $token);
            }
        }
        if (!$token) {
            throw new waAPIException('invalid_request', 'Required parameter is missing: access_token', 400);
        }

        $tokens_model = new waApiTokensModel();
        $data = $tokens_model->getById($token);
        if (!$data) {
            throw new waAPIException('invalid_token', 'Invalid access token', 401);
        }
        if ($data['expires'] && (strtotime($data['expires']) < time())) {
            throw new waAPIException('invalid_token', 'Access token has expired', 401);
        }

        // remember token usage time
        $tokens_model->updateLastUseDatetime($token);

        // auth user
        wa()->setUser(new waApiAuthUser($data['contact_id']));

        return $data;
    }
}
