<?php
/**
 * HTTP response.
 *
 * @package     wa-system
 * @subpackage  response
 * @author      Webasyst LLC
 * @copyright   2014 Webasyst LLC
 * @license     http://www.webasyst.com/developers/ LGPL
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
     * Sets a cookie value.
     *
     * @param   string     $name       Cookie name
     * @param   mixed      $value      Cookie value
     * @param   array|int  $expires    Expiration time; an array here can be used to pass key-value options like setcookie() of PHP 7.3
     * @param   string     $path       Path to URL "subdirectory" within which cookie must be valid
     * @param   string     $domain     Domain name for which cookie must be valid
     * @param   bool       $secure     Flag making cookie value available only if passed over HTTPS
     * @param   bool       $httponly   Flag making cookie value accessible only via HTTP and not accessible to client scripts (JavaScript)
     * @return  waResponse  Instance of waResponse class
     */
    public function setCookie(
        $name,
        $value,
        $expires = 0,
        $path = '',
        $domain = '',
        $secure = false,
        $httponly = false
    ) {
        if (headers_sent()) {
            return $this;
        }

        if (is_array($expires)) {
            $options = $expires;
            $expires = ifset($options, 'expires', 0);
            $path = ifset($options, 'path', '');
            $domain = ifset($options, 'domain', '');
            $secure = ifset($options, 'secure', false);
            $httponly = ifset($options, 'httponly', false);
            $samesite = ifset($options, 'samesite', null);
        }
        if (!$path) {
            $path = waSystem::getInstance()->getRootUrl();
        }
        if (!in_array(ifset($samesite), ['Lax', 'Strict', 'None'])) {
            $samesite = null;
        }
        if (!isset($samesite)) {
            if (waRequest::isHttps()) {
                //
                // 'samesite=None; Secure' means:
                // POST from different domain will contain all cookies.
                // Non-HTTPS request from anywhere will contain no cookies.
                //
                // Since we need cross-domain POSTs for certain functions to work,
                // this is the preferred option. And we have other built-in methods
                // to protect against CSRF.
                //
                $samesite = 'None';
            } else {
                //
                // 'samesite=Lax' means:
                // Background POST from different domain will contain no cookies.
                // Non-HTTPS request from anywhere will contain all cookies.
                //
                // No other option on HTTP, so we'll have to sacrifice cross-domain POSTs here.
                //
                $samesite = 'Lax';
            }
        }
        if ($samesite == 'None') {
            $secure = true; // required by specs
        }

        // setCookie changed in 7.3
        if (PHP_VERSION_ID < 70300) {
            setcookie(
                (string)$name,
                (string)$value,
                (int)$expires,
                sprintf('%s; samesite=%s', $path, $samesite),
                (string)$domain,
                (bool)$secure,
                (bool)$httponly
            );
        } else {
            setcookie(
                (string)$name,
                (string)$value,
                compact('expires', 'path', 'domain', 'secure', 'httponly', 'samesite')
            );
        }

        $_COOKIE[$name] = $value;

        return $this;
    }

    /**
     * Returns the server response code.
     *
     * @return  int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets server response status.
     *
     * @param  int $code Server status code
     * @return waResponse  Instance of waResponse class
     */
    public function setStatus($code = 200)
    {
        if (isset(self::$statuses[$code])) {
            $this->status = (int)$code;
        }

        return $this;
    }

    /**
     * Adds a header to be sent by server in response to user request.
     * All added headers will be sent to user when method sendHeaders() is called.
     *
     * @param  string  $name Header name
     * @param  mixed   $value Header value
     * @param  bool    $replace Flag requiring to replace previously set value of specified header
     * @return  waResponse  Instance of waResponse class
     */
    public function addHeader($name, $value, $replace = true)
    {
        if (in_array(strtolower($name), array('expires', 'last-modified'))) {
            $value = gmdate('D, d M Y H:i:s', is_int($value) ? $value : strtotime($value)).' GMT';
        }

        if (!isset($this->headers[$name]) || $replace) {
            $this->headers[$name] = $value;
        } elseif (!$replace) {
            if (!is_array($this->headers[$name])) {
                settype($this->headers[$name], 'array');
            }
            $this->headers[$name][] = $value;
        }

        return $this;
    }

    /**
     * Returns response header value.
     *
     * @param  string|null  $name  Id of header whose value must be returned. If not specified, entire header array is returned.
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
     * Sends all previously added headers.
     *
     * Will immediately send 304 and exit if there are both
     * an If-Modified-Since request header as well as Last-Modified response header
     * and the former equals the latter. See `handleIfModifiedSince()`
     *
     * @return  waResponse  Instance of waResponse class
     */
    public function sendHeaders()
    {
        if ($this->status != 304 && $this->isNotModified304()) {
            $this->setStatus(304);
        }

        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $var) {
                    $this->header($name.': '.$var, false);
                }
            } else {
                $this->header($name.': '.$value);
            }
        }

        // Added after all that was not erased
        if ($this->status !== null) {
            $this->header(waRequest::server('SERVER_PROTOCOL', 'HTTP/1.0').' '.$this->status.' '.self::$statuses[$this->status]);
        }

        if ($this->status == 304) {
            exit;
        }

        return $this;
    }

    protected function header($string, $replace = true, $http_response_code = null)
    {
        if (!headers_sent()) {
            header($string, $replace, $http_response_code);
        }
    }

    /**
     * Redirects user to specified URL.
     *
     * @param   string  $url   URL to redirect to
     * @param   int     $code  Server response code to return with the redirect
     */
    public function redirect($url, $code = 302)
    {
        $this->setStatus($code)
            ->addHeader('Location', $url)
            ->sendHeaders();

        exit;
    }

    /**
     * Return current TITLE value.
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->getMeta('title');
    }

    /**
     * Sets the page TITLE value.
     * This value is accessible in Smarty templates using {$wa->title()}.
     *
     * @param   string  $title  Page TITLE value
     * @return  waResponse  Instance of waResponse class
     */
    public function setTitle($title)
    {
        return $this->setMeta('title', (string)$title);
    }

    /**
     * Return current CANONICAL link.
     *
     * @return  string
     * @since   1.14.2
     */
    public function getCanonical()
    {
        return $this->getMeta('canonical');
    }

    /**
     * Sets the page CANONICAL link.
     * This link is accessible in Smarty templates using {$wa->head()}.
     *
     * @param   string  $url  Page CANONICAL link
     * @return  waResponse  Instance of waResponse class
     * @since   1.14.2
     */
    public function setCanonical($canonical_url, $with_header_link = true)
    {
        $actual_url = wa()->getConfig()->getRootUrl(true) . wa()->getConfig()->getRequestUrl();
        if ($canonical_url != $actual_url) {
            if ($with_header_link) {
                $this->addHeader('Link', "<{$canonical_url}>; rel='canonical'");
            }
            $this->setMeta('canonical', (string)$canonical_url);
        }
    }

    /**
     * Sets the Last-Modified header.
     * Can be called multiple times during page generation. Effective value of
     * Last-Modified header is the most recent $last_modified_datetime between all calls.
     *
     * `SendHeaders()` will immediately send 304 and exit if there's
     * an If-Modified-Since request header that equals effective $last_modified_datetime.
     * See handleIfModifiedSince(): it can be used to send 304 immediately.
     *
     * @param   string  $last_modified_datetime  Page update datetime
     * @since   1.14.3
     */
    public function setLastModified($last_modified_datetime)
    {
        $last_modified_timestamp = @strtotime($last_modified_datetime);
        if (!$last_modified_timestamp || wa()->getUser()->getId()) {
            return;
        }

        // Ignore new $last_modified_datetime if more recent datetime
        // has been set via previous call to setLastModified()
        $existing_last_modified = $this->getHeader('Last-Modified');
        if ($existing_last_modified) {
            $existing_last_modified_timestamp = strtotime($existing_last_modified);
            if ($existing_last_modified_timestamp >= $last_modified_timestamp) {
                return;
            }
        }

        $last_modified = gmdate("D, d M Y H:i:s \G\M\T", $last_modified_timestamp);
        $this->addHeader('Last-Modified', $last_modified);
        if (!$this->getHeader('Cache-Control')) {
            $this->addHeader('Cache-Control', 'max-age=0');
        }
    }

    /**
     * Check if request header If-Modified-Since equals response header Last-Modified.
     * If it does, this sends 304 response immediately and exits.
     *
     * @since   1.14.7
     */
    public function handleIfModifiedSince()
    {
        if ($this->isNotModified304()) {
            $this->setStatus(304);
            $this->sendHeaders();
            exit;
        }
    }

    /**
     * Compare request header If-Modified-Since and response header Last-Modified.
     * Return true if 304 status should be sent. False if full response is required.
     */
    protected function isNotModified304()
    {
        // Handle behaviour of headers: If-Modified-Since / Last-Modified / 304 Not Modified status.
        $last_modified = $this->getHeader('Last-Modified');
        if ($last_modified) {
            $if_modified_since = false;
            $last_modified_timestamp = strtotime($last_modified);
            if (getenv('HTTP_IF_MODIFIED_SINCE')) {
                $if_modified_since = @strtotime(substr(getenv('HTTP_IF_MODIFIED_SINCE'), 5));
            }
            if (!$if_modified_since && waRequest::server('HTTP_IF_MODIFIED_SINCE')) {
                $if_modified_since = @strtotime(substr(waRequest::server('HTTP_IF_MODIFIED_SINCE'), 5));
            }
            if ($if_modified_since && $if_modified_since == $last_modified_timestamp) {
                // We check for equality (==) rather than (>=) deliberately because of the way
                // some dynamic content (e.g. shop cart in frontend theme) behaves with last-modified.
                // Equality check does not break anything but fixes corner cases
                // and is more robust overall.
                return true;
            }
        }
        return false;
    }

    /**
     * Sets a META value.
     * This value is accessible in Smarty templates using {$wa->meta()}.
     *
     * @param   string|array  $name   META data item id: page title ('title'), META tags keywords ('keywords'), description ('description')
     * @param   mixed         $value  META field value
     * @return  waResponse  Instance of waResponse class
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

    public function setOGMeta($property, $content)
    {
        $this->metas['og'][$property] = $content;
        return $this;
    }

    /**
     * Returns META data: page TITLE, META tags 'keywords', 'description'.
     *
     * @param  string|null  $name  META data item id whose value must be returned: 'title', 'keywords', or 'description'.
     *     If not specified, the method returns entire META data array.
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
     * Adds a JavaScript code string for Google Analytics.
     * Code added for Google Analytics is included in HTML templates using {$wa->headJs()}.
     *
     * @param  string  $str  JavaScript code string
     * @return  waResponse  Instance of waResponse class
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
     * Adds a URL to the JavaScript file list.
     * All added URLs are available in Smarty templates using {$wa->js()}.
     *
     * @param  string       $url     URL of a JavaScript file. If $app_id is specified, then the URL must be relative to specified
     *     app's directory URL. Otherwise JavaScript file URL must be relative to framework's root URL.
     * @param  string|bool  $app_id  Optional app id
     * @return  waResponse  Instance of waResponse class
     */
    public function addJs($url, $app_id = false)
    {
        if ($app_id) {
            $url = wa()->getAppStaticUrl($app_id).$url;
            if (false === strpos($url, '?')) {
                $app_info = wa()->getAppInfo($app_id === true ? null : $app_id);
                $url .= '?'.(isset($app_info['version']) ? $app_info['version'] : '0.0.1');
                if (waSystemConfig::isDebug()) {
                    $url .= '.'.time();
                }
            }
        // Support external links
        } elseif ((strpos($url, '://') === false) && (strpos($url, '//') !== 0)) {
            $url = wa()->getRootUrl().$url;
        }

        $this->js[] = $url;

        return $this;
    }

    /**
     * Returns the list of previously added JavaScript file URLs.
     *
     * @param  bool  $html   Determines whether method must return an HTML string for including JavaScript files or an array of URLs
     * @param  bool  $strict  Determines whether XHTML format must be used instead of default HTML
     * @return string|array  HTML string or array of URLs
     * @throws waDbException
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

        if (wa()->getEnv() == 'frontend') {
            $result .= $this->getPreviewTemplate();
        }

        return $result;
    }


    /**
     * Adds a URL to the CSS file list. All added CSS file URLs are available in Smarty templates using {$wa->css()}.
     *
     * @param  string       $url     Relative URL of a CSS file. If $app_id is specified, then the URL must be relative to
     *     specified app's directory URL. Otherwise the CSS file URL must be relative to framework's root URL.
     * @param  string|bool  $app_id  Optional app id
     * @return waResponse Instance of waResponse class
     */
    public function addCss($url, $app_id = false)
    {
        if ($app_id) {
            $url = wa()->getAppStaticUrl($app_id).$url;
            if (false === strpos($url, '?')) {
                $app_info = wa()->getAppInfo($app_id === true ? null : $app_id);
                $url .= '?'.(isset($app_info['version']) ? $app_info['version'] : '0.0.1');
                if (waSystemConfig::isDebug()) {
                    $url .= '.'.time();
                }
            }
        // Support external links
        } elseif ((strpos($url, '://') === false) && (strpos($url, '//') !== 0)) {
            $url = wa()->getRootUrl().$url;
        }

        $this->css[] = $url;

        return $this;
    }

    /**
     * Returns the list of previously added CSS file URLs.
     *
     * @param  bool  $html    Determines whether method must return an HTML string for including CSS files or an array of URLs
     * @param  bool  $strict  Determines whether XHTML format must be used instead of default HTML
     * @return  string|array  HTML string or array of URLs
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

    /**
     * @return string|null
     * @throws waDbException|waException|SmartyException
     * @see waRequest::getTheme();
     * @see waDesignActions::getThemeHash();
     */
    private function getPreviewTemplate()
    {
        $app_id =  wa()->getApp();

        $key = waRequest::getThemeStorageKey();
        $theme_hash = waRequest::get('theme_hash');
        $theme = waRequest::get('set_force_theme');

        $hash = false;

        if ($theme_hash && $theme !== null) {
            $hash = $theme_hash;
        } elseif ($theme = wa()->getStorage()->get($key)) {
            $app_settings_model = new waAppSettingsModel();
            $hash = $app_settings_model->get('webasyst', 'theme_hash');
            if ($hash) {
                $hash = md5($hash);
            }
        }

        if ($hash === false || !$theme || !waTheme::exists($theme)) {
            return null;
        }

        $theme = new waTheme($theme, $app_id);

        if ($theme->type === waTheme::TRIAL && wa()->getUser()->get('is_user') != 1) {
            return null;
        }

        $url = '?theme_hash='.$hash.'&set_force_theme=';

        $view = wa('webasyst')->getView();
        $view->assign([
            'theme' => $theme,
            'url'   => $url,
        ]);
        $template = wa()->getConfig()->getRootPath().'/wa-system/webasyst/templates/actions/preview/preview.html';
        return $view->fetch($template);
    }
}
