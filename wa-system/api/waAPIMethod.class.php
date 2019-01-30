<?php

class waAPIMethod
{

    protected $method = 'GET';
    protected $response;

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
                throw new waAPIException('invalid_request', 'Method '.$request_method.' not allowed', 405);
            }
        }

        $this->execute();
        return $this->response;
    }

    public function get($name, $required = false)
    {
        $v = waRequest::get($name);
        if ($required && !$v) {
            throw new waAPIException('invalid_param', 'Required parameter is missing: '.$name, 400);
        }
        return $v;
    }

    public function post($name, $required = false)
    {
        $v = waRequest::post($name);
        if ($required && !$v) {
            throw new waAPIException('invalid_param', 'Required parameter is missing: '.$name, 400);
        }
        return $v;
    }

    public function getRights($name)
    {
        return wa()->getUser()->getRights(wa()->getApp(), $name);
    }
}
