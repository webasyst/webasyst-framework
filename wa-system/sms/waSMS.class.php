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

    /**
     * @param $to
     * @param $text
     * @param string $from - sender
     * @return bool|mixed
     */
    public function send($to, $text, $from = null)
    {
        try {
            $adapter = $this->getAdapter($from);
            $result = $adapter->send($to, $text, $from ? $from : $adapter->getOption('from'));
            return $result;
        } catch (waException $e) {
            waLog::log($e->getMessage(), 'sms.log');
            return false;
        }
    }

    public static function adapterExists($from = null)
    {
        try {
            $sms = new self();
            return !!$sms->getAdapter($from);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $from
     * @throws waException
     * @return waSMSAdapter
     */
    protected function getAdapter($from = null)
    {
        if (!$from || (!isset(self::$config[$from]) && isset(self::$config['*']))) {
            $from = '*';
        }
        if (isset(self::$config[$from])) {
            $options = self::$config[$from];
        } elseif ($from == '*') {
            $options = reset(self::$config);
            $from = key(self::$config);
        } else {
            throw new waException('SMS sender '.$from.' not configured.');
        }

        if ($from != '*' && !isset($options['from'])) {
            $options['from'] = $from;
        }

        if (!isset($options['adapter'])) {
            throw new waException('SMS sending not configured.');
        }

        $class_name = $options['adapter'].'SMS';
        require_once(wa()->getConfig()->getPath('plugins').'/sms/'.$options['adapter'].'/lib/'.$class_name.'.class.php');
        return new $class_name($options);
    }
}