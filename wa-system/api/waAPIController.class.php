<?php
/**
 * API controller.
 * 
 * @package    Webasyst
 * @category   System/API
 * @author     Webasyst
 * @copyright  (c) 2011-2014 Webasyst
 * @license    LGPL
 */
class waAPIController 
{
    /**
     * @var  string  Format of the response
     */
    protected $format = 'JSON';

    /**
     * Displays response to request.
     * 
     * @param   mixed    $response
     * @param   integer  $code
     * @return  void
     */
    protected function response($response, $code = 0)
    {
        if ($code) {
            wa()->getResponse()->setStatus($code);
        }

        if($this->format == 'XML'){
            wa()->getResponse()->addHeader('Content-type', 'text/xml; charset=utf-8');
        } elseif ($this->format == 'JSON') {
            $callback = waRequest::get('callback', false);
            // for JSONP
            if ($callback) {
                wa()->getResponse()->setStatus(200);
                // @link http://en.wikipedia.org/wiki/JSONP correct MIME type is "application/javascript" for JSONP.
                wa()->getResponse()->addHeader('Content-type', 'application/javascript; charset=utf-8');
                echo $callback .'(';
            } else {
                // @link http://en.wikipedia.org/wiki/JSON The official Internet media type for JSON is application/json.
                wa()->getResponse()->addHeader('Content-type', 'application/json; charset=utf-8');
            }
        }

        wa()->getResponse()->sendHeaders();

        echo waAPIDecorator::getInstance($this->format)->decorate($response);

        if (!empty($callback)) {
            echo ');';
        }
    }

    /**
     * Dispatches response of controller to request.
     * 
     * @return  void
     * @throws  waAPIException
     */
    public function dispatch()
    {
        $url = rtrim(wa()->getConfig()->getRequestUrl(true, true), '/');
        if ($url == 'api.php/auth') {
            if (!wa()->getUser()->isAuth()) {
                wa()->getFrontController()->execute(null, 'login');
            } else {
                wa()->getFrontController()->execute(null, 'api', 'auth');
            }
        } elseif ($url == 'api.php/token') {
            wa()->getFrontController()->execute(null, 'api', 'token');
        } elseif ($url == 'api.php') {
            $this->execute(waRequest::get('app'), waRequest::get('method'));
        }

        $parts = explode('/', $url);
        $cnt_parts = count($cnt_parts);
        if ($cnt_parts == 3) {
            $this->execute($parts[1], $parts[2]);
        } elseif ($cnt_parts == 2 && strpos($parts[1], '.') !== false) {
            $parts = explode('.', $parts[1], 2);
            $this->execute($parts[0], $parts[1]);
        } else {
            throw new waAPIException('invalid_request');
        }
    }

    /**
     * Check secure token.
     * 
     * @return  array
     * @throws  waAPIException
     */
    protected function checkToken()
    {
        $token = waRequest::request('access_token');
        if (!$token) {
            throw new waAPIException('invalid_request', 'Required parameter is missing: access_token', 400);
        }
        
        $tokens_model = new waApiTokensModel;

        $data = $tokens_model->getById($token);
        if (!$data) {
            throw new waAPIException('invalid_token', 'Invalid access token', 401);
        }
        if ($data['expires'] && (strtotime($data['expires']) < time())) {
            throw new waAPIException('invalid_token', 'Access token has expired', 401);
        }

        // Auth user
        wa()->setUser(new waUser($data['contact_id']));

        return $data;
    }

    /**
     * Execute method of the API.
     * 
     * @param   string  $app     Application name
     * @param   string  $method  Name of called method
     * @return  void
     */
    protected function execute($app, $method)
    {
        // Check response format
        $format = waRequest::get('format');
        if ($format) {
            $format = strtoupper($format);
            if (!in_array($format, waAPIDecorator::getFormats())) {
                $this->response(array(
                    'error' => 'invalid_request',
                    'error_description' => 'Invalid response format: '.$format
                ));
                return;
            }
            $this->format = $format;
        }

        // Generate class name for API method
        $class = $app.implode(array_map('ucfirst', explode('.', $method))).'Method';
        // Check class
        if (!class_exists($class)) {
            throw new waAPIException('invalid_method', 'Unknown method: '.$app.'.'.$method, 404);
        }

        // Check access token and scope
        $token = $this->checkToken();

        // Check app access
        if (!waSystem::getInstance()->appExists($app)) {
            throw new waAPIException('invalid_request', 'Application '.$app.' not exists');
        }

        // Check scope
        $scope = explode(',', $token['scope']);
        if (!in_array($app, $scope)) {
            throw new waAPIException('access_denied', 403);
        }
        
        // Initialize application
        waSystem::getInstance($app, null, true);

        // Create method, instance of class waAPIMethod
        $method = new $class;
        
        // Displays response of method
        $this->response($method->getResponse());
    }
}
