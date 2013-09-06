<?php

class waAPIException extends Exception
{
    protected $status_code;
    protected $error;
    protected $error_description;

    public function __construct($error, $error_description = null, $status_code = null)
    {
        if (empty($status_code) && is_numeric($error_description)) {
            $status_code = $error_description;
            $error_description = null;
        }
        $this->status_code = $status_code;
        $this->error = $error;
        $this->error_description = $error_description;
    }

    public function __toString()
    {
        if ($this->status_code) {
            wa()->getResponse()->setStatus($this->status_code);
        }

        $format = strtoupper(waRequest::request('format', 'JSON'));
        if ($format && !in_array($format, array("XML", "JSON"))) {
            $this->error = 'invalid_request';
            $this->error_description  = 'Invalid response format: '.$format;
            $format = 'JSON';
        }
        if (!$format) {
            $format = 'JSON';
        }

        $result = '';

        if ($format == 'XML'){
            wa()->getResponse()->addHeader('Content-type', 'text/xml; charset=utf-8');
        } elseif ($format == 'JSON') {
            $callback = (string)waRequest::get('callback', false);
            // for JSONP
            if ($callback) {
                wa()->getResponse()->setStatus(200);
                wa()->getResponse()->addHeader('Content-type', 'text/javascript; charset=utf-8');
                $result .= $callback .'(';
            } else {
                wa()->getResponse()->addHeader('Content-type', 'application/json; charset=utf-8');
            }
        }

        $response = array('error' => $this->error);
        if ($this->error_description) {
            $response['error_description'] = $this->error_description;
        }
        wa()->getResponse()->sendHeaders();
        $result .= waAPIDecorator::factory($format)->decorate($response);
        if (!empty($callback)) {
            $result .= ');';
        }
        return $result;
    }
    
}