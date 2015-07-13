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
     * @param   string  $name       Cookie name
     * @param   mixed   $value      Cookie value
     * @param   int     $expire     Expiration time
     * @param   string  $path       Path to URL "subdirectory" within which cookie must be valid
     * @param   string  $domain     Domain name for which cookie must be valid
     * @param   bool    $secure     Flag making cookie value available only if passed over HTTPS
     * @param   bool    $http_only  Flag making cookie value accessible only via HTTP and not accessible to client scripts (JavaScript)
     * @return  waResponse  Instance of waResponse class
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
     * @param  int  $code  Server status code

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
     * @return  waResponse  Instance of waResponse class
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
            header(waRequest::server('SERVER_PROTOCOL', 'HTTP/1.0').' '.$this->status.' '.self::$statuses[$this->status]);
        }

        return $this;
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
            $app_info = wa()->getAppInfo($app_id === true ? null : $app_id);
            $url .= '?'.(isset($app_info['version']) ? $app_info['version'] : '0.0.1');
            if (waSystemConfig::isDebug()) {
                $url .= '.'.time();
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
     * @return  string|array  HTML string or array of URLs
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
            $app_id =  wa()->getApp();
            $key = wa()->getRouting()->getDomain().'/theme';
            $hash = false;
            if (waRequest::get('theme_hash') && ($theme = waRequest::get('set_force_theme')) !== null) {
                $hash = waRequest::get('theme_hash');
            } elseif (($theme = wa()->getStorage()->get($app_id.'/'.$key)) || ($theme = wa()->getStorage()->get($key))) {
                $app_settings_model = new waAppSettingsModel();
                $hash = $app_settings_model->get($app_id, 'theme_hash');
                if (!$hash) {
                    $hash = $app_settings_model->get('webasyst', 'theme_hash');
                }
                if ($hash) {
                    $hash = md5($hash);
                }
            }
            if (!$hash || !$theme || !waTheme::exists($theme)) {
                return $result;
            }
            $theme = new waTheme($theme, $app_id);
            $theme = $theme['name'];
            $url = '?theme_hash='.$hash.'&set_force_theme=';
            $result .= '
<script type="text/javascript">
$(function () {
    var div = $(\'<div class="theme-preview"></div>\');
    div.css({
        position: "fixed",
        bottom: 0,
        left: 0,
        right: 0,
        opacity: 0.9,
        padding: "15px",
        "text-align": "center",
        background: "#ffd",
        "border-top": "4px solid #eea",
        "border-image": "url(\''.wa()->getUrl().'wa-content/img/recovery-mode-background.png\') 10 10 10 10 repeat",
        "font-family": "Lucida Grande",
        "font-size": "14px",
        "z-index": 100500
    });
    div.html("'.sprintf(_ws('<strong>%s</strong> theme preview in action'), $theme).'");';
            if (wa()->getUser()->isAuth() && wa()->getUser()->getRights('shop')) {
                $result .= '
    div.prepend(\'<a href="'.$url.'" style="float: right;">'._ws('Stop preview session').'</a>\');
    div.find("a").click(function () {
        $("body").append($(\'<iframe style="display:none" src="\' + $(this).attr("href") + \'" />\').load(function () {
            $(this).remove();
            div.remove();
            if (location.href.indexOf("theme_hash") != -1) {
                location.href = location.href.replace(/(theme_hash|set_force_theme)=[^&]*&?/g, "");
            }
        }));
        return false;
    });';
            }
            $result .= '
    $("body").append(div);
});
</script>';
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
            $app_info = wa()->getAppInfo($app_id === true ? null : $app_id);
            $url .= '?'.(isset($app_info['version']) ? $app_info['version'] : '0.0.1');
            if (waSystemConfig::isDebug()) {
                $url .= '.'.time();
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
}
