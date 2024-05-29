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

    public function getControls()
    {
        return array(
            'app_id'     => 'App ID',
            'app_secret' => 'App Secret'
        );
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
        return wa()->getRootUrl(false, true).'oauth.php?app='.wa()->getApp().'&provider='.$this->getId();
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

    protected function get($url, &$status = null)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
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

    protected function post($url, $post_data, $header = [])
    {
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
