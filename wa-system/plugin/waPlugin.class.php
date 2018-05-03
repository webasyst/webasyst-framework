<?php

class waPlugin
{
    protected $id;
    protected $app_id;
    protected $info = array();
    protected $path;
    /**
     * @var string Path to folder with plugin log files.
     */
    protected $logs_path;

    /**
     * @var waAppSettingsModel
     */
    protected static $app_settings_model;

    /**
     * @var mixed[string]
     */
    protected $settings;
    /**
     * @var mixed[string]
     */
    protected $settings_config;

    /**
     * @var mixed[string] App defined settings list
     */
    protected $common_settings_config = array();


    public function __construct($info)
    {
        $this->info = $info;
        $this->id = $this->info['id'];
        if (isset($this->info['app_id'])) {
            $this->app_id = $this->info['app_id'];
        } else {
            $this->app_id = waSystem::getInstance()->getApp();
        }
        $this->path = wa()->getAppPath('plugins/'.$this->id, $this->app_id);
        $this->logs_path = $this->app_id.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$this->id.DIRECTORY_SEPARATOR;

        $this->checkUpdates();
    }
    
    /**
     * @param string $name Log name.
     * @return string Path to log file.
     */
    public function getLogPath($name)
    {
        $name = empty($name) ? 'errors' : rtrim($name, '.log');

        return $this->logs_path.$name.'.log';
    }
    
    /**
     * @param mixed $message Message to be logged.
     * @param string|null $name Log name. Set null for use called method as name.
     * @return bool Logging completion status.
     */
    protected function addToLogFile($message, $name = null)
    {
        $message = (string) $message;
        
        if (is_null($name)) {
            $backtrace = debug_backtrace();
            $name = ifset($backtrace, 1, 'function', '');
        }

        return waLog::log($message, $this->getLogPath($name));
    }
    
    /** @since 1.8.2 */
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->info['name'];
    }

    public function getVersion()
    {
        $version = isset($this->info['version']) ? $this->info['version'] : '0.0.1';
        if (!empty($this->info['build'])) {
            $version .= '.'.$this->info['build'];
        }
        return $version;
    }

    protected function checkUpdates()
    {
        $app_settings_model = new waAppSettingsModel();
        $time = $app_settings_model->get(array($this->app_id, $this->id), 'update_time');
        if (!$time) {
            try {
                $this->install();
            } catch (Exception $e) {
                $this->addToLogFile($e->getMessage());
                throw $e;
            }
            $ignore_all = true;
        } else {
            $ignore_all = false;
        }

        $is_debug = waSystemConfig::isDebug();

        if (!$is_debug) {
            $cache = new waVarExportCache('updates', -1, $this->app_id.".".$this->id);
            if ($cache->isCached() && $cache->get() <= $time) {
                return;
            }
        }
        $path = $this->path.'/lib/updates';
        $cache_database_dir = wa()->getConfig()->getPath('cache').'/db';
        if (file_exists($path)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
            $files = array();
            foreach ($iterator as $file) {
                /**
                 * @var SplFileInfo $file
                 */
                if ($file->isFile() && preg_match('/^[0-9]+\.php$/', $file->getFilename())) {
                    $t = substr($file->getFilename(), 0, -4);
                    if ($t > $time) {
                        $files[$t] = $file->getPathname();
                    }
                }
            }
            ksort($files);
            if (!$is_debug && !empty($cache)) {
                // get last time
                if ($files) {
                    $keys = array_keys($files);
                    $cache->set(end($keys));
                } else {
                    $cache->set($time ? $time : 1);
                }
            }
            foreach ($files as $t => $file) {
                try {
                    if (!$ignore_all) {
                        $this->includeUpdate($file);
                        waFiles::delete($cache_database_dir);
                        $app_settings_model->set(array($this->app_id, $this->id), 'update_time', $t);
                    }
                } catch (Exception $e) {
                    // log errors
                    $this->addToLogFile($e);
                    if ($is_debug) {
                        echo $e;
                    }
                    break;
                }
            }
        } else {
            $t = 1;
        }

        if ($ignore_all) {
            if (!isset($t) || !$t) {
                $t = 1;
            }
            $app_settings_model->set(array($this->app_id, $this->id), 'update_time', $t);
        }
    }

    /**
     * @param string $file
     */
    private function includeUpdate($file)
    {
        include($file);
    }

    protected function install()
    {

        $file_db = $this->path.'/lib/config/db.php';
        if (file_exists($file_db)) {
            $schema = include($file_db);
            $model = new waModel();
            $model->createSchema($schema);
        }
        // check install.php
        $file = $this->path.'/lib/config/install.php';
        if (file_exists($file)) {
            $app_id = $this->app_id;
            /**
             * @var string $app_id
             */
            include($file);
            // clear db scheme cache, see waModel::getMetadata
            try {
                // remove files
                $path = waConfig::get('wa_path_cache').'/db/';
                waFiles::delete($path, true);
            } catch (waException $e) {
                $this->addToLogFile($e);
            }
            // clear runtime cache
            waRuntimeCache::clearAll();
        }
    }

    public function uninstall($force = false)
    {
        // check uninstall.php
        $file = $this->path.'/lib/config/uninstall.php';
        if (file_exists($file)) {
            try {
                include($file);

            } catch (Exception $ex) {
                if ($force) {
                    $this->addToLogFile(sprintf(
                        "Error while uninstall %s at %s: %s", 
                        $this->id, 
                        $this->app_id, 
                        $ex->getMessage()
                    ));
                } else {
                    throw $ex;
                }
            }
        }

        $file_db = $this->path.'/lib/config/db.php';
        if (file_exists($file_db)) {
            $schema = include($file_db);
            $model = new waModel();
            foreach ($schema as $table => $fields) {
                $sql = "DROP TABLE IF EXISTS ".$table;
                $model->exec($sql);
            }
        }
        // Remove plugin settings
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->del($this->app_id.".".$this->id);

        if (!empty($this->info['rights'])) {
            // Remove rights to plugin
            $contact_rights_model = new waContactRightsModel();
            $sql = "DELETE FROM ".$contact_rights_model->getTableName()."
                    WHERE app_id = s:app_id AND (
                        name = '".$contact_rights_model->escape('plugin.'.$this->id)."' OR
                        name LIKE '".$contact_rights_model->escape('plugin.'.$this->id).".%'
                    )";
            $contact_rights_model->exec($sql, array('app_id' => $this->app_id));
        }

        // Remove cache of the application
        waFiles::delete(wa()->getAppCachePath('', $this->app_id));
    }


    public function getPluginStaticUrl($absolute = false)
    {
        return wa()->getAppStaticUrl($this->app_id, $absolute).'plugins/'.$this->id.'/';
    }

    public function getRights($name = '', $assoc = true)
    {
        $right = 'plugin.'.$this->id;
        if ($name) {
            $right .= '.'.$name;
        }
        return wa()->getUser()->getRights(wa()->getConfig()->getApplication(), $right, $assoc);
    }

    public function rightsConfig(waRightConfig $rights_config)
    {
        $rights_config->addItem('plugin.'.$this->id, $this->info['name'], 'checkbox');
    }

    protected function getUrl($url, $is_plugin)
    {
        if ($is_plugin) {
            return 'plugins/'.$this->id.'/'.$url;
        } else {
            return $url;
        }
    }

    protected function addJs($url, $is_plugin = true)
    {
        if (false === strpos($url, '?')) {
            $url .= '?'.$this->getVersion();
            if (waSystemConfig::isDebug()) {
                $url .= '.'.time();
            }
        }
        waSystem::getInstance()->getResponse()->addJs($this->getUrl($url, $is_plugin), $this->app_id);
    }

    protected function addCss($url, $is_plugin = true)
    {
        if (false === strpos($url, '?')) {
            $url .= '?'.$this->getVersion();
            if (waSystemConfig::isDebug()) {
                $url .= '.'.time();
            }
        }
        waSystem::getInstance()->getResponse()->addCss($this->getUrl($url, $is_plugin), $this->app_id);
    }

    public function routing($route = array())
    {
        $file = $this->path.'/lib/config/routing.php';
        if (file_exists($file)) {
            /**
             * @var array $route Variable available at routing file
             */
            return include($file);
        } else {
            return array();
        }
    }


    /**
     * @param array $params Control items params (see waHtmlControl::getControl for details)
     * @return string[string] Html code of control
     */
    public function getControls($params = array())
    {
        $controls = array();
        $settings_config = $this->getSettingsConfig();
        foreach ($settings_config as $name => $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!empty($params['subject']) && !empty($row['subject']) && !in_array($row['subject'], (array)$params['subject'])) {
                continue;
            }
            $row = array_merge($row, $params);
            $row['value'] = $this->getSettings($name);
            if (!empty($row['control_type'])) {
                $controls[$name] = waHtmlControl::getControl($row['control_type'], $name, $row);
            }
        }
        return $controls;
    }

    /**
     * @param null $name
     * @return array|mixed|null|string
     */
    public function getSettings($name = null)
    {
        if ($this->settings === null) {
            $this->settings = self::getSettingsModel()->get($this->getSettingsKey());
            foreach ($this->settings as $key => $value) {
                #decode non string values
                if (!is_numeric($value)) {
                    $json = json_decode($value, true);
                    if (is_array($json)) {
                        $this->settings[$key] = $json;
                    }
                }
            }
            #merge user settings from database with raw default settings
            $settings_config = $this->getSettingsConfig();
            if ($settings_config) {
                foreach ($settings_config as $key => $row) {
                    if (!isset($this->settings[$key])) {
                        $this->settings[$key] = is_array($row) ? (isset($row['value']) ? $row['value'] : null) : $row;
                    }
                }
            }
        }
        if ($name === null) {
            return $this->settings;
        } else {
            return isset($this->settings[$name]) ? $this->settings[$name] : null;
        }
    }

    /**
     * Get raw settings config
     * @return array
     */
    protected function getSettingsConfig()
    {
        if (is_null($this->settings_config)) {
            $path = $this->path.'/lib/config/settings.php';
            if (file_exists($path)) {
                $settings_config = include($path);
                if (!is_array($settings_config)) {
                    $settings_config = array();
                }
            } else {
                $settings_config = array();
            }
            $this->settings_config = array_merge($this->common_settings_config, $settings_config);
        }
        return $this->settings_config;
    }

    /**
     * @param mixed [string] $settings Array of settings key=>value
     * @return void|array
     */
    public function saveSettings($settings = array())
    {
        $settings_config = $this->getSettingsConfig();
        foreach ($settings_config as $name => $row) {
            if (!isset($settings[$name])) {
                if ((ifset($row['control_type']) == waHtmlControl::CHECKBOX) && !empty($row['value'])) {
                    $settings[$name] = false;
                } elseif ((ifset($row['control_type']) == waHtmlControl::GROUPBOX) && !empty($row['value'])) {
                    $settings[$name] = array();
                } elseif (!empty($row['control_type']) || isset($row['value'])) {
                    $this->settings[$name] = isset($row['value']) ? $row['value'] : null;
                    self::getSettingsModel()->del($this->getSettingsKey(), $name);
                }
            }
        }
        foreach ($settings as $name => $value) {
            $this->settings[$name] = $value;
            // save to db
            self::getSettingsModel()->set($this->getSettingsKey(), $name, is_array($value) ? json_encode($value) : $value);
        }
    }

    /**
     * @return waAppSettingsModel
     */
    protected static function getSettingsModel()
    {
        if (!self::$app_settings_model) {
            self::$app_settings_model = new waAppSettingsModel();
        }
        return self::$app_settings_model;
    }

    protected function getSettingsKey()
    {
        return array($this->app_id, $this->id);
    }
}
