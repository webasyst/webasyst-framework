<?php

class waAPIMethod
{

    protected $method = 'GET';
    protected $response;
    protected $http_status_code = 200;

    public function __construct()
    {

    }

    public function execute()
    {

    }


    public function getResponse($internal = false)
    {
        if (!$internal) {
            // check request method
            $request_method = strtoupper(waRequest::method());
            if ((is_array($this->method) && !in_array($request_method, $this->method)) ||
                (!is_array($this->method) && $request_method != $this->method)
            ) {
                throw new waAPIException('invalid_request', sprintf(_ws('Method %s not allowed'), $request_method), 405);
            }
        }

        $this->execute();
        return $this->response;
    }

    public function getStatusCode()
    {
        return $this->http_status_code;
    }

    public function get($name, $required = false)
    {
        $v = waRequest::get($name);
        if ($required && !$v) {
            throw new waAPIException('invalid_param', sprintf(_ws('Required parameter is missing: “%s”.'), $name), 400);
        }
        return $v;
    }

    public function post($name, $required = false)
    {
        $v = waRequest::post($name);
        if ($required && !$v) {
            throw new waAPIException('invalid_param', sprintf(_ws('Required parameter is missing: “%s”.'), $name), 400);
        }
        return $v;
    }

    public function getRights($name)
    {
        return wa()->getUser()->getRights(wa()->getApp(), $name);
    }
}
