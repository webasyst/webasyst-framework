<?php

class waSMS
{
    protected static $config;
    protected $from;

    public function __construct()
    {
        if (!self::$config) {
            self::$config = wa()->getConfig()->getConfigFile('sms');
        }
    }

    public function setFrom($from)
    {
        $this->from = $from;
    }

    public function send($to, $text)
    {
        $adapter = $this->getAdapter();
        $result = $adapter->send($to, $text);
        return $result;
    }


    /**
     * @return waSMSAdapter
     */
    protected function getAdapter()
    {
        $from = $this->from;

        if (!$from || !isset(self::$config[$from])) {
            $from = '*';
        }

        if (isset(self::$config[$from])) {
            $options = self::$config[$from];
        } else {
            $options = reset(self::$config);
        }


        $class_name = $options['adapter'].'SMS';
        require_once(wa()->getConfig()->getPath('plugins').'/sms/'.$options['adapter'].'/lib/'.$class_name.'.class.php');
        return new $class_name($options);
    }
}