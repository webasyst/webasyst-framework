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
        if($this->format == 'XML'){
            wa()->getResponse()->addHeader('Content-type', 'text/xml; charset=utf-8');
        } elseif ($this->format == 'JSON') {
            $callback = (string)waRequest::get('callback', false);
            // for JSONP
            if ($callback) {
                wa()->getResponse()->setStatus(200);
                wa()->getResponse()->addHeader('Content-type', 'text/javascript; charset=utf-8');
                echo $callback .'(';
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
        $request_url = rtrim(wa()->getConfig()->getRequestUrl(true, true), '/');
        if ($request_url === 'api.php/auth') {
            $user = wa()->getUser();
            if (!$user->isAuth()) {
                wa()->getFrontController()->execute(null, 'login');
            } else {
                wa()->getFrontController()->execute(null, 'api', 'auth');
            }
        } elseif ($request_url == 'api.php/token') {
            wa()->getFrontController()->execute(null, 'api', 'token');
        } elseif ($request_url == 'api.php/revoke') {
            $this->checkToken();
            wa()->getFrontController()->execute(null, 'api', 'revoke');
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
                throw new waAPIException('invalid_request');
            }
        }
    }



    protected function execute($app, $method_name)
    {
        if ($format = waRequest::get('format')) {
            $format = strtoupper($format);
            if (!in_array($format, array('JSON', 'XML'))) {
                $this->response(array(
                    'error' => 'invalid_request',
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
            throw new waAPIException('invalid_request', 'Application '.$app.' not exists');
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
        $token = waRequest::request('access_token');
        if ($token) {
            $tokens_model = new waApiTokensModel();
            $data = $tokens_model->getById($token);
            if ($data) {
                if ($data['expires'] && (strtotime($data['expires']) < time())) {
                    throw new waAPIException('invalid_token', 'Access token has expired', 401);
                }
                // auth user
                wa()->setUser(new waUser($data['contact_id']));
                return $data;
            }
            throw new waAPIException('invalid_token', 'Invalid access token', 401);
        }
        throw new waAPIException('invalid_request', 'Required parameter is missing: access_token', 400);
    }
}