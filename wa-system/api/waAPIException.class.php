<?php
/**
 * API exception.
 * 
 * @package    Webasyst
 * @category   wa-system/API
 * @author     Webasyst
 * @copyright  (c) 2011-2014 Webasyst
 * @license    LGPL
 */
class waAPIException extends Exception
{
    /**
     * @var string
     */
    protected $error;
    /**
     * @var string
     */
    protected $error_description;
    /**
     * @var integer
     */
    protected $status_code;

    /**
     * Set properties.
     * 
     * @param   string          $error
     * @param   string|integer  $error_description
     * @param   integer         $status_code
     * @return  void
     */
    public function __construct($error, $error_description = '', $status_code = 0)
    {
        if (!$status_code && is_numeric($error_description)) {
            $status_code = $error_description;
            $error_description = '';
        }
        $this->status_code = $status_code;
        $this->error = $error;
        $this->error_description = htmlspecialchars($error_description);
    }

    /**
     * Returns a string representation of the current object.
     * 
     * @return  string
     */
    public function __toString()
    {
        if ($this->status_code) {
            wa()->getResponse()->setStatus($this->status_code);
        }

        $format = strtoupper(waRequest::request('format', 'JSON'));
        if ($format && !in_array($format, waAPIDecorator::getFormats())) {
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
            $callback = waRequest::get('callback', false);
            // For JSONP
            if ($callback) {
                wa()->getResponse()->setStatus(200);
                wa()->getResponse()->addHeader('Content-type', 'application/javascript; charset=utf-8');
                $result .= $callback.'(';
            } else {
                wa()->getResponse()->addHeader('Content-type', 'application/json; charset=utf-8');
            }
        }

        wa()->getResponse()->sendHeaders();

        $response = array('error' => $this->error);
        if ($this->error_description) {
            $response['error_description'] = $this->error_description;
        }
        $result .= waAPIDecorator::getInstance($format)->decorate($response);
        if (!empty($callback)) {
            $result .= ');';
        }

        return $result;
    }
}
