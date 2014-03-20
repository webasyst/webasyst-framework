<?php
/**
 * Abstract API method.
 * 
 * @package    Webasyst
 * @category   System/API
 * @author     Webasyst
 * @copyright  (c) 2011-2014 Webasyst
 * @license    LGPL
 */
abstract class waAPIMethod
{
    /**
     * @var  string|array  Method of data sending or an array of methods
     */
    protected $method = 'GET';

    /**
     * @var  mixed  Response data
     */
    protected $response;

    /**
     * Override in childs.
     * 
     * @return  void
     */
    abstract public function execute();

    /**
     * Executes API method and return response
     * 
     * @param   bool    $internal
     * @return  mixed
     * @throws  waAPIException
     */
    public function getResponse($internal = false)
    {
        if (!$internal) {
            // Check request method
            $request_method = strtoupper(waRequest::method());
            if (!in_array($request_method, (array)$this->method)) {
                throw new waAPIException('invalid_request', 'Method '.$request_method.' not allowed', 405);
            }
        }

        $this->execute();

        return $this->response;
    }

    /**
     * 
     * 
     * @param   string  $name  
     * @param   bool    $required 
     * @param   string  $method 
     * @return  mixed
     * @throws  waAPIException
     */
    protected function getRequestParam($name, $required = false, $method = 'get')
    {
        $value = waRequest::$method($name);
        if ($required && is_null($value)) {
            throw new waAPIException('invalid_param', 'Required parameter is missing: '.$name, 400);
        }
        return $value;
    }

    /**
     * 
     * 
     * @param   string  $name  
     * @param   bool    $required 
     * @param   string  $method 
     * @return  mixed
     * @throws  waAPIException
     */
    public function get($name, $required = false)
    {
        return $this->getRequestParam($name, $required);
    }

    /**
     * 
     * 
     * @param   string  $name  
     * @param   bool    $required 
     * @param   string  $method 
     * @return  mixed
     * @throws  waAPIException
     */
    public function post($name, $required = false)
    {
        return $this->getRequestParam($name, $required, 'post');
    }

    /**
     * Returns rights of the contact
     * 
     * @param   string  $name   Кey of the right. if it's null, return all rights of the contact for app
     * @param   bool    $assoc  Only if name is null, if true returns associative array of the rights
     * @return  mixed
     */
    public function getRights($name = null, $assoc = true)
    {
        return wa()->getUser()->getRights(wa()->getApp(), $name, $assoc);
    }
}
