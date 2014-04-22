<?php
/**
 * HTTP response.
 *
 * @package     wa-system
 * @subpackage  response
 * @author      Webasyst LLC
 * @copyright   2014 Webasyst LLC
 * @license     http://webasyst.com/framework/license/ LGPL
 */
class waResponse
{
    /**
     * @var  array  Available HTTP statuses
     */
    protected static $statuses = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    );

    /**
     * @var  array  Response HTTP headers
     */
    protected $headers = array();

    /**
     * @var  int  Response HTTP status
     */
    protected $status;

    /**
     * @var  array  Meta tags
     */
    protected $metas = array();

    /**
     * @var  array  JavaScript files
     */
    protected $js = array();

    /**
     * @var  array  CSS files
     */
    protected $css = array();

    /**
     * @var  array  Google Analytics
     * @link  http://google.com/intl/en_us/analytics/
     */
    protected $google_analytics = array();

    /**
     * Send a cookie.
     *
     * @param   string  $name       The name of the cookie
     * @param   mixed   $value      The value of the cookie
     * @param   int     $expire     The time the cookie expires
     * @param   string  $path       The path on the server in which the cookie will be available on
     * @param   string  $domain     The domain that the cookie is available to
     * @param   bool    $secure     Transmitted over a secure HTTPS connection from the client?
     * @param   bool    $http_only  Cookie will be made accessible only through the HTTP protocol?
     * @return  waResponse
     */
    public function setCookie(
        $name,
        $value,
        $expire = 0,
        $path = '',
        $domain = '',
        $secure = false,
        $http_only = false
    )
    {
        if (!$path) {
            $path = waSystem::getInstance()->getRootUrl();
        }

        setcookie(
            (string)$name,
            (string)$value,
            (int)$expire,
            (string)$path,
            (string)$domain,
            (bool)$secure,
            (bool)$http_only
        );

        // New value available only after the page reloads
        // $_COOKIE[$name] = $value;
        
        return $this;
    }

    /**
     * Get HTTP status.
     *
     * @return  int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set HTTP status.
     *
     * @param   int   $code  Code of HTTP status
     * @return  waResponse
     */
    public function setStatus($code = 200)
    {
        if (isset(self::$statuses[$code])) {
            $this->status = (int)$code;
        }

        return $this;
    }

    /**
     * Set HTTP header.
     *
     * @param   string  $name
     * @param   mixed   $value
     * @param   bool    $replace
     * @return  waResponse
     */
    public function addHeader($name, $value, $replace = true)
    {
        $name = strtolower($name);

        if (in_array($name, array('expires', 'last-modified'))) {
            $value = gmdate('D, d M Y H:i:s', is_int($value) ? $value : strtotime($value)).' GMT';
        }

        if (!isset($this->headers[$name])) {
            $this->headers[$name] = $value;
        } else {
            if ($replace) {
                $this->headers[$name] = $value;
            } else {
                if (!is_array($this->headers[$name])) {
                    settype($this->headers[$name], 'array');
                }
                $this->headers[$name][] = $value;
            }
        }

        return $this;
    }

    /**
     * Get current header or all headers (name = null).
     *
     * @param   string|null  $name
     * @return  mixed
     */
    public function getHeader($name = null)
    {
        if ($name !== null) {
            return isset($this->headers[$name]) ? $this->headers[$name] : null;
        }

        return $this->headers;
    }

    /**
     * Send HTTP headers.
     *
     * @return  waResponse
     */
    public function sendHeaders()
    {
        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $var) {
                    header($name.': '.$var, false);
                }
            }  else {
                header($name.': '.$value);
            }
        }

        // Added after all that was not erased
        if ($this->status !== null) {
            header($_SERVER['SERVER_PROTOCOL'].' '.$this->status.' '.self::$statuses[$this->status]);
        }

        return $this;
    }

    /**
     * Redirect to URL.
     *
     * @param   string  $url
     * @param   int     $code  Code of HTTP status
     * @return  void
     */
    public function redirect($url, $code = 302)
    {
        $this->setStatus($code)
            ->addHeader('Location', $url)
            ->sendHeaders();

        exit;
    }
    
    /**
     * Get meta tag "title".
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->getMeta('title');
    }

    /**
     * Set meta tag "title".
     *
     * @param   string  $title
     * @return  waResponse
     */
    public function setTitle($title)
    {
        return $this->setMeta('title', (string)$title);
    }

    /**
     * Set meta tag.
     *
     * @param   string|array  $name
     * @param   mixed         $value
     * @return  waResponse
     */
    public function setMeta($name, $value = null)
    {
        if (is_array($name)) {
            $this->metas = $name + $this->metas;
        } else {
            $this->metas[$name] = $value;
        }

        return $this;
    }

    /**
     * Get current meta tag or all tags (name = null).
     *
     * @param   string|null  $name
     * @return  mixed
     */
    public function getMeta($name = null)
    {
        if ($name !== null) {
            return isset($this->metas[$name]) ? $this->metas[$name] : null;
        }

        return $this->metas;
    }

    /**
     * Add string to Google Analytics.
     *
     * @param   string  $str
     * @return  waResponse
     */
    public function addGoogleAnalytics($str)
    {
        $this->google_analytics[] = $str;

        return $this;
    }

    /**
     * Return compiled code of Google Analytics.
     *
     * @return  string
     */
    public function getGoogleAnalytics()
    {
        return implode(PHP_EOL, $this->google_analytics);
    }

    /**
     * Add JavaScript file.
     *
     * @param   string       $url
     * @param   string|bool  $app_id
     * @return  waResponse
     */
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
     * Gets JavaScript files as compiled HTML string or an array of URL's.
     *
     * @param   bool  $html    Return scripts as HTML string or an array of URL's?
     * @param   bool  $strict  Use strict HTML format (XHTML)?
     * @return  string|array   
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

    
    /**
     * Add CSS file.
     *
     * @param   string       $url
     * @param   string|bool  $app_id
     * @return  waResponse
     */
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
     * Gets CSS styles as compiled HTML string or an array of URL's.
     *
     * @param   bool  $html    Return styles as HTML string or an array of URL's?
     * @param   bool  $strict  Use strict HTML format (XHTML)?
     * @return  string|array   Compiled HTML string or an array of URL's
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
