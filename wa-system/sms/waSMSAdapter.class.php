<?php

abstract class waSMSAdapter
{
    protected $options;

    public function __construct($options = array())
    {
        $this->options = $options;
    }

    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    protected function log($to, $text, $response = '')
    {
        waLog::log('SMS to '.$to.' ('.mb_strlen($text).' chars).'."\nResponse: ".$response, 'sms.log');
    }

    /**
     * @param string $to
     * @param string $text
     * @param string $from - sender
     * @return mixed
     */
    abstract function send($to, $text, $from = null);

    public function getControls()
    {
        return array();
    }

    public function getId()
    {
        return substr(get_class($this), 0, -3);
    }


    public function getInfo()
    {
        $path = wa()->getConfig()->getPath('plugins').'/sms/'.$this->getId();
        $info = include($path.'/lib/config/plugin.php');

        $info['icon'] = wa()->getRootUrl().'wa-plugins/sms/'.$this->getId().'/'.$info['icon'];
        return $info;
    }
}