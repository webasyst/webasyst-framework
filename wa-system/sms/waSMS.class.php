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
     * @return bool|mixed - FALSE means failure sending
     */
    public function send($to, $text, $from = null)
    {
        try {
            $adapter = $this->getAdapter($from);
            $params = array(
                'to' => $to,
                'text' => $text,
                'from' => $from ? $from : $adapter->getOption('from'),
                'adapter' => $adapter,
                'result' => false,
            );
            wa()->event('sms_send.before', $params, array('to', 'text', 'from', 'result', 'adapter'));
            if (empty($params['result'])) {
                $params['result'] = $params['adapter']->send($params['to'], $params['text'], $params['from']);
            }
            wa()->event('sms_send.after', $params);
            return $params['result'];
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
        if (empty(self::$config)) {
            $no_settings_adapter = self::getNoSettingsAdapter();
            if ($no_settings_adapter) {
                self::$config = [ '*' => [ 'adapter' => $no_settings_adapter ] ];
                $path = wa()->getConfig()->getPath('config', 'sms');
                waUtils::varExportToFile(self::$config, $path);
            }
        }
        if (!$from || (!isset(self::$config[$from]) && isset(self::$config['*']))) {
            $from = '*';
        }
        if (isset(self::$config[$from])) {
            $options = self::$config[$from];
        } elseif ($from == '*' && !empty(self::$config)) {
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
        $path = wa()->getConfig()->getPath('plugins').'/sms/'.$options['adapter'].'/lib/'.$class_name.'.class.php';
        if (file_exists($path)) {
            include_once($path);
        }
        if (!class_exists($class_name)) {
            throw new waException('Unable to initialize SMS adapter '.$options['adapter']);
        }
        return new $class_name($options);
    }

    public static function getInstalledAdapterIds()
    {
        $path = wa()->getConfig()->getPath('plugins').'/sms/';
        if (!file_exists($path)) {
            return array();
        }
        $dh = opendir($path);
        $adapters = array();
        while (($f = readdir($dh)) !== false) {
            if ($f === '.' || $f === '..' || !is_dir($path.$f)) {
                continue;
            } elseif (file_exists($path.$f.'/lib/'.$f.'SMS.class.php')) {
                $adapters[] = $f;
            }
        }
        closedir($dh);

        if (class_exists('wadebugSMS') && waSystemConfig::isDebug()) {
            $adapters[] = 'wadebug';
        }

        return $adapters;
    }

    protected static function getNoSettingsAdapter()
    {
        $path = wa()->getConfig()->getPath('plugins').'/sms/';
        if (!file_exists($path)) {
            return null;
        }
        $config_cache = waConfigCache::getInstance();
        $dh = opendir($path);
        $result = null;
        while (($f = readdir($dh)) !== false) {
            if ($f === '.' || $f === '..' || !is_dir($path.$f)) {
                continue;
            }
            $config_file = $path.$f.'/lib/config/plugin.php';
            if (file_exists($config_file)) {
                $config = $config_cache->includeFile($config_file);
                if (!empty($config['no_settings'])) {
                    $result = $f;
                    break;
                }
            }
        }
        closedir($dh);
        return $result;
    }
}
