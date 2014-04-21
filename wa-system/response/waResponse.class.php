<?php
/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage response
 */
class waResponse {

    protected $headers = array();

    protected $metas = array();

    protected $js = array();

    protected $css = array();

    protected $google_analytics = array();

    protected $status;

    protected static $statuses = array(
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '306' => '(Unused)',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported',
    );


    public function setCookie(
        $name,
        $value,
        $expire = null,
        $path = null,
        $domain = '',
        $secure = false,
        $http_only = false
    )
    {
        if ($expire !== null) {
            $expire = (int) $expire;
        }

        if ($path === null) {
            $path = waSystem::getInstance()->getRootUrl();
        }

        settype($secure, 'bool');
        settype($http_only, 'bool');

        setcookie($name, $value, $expire, $path, $domain, $secure, $http_only);

        $_COOKIE[$name] = $value;
    }

    
    public function getStatus()
    {
        return $this->status;
    }

    
    public function setStatus($code)
    {
        if (isset(self::$statuses[$code])) {
            $this->status = $code;
        }

        return $this;
    }

    
    public function addHeader($name, $value, $replace = true)
    {
        if (in_array($name, array('Expires', 'Last-Modified'))) {
            $value = gmdate('D, d M Y H:i:s', is_int($value) ? $value : strtotime($value)).' GMT';
        }

        if ($replace || !isset($this->headers[$name])) {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    
    public function getHeader($name = null)
    {
        if ($name !== null) {
            return isset($this->headers[$name]) ? $this->headers[$name] : null;
        }

        return $this->headers;
    }

    
    public function redirect($url, $code = 302)
    {
        $this->setStatus($code)
            ->addHeader('Location', $url)
            ->sendHeaders();

        exit;
    }

    /**
     * Send HTTP headers.
     *
     * @see http://faqs.org/rfcs/rfc2616 HTTP/1.1 specification
     */
    public function sendHeaders()
    {
        if ($this->status !== null) {
            header('HTTP/1.1 '.$this->status.' '.self::$statuses[$this->status]);
        }

        foreach ($this->headers as $name => $value) {
            header($name.': '.$value);
        }

        return $this;
    }

    
    public function getTitle()
    {
        return $this->getMeta('title');
    }

    
    public function setTitle($title)
    {
        return $this->setMeta('title', (string)$title);
    }

    
    public function setMeta($name, $value = null)
    {
        if (is_array($name)) {
            $this->metas = $name + $this->metas;
        } else {
            $this->metas[$name] = $value;
        }

        return $this;
    }

    
    public function getMeta($name = null)
    {
        if ($name !== null) {
            return isset($this->metas[$name]) ? $this->metas[$name] : null;
        }

        return $this->metas;
    }

    
    public function addGoogleAnalytics($str)
    {
        $this->google_analytics[] = $str;

        return $this;
    }

    
    public function getGoogleAnalytics()
    {
        return implode(PHP_EOL, $this->google_analytics);
    }

    
    public function addJs($url, $app_id = false)
    {
        if ($app_id) {
            $url = wa()->getAppStaticUrl($app_id).$url;
            $app_info = wa()->getAppInfo($app_id === true ? null : $app_id);
            $url .= '?'.(isset($app_info['version']) ? $app_info['version'] : '0.0.1');
            if (waSystemConfig::isDebug()) {
                $url .= '.'.time();
            }
        // Support external links
        } elseif (strpos($url, '://') === false) {
            $url = wa()->getRootUrl().$url;
        }

        $this->js[] = $url;

        return $this;
    }

    /**
     * Gets JavaScipt's
     *
     * @param   bool  $html    Return scripts as HTML string or an array of URL's?
     * @param   bool  $strict  Use strict HTML format (XHTML)?
     * @return  array|string
     */
    public function getJs($html = true, $strict = false)
    {
        if (!$html) {
            return $this->js;
        }

        $result = '';
        foreach ($this->js as $url) {
            $result .= '<script'.($strict ? ' type="text/javascript"' : '').' src="'.$url.'"></script>'.PHP_EOL;
        }
        return $result;
    }

    
    public function addCss($url, $app_id = false)
    {
        if ($app_id) {
            $url = wa()->getAppStaticUrl($app_id).$url;
            $app_info = wa()->getAppInfo($app_id === true ? null : $app_id);
            $url .= '?'.(isset($app_info['version']) ? $app_info['version'] : '0.0.1');
            if (waSystemConfig::isDebug()) {
                $url .= '.'.time();
            }
        // Support external links
        } elseif (strpos($url, '://') === false) {
            $url = wa()->getRootUrl().$url;
        }

        $this->css[] = $url;

        return $this;
    }

    /**
     * Gets CSS styles
     *
     * @param   bool  $html    Return styles as HTML string or an array of URL's?
     * @param   bool  $strict  Use strict HTML format (XHTML)?
     * @return  array|string
     */
    public function getCss($html = true, $strict = false)
    {
        if (!$html) {
            return $this->css;
        }

        $result = '';
        foreach ($this->css as $url) {
            $result .= '<link href="'.$url.'" rel="stylesheet"'.($strict ? ' type="text/css" /' : '').'>'.PHP_EOL;
        }
        return $result;
    }
}
