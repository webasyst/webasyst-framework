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

    public function getIcon()
    {
        return wa()->getRootUrl().'wa-content/img/auth/'.$this->getId().'.png';
    }

    public function getUrl()
    {
        return wa()->getRootUrl(false, true).'oauth.php?app='.wa()->getApp().'&amp;provider='.$this->getId();
    }

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

    protected function post($url, $post_data)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

            $content = curl_exec($ch);
            curl_close($ch);

            return $content;
        }
        $context = stream_context_create(array(
            parse_url($url, PHP_URL_SCHEME) => array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $post_data
            ),
        ));
        return file_get_contents($url, false, $context);
    }
}
