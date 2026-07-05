<?php

abstract class waAuthAdapter
{

    protected $options = array();

    public function __construct($options = array())
    {
        if (is_array($options)) {
            foreach ($options as $k => $v) {
                $this->options[$k] = $v;
            }
        }
    }

    /** @since 4.1.0 */
    protected function getControlsConfig()
    {
        return array(
            'app_id'     => 'App ID',
            'app_secret' => 'App Secret'
        );
    }

    /**
     * @param array $values
     * 
     * Changed in 4.1.0: new field types are supported; $values param added.
     */
    public function getControls()
    {
        $config = $this->getControlsConfig();
        $site_is_updated = false;
        if (version_compare(wa()->getversion('site'), '3.5.2') >= 0) {
            $site_is_updated = true;
        }

        if (!$site_is_updated) {
            // Site below certain version does not support arbutrary field types
            $config = array_filter($config, function($field) {
                return is_string($field);
            });
        } else {
            // render waHtmlControl
            $values = ifempty(ref(func_get_arg(0)), []);
            foreach ($config as $control_id => &$control) {
                if (ifset($control, 'type', null) === 'waHtmlControl') {
                    $adapter_id = $this->getId();
                    $control['value'] = ifset($values, $control_id, '');
                    $control['html'] = waHtmlControl::getControl($control['control_type'], "adapters[{$adapter_id}][{$control_id}]", $control);
                    $control['type'] = 'html';
                }
            }
            unset($field);
        }

        return $config;
    }

    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /**
     * @return mixed
     * @throws waAuthException
     * @throws waAuthInvalidCredentialsException
     * @throws waAuthConfirmEmailException
     * @throws waAuthConfirmPhoneException
     * @throws waException
     */
    abstract public function auth();

    public function getId()
    {
        $class = get_class($this);
        return substr($class, 0, -4);
    }

    public function getName()
    {
        $class = get_class($this);
        return ucfirst(substr($class, 0, -4));
    }

    public function getIcon($prefix = 'circle', $ext = 'svg')
    {
        $prefix = $prefix ? '-'.htmlspecialchars($prefix) : null;
        $ext = $ext === 'png' ? 'png' : 'svg';
        return wa()->getRootUrl().'wa-content/img/auth/'.$this->getId().$prefix.'.'.$ext;
    }

    /**
     * Inner url that will dispatched to OAuthController and that to auth adapter again
     * @return string
     * @throws waException
     */
    public function getUrl()
    {
        return wa()->getRootUrl(false, true).
            'oauth.php?app='.wa()->getApp().
            '&provider='.$this->getId();
    }

    public function url()
    {
        $current_url = waRequest::isXMLHttpRequest() ? 
            waRequest::server('HTTP_REFERER', wa()->getRootUrl(false, true), waRequest::TYPE_STRING_TRIM) : 
            wa()->getConfig()->getCurrentUrl();
        
        $goal_url_encoded = waRequest::get('goal_url', waUtils::urlSafeBase64Encode($current_url), waRequest::TYPE_STRING_TRIM);
        return wa()->getRootUrl(false, true).
            'oauth.php?app='.wa()->getApp().
            '&provider='.$this->getId().
            '&goal_url='.$goal_url_encoded;
    }

    /**
     * Callback url - url of controller that will process response from oauth provider service
     * @param bool $absolute
     * @return string
     * @throws waException
     */
    public function getCallbackUrl($absolute = true)
    {
        return wa()->getRootUrl($absolute, true).'oauth.php?provider='.$this->getId();
    }

    protected function get($url, &$status = null, $header = [])
    {
        $header[] = 'User-Agent: Webasyst-oAuth';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if (!empty($header)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            $content = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $content;
        }
        return file_get_contents($url);
    }

    protected function post($url, $post_data, $header = [], &$status = null)
    {
        $header[] = 'User-Agent: Webasyst-oAuth';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if (!empty($header)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

            $content = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $content;
        }
        $header[] = 'Content-type: application/x-www-form-urlencoded';
        $context = stream_context_create(array(
            parse_url($url, PHP_URL_SCHEME) => array(
                'method'  => 'POST',
                'header'  => $header,
                'content' => $post_data
            ),
        ));
        return file_get_contents($url, false, $context);
    }
}
