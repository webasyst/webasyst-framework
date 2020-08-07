<?php

class installerHelper
{
    /**
     *
     * @var waAppSettingsModel
     */
    private static $model;
    /**
     *
     * @var waInstallerApps
     */
    private static $installer;

    private static $counter;

    /**
     * @return waInstallerApps
     * @throws waException
     */
    public static function getInstaller()
    {
        if (!self::$model) {
            self::$model = new waAppSettingsModel();
        }
        if (!self::$installer) {
            $license = self::$model->get('webasyst', 'license', false);
            $token = null;
            if ($token_data = self::$model->get('installer', 'token_data', false)) {
                $token_data = waUtils::jsonDecode($token_data, true);
                if (!empty($token_data['token'])) {
                    $token = $token_data['token'];
                }
            }
            $ttl = 600;
            $locale = wa()->getSetting('locale', wa()->getLocale(), 'webasyst');
            self::$installer = new waInstallerApps($license, $locale, $ttl, !!waRequest::get('refresh'), $token);
        }
        if (waConfig::get('is_template')) {
            throw new waException('installerHelper::getInstaller() is not allowed in template context');
        }
        return self::$installer;
    }

    /**
     *
     * Get hash of installed framework
     * @return string
     */
    public static function getHash()
    {
        return self::getInstaller()->getHash();
    }

    /**
     *
     * Get promo_id of installed framework
     * @return string
     */
    public static function getPromoId()
    {
        return self::getInstaller()->getPromoId();
    }

    /**
     * Get current domain name
     * @return string
     */
    public static function getDomain()
    {
        return self::getSafeInstaller()->getDomain();
    }

    public static function flushCache()
    {
        $path_cache = waConfig::get('wa_path_cache');

        $errors = array();
        if (!waSystemConfig::systemOption('cache_versioning')) {
            $paths = waFiles::listdir($path_cache);
            $root_path = wa()->getConfig()->getRootPath().DIRECTORY_SEPARATOR;
            foreach ($paths as $path) {
                $path = $path_cache.'/'.$path;
                if (is_dir($path)) {
                    try {
                        waFiles::delete($path);
                    } catch (Exception $ex) {
                        $errors[] = str_replace($root_path, '', $ex->getMessage());
                    }
                }
            }
        }

        if (!wa()->getConfig()->clearCache()) {
            if ($errors) {
                return $errors;
            } else {
                return array(_ws('Unable to delete certain files'));
            }
        } else {
            return array(); // went fine the second time
        }
    }

    public static function checkUpdates(&$messages)
    {
        try {
            self::getInstaller()->checkUpdates();
        } catch (Exception $ex) {
            $text = $ex->getMessage();
            $message = array('text' => $text, 'result' => 'fail');
            if (strpos($text, "\n")) {
                $texts = array_filter(array_map('trim', explode("\n", $message['text'])), 'strlen');
                while ($texts) {
                    $message['text'] = array_shift($texts);
                    $messages[] = $message;
                }
            } else {
                $messages[] = $message;
            }
        }

    }

    /**
     * @param array $options
     * @param array $filter
     * @param array [string]string $filter['extras'] select apps with specified extras type
     * @return array
     * @throws Exception
     */
    public static function getApps($options, $filter = array())
    {
        return self::getInstaller()->getApps($options, $filter);
    }

    public static function getUpdatesCounter($field = 'total')
    {
        if (empty(self::$counter)) {
            self::getUpdates();
        }
        return $field ? self::$counter[$field] : self::$counter;
    }

    public static function getUpdates($vendor = null)
    {
        static $items = null;
        $config = wa('installer')->getConfig();
        if ($items === null) {
            self::$counter = array(
                'total'      => 0,
                'applicable' => 0,
                'payware'    => 0,
            );
            $app_settings_model = new waAppSettingsModel();
            $errors = (array)json_decode($app_settings_model->get('installer', 'errors', '[]'));
            $items = self::getInstaller()->getUpdates($vendor);
            $changed = false;
            $actions = array(
                waInstallerApps::ACTION_UPDATE,
                waInstallerApps::ACTION_CRITICAL_UPDATE,
                waInstallerApps::ACTION_INSTALL,
            );
            foreach ($items as $id => &$item) {
                if (isset($item['version'])) {
                    if (in_array($item['action'], $actions, true)) {
                        ++self::$counter['total'];
                        if (!empty($item['applicable']) && (empty($item['commercial']) || !empty($item['purchased']))) {
                            ++self::$counter['applicable'];
                        }

                        if (!empty($item['commercial']) && empty($item['purchased'])) {
                            ++self::$counter['payware'];
                        }
                    }
                }

                if (!empty($item['error'])) {
                    if (!$errors) {
                        $model = new waAnnouncementModel();
                        $data = array(
                            'app_id'   => 'installer',
                            'text'     => $item['error'],
                            'datetime' => date('Y-m-d H:i:s', time() - 86400),
                        );
                        if (!$model->select('COUNT(1) `cnt`')->where('app_id=s:app_id AND datetime > s:datetime', $data)->fetchField('cnt')) {
                            $data['datetime'] = date('Y-m-d H:i:s');
                            $model->insert($data);
                        }
                    }

                    $errors[$id] = true;
                    $changed = true;
                } elseif (!empty($errors[$id])) {
                    unset($errors[$id]);
                    $changed = true;
                }

                foreach (array('themes', 'plugins', 'widgets') as $extras) {
                    if (isset($item[$extras])) {
                        self::$counter['total'] += count($item[$extras]);
                        foreach ($item[$extras] as $extras_id => $extras_item) {
                            if (!empty($extras_item['inbuilt'])) {
                                if (empty($item['applicable'])) {
                                    --self::$counter['total'];
                                    unset($item[$extras][$extras_id]);
                                } elseif (!empty($extras_item['applicable']) && (empty($extras_item['commercial']) || !empty($extras_item['purchased']))) {
                                    ++self::$counter['applicable'];
                                }
                            } elseif (!empty($extras_item['applicable']) && (empty($extras_item['commercial']) || !empty($extras_item['purchased']))) {
                                ++self::$counter['applicable'];
                            }
                        }
                    }
                }
                unset($item);
            }
            if ($changed) {
                $app_settings_model->ping();
                $app_settings_model->set('installer', 'errors', json_encode($errors));
            }

            if ($errors) {
                $count = '!';
            } elseif (self::$counter['total']) {
                $count = self::$counter['total'];
            } else {
                $count = null;
            }

            $config->setCount($count);
        }
        return $items;
    }

    public static function overdue($slug = null)
    {
        static $errors;
        if (!isset($errors)) {
            $app_settings_model = new waAppSettingsModel();
            $errors = (array)json_decode($app_settings_model->get('installer', 'errors', '[]'));
        }
        return $slug ? !empty($errors[$slug]) : !empty($errors);
    }

    public static function isDeveloper()
    {
        if (waSystemConfig::systemOption('installer_in_developer_mode')) {
            return false;
        }
        $result = false;
        $paths = array();
        $paths[] = dirname(__FILE__).'/.svn';
        $paths[] = dirname(__FILE__).'/.git';
        $root_path = wa()->getConfig()->getRootPath();
        $paths[] = $root_path.'/.svn';
        $paths[] = $root_path.'/.git';
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /**
     *
     * Search first entry condition
     * @param array $items
     * @param array $filter
     * @param bool  $return_key
     * @return mixed
     */
    public static function &search($items, $filter, $return_key = false)
    {
        $matches = array();

        foreach ($items as $key => $item) {
            $matched = true;
            foreach ($filter as $field => $value) {
                if ($value) {
                    if (is_array($value)) {
                        if (!in_array($item[$field], $value)) {
                            $matched = false;
                            break;
                        }
                    } elseif ($item[$field] != $value) {
                        $matched = false;
                        break;
                    }
                }
            }
            if ($matched) {
                $matches[] = $return_key ? $key : $items[$key];
            }
        }
        return $matches;
    }

    /**
     *
     * Compare arrays by specified fields
     * @param array $a
     * @param array $b
     * @param array $fields
     * @return bool
     */
    public static function equals($a, $b, $fields = array('vendor', 'edition'))
    {
        $equals = true;
        foreach ($fields as $field) {
            if (empty($a[$field]) && empty($b[$field])) {
                /*do nothing*/
            } elseif ($a[$field] != $b[$field]) {
                $equals = false;
                break;
            }
        }

        return $equals;
    }

    /**
     * @return string
     */
    public static function getModule()
    {
        $module = 'apps';
        $url = parse_url(waRequest::server('HTTP_REFERER'), PHP_URL_QUERY);
        if (preg_match('/(^|&)module=(update|apps|plugins|widgets)($|&)/', $url, $matches)) {
            $module = $matches[2];
        }
        return $module;
    }

    /**
     * @param Exception $ex
     * @param array     $messages
     * @throws Exception
     */
    public static function handleException($ex, &$messages)
    {
        $message = $ex->getMessage();
        waLog::log($message, 'installer.log');
        if (preg_match('@\b\[?(https?://[^\s]+)\]?\b@', $message, $matches)) {
            $message = str_replace($matches[1], parse_url($matches[1], PHP_URL_HOST), $message);
        }

        if ($messages === null) {
            throw $ex;
        } else {
            $messages[] = array(
                'text'   => $message,
                'result' => 'fail',
            );
        }
    }

    public static function getOneStringKey($dkim_pub_key)
    {
        $one_string_key = trim(preg_replace('/^\-{5}[^\-]+\-{5}(.+)\-{5}[^\-]+\-{5}$/s', '$1', trim($dkim_pub_key)));
        //$one_string_key = str_replace('-----BEGIN PUBLIC KEY-----', '', $dkim_pub_key);
        //$one_string_key = trim(str_replace('-----END PUBLIC KEY-----', '', $one_string_key));
        $one_string_key = preg_replace('/\s+/s', '', $one_string_key);
        return $one_string_key;
    }

    public static function getDkimSelector($email)
    {
        $e = explode('@', $email);
        return trim(preg_replace('/[^a-z0-9]/i', '', $e[0])).'wamail';
    }

    public static function getDesignUrl($app_id)
    {
        $url = null;
        $class_name = sprintf('%sDesignActions', $app_id);
        wa($app_id);
        if (class_exists($class_name)) {
            /** @var waDesignActions $instance */
            $instance = new $class_name();
            $url = $instance->getDesignUrl();
        }
        return $url;
    }

    private static function getSafeInstaller()
    {
        try {
            self::getInstaller();
        } catch (waException $e) {
        }
        return self::$installer;
    }
}
