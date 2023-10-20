<?php

class waPlugin
{
    protected $id;
    protected $app_id;
    protected $info = array();
    protected $path;

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

        $this->checkUpdates();
    }

    /**
     * Returns plugin ID.
     * @return string
     * @since 1.8.2
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns localized plugin name.
     * @return string
     */
    public function getName()
    {
        return $this->info['name'];
    }

    /**
     * Returns plugin's version number.
     * @return string
     */
    public function getVersion()
    {
        $version = isset($this->info['version']) ? $this->info['version'] : '0.0.1';
        if (!empty($this->info['build'])) {
            $version .= '.'.$this->info['build'];
        }
        return $version;
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    protected function checkUpdates()
    {
        $is_from_template = waConfig::get('is_template');
        $disable_exception_log = waConfig::get('disable_exception_log');
        waConfig::set('is_template', null);

        $locking_model = $app_settings_model = new waAppSettingsModel();
        $time = $app_settings_model->get(array($this->app_id, $this->id), 'update_time');

        try {
            if (!$time) {
                $is_first_launch = true;

                // To avoid running install.php multiple times in parallel, obtain a named lock
                // and then read update_time again because it might have changed in another thread
                $locking_model->exec("SELECT GET_LOCK(?, -1)", ["wa_init_plugin_{$this->app_id}_{$this->id}"]);
                $app_settings_model->clearCache(array($this->app_id, $this->id));
                $time = $app_settings_model->get(array($this->app_id, $this->id), 'update_time', null);

                try {
                    if (!$time) {
                        waConfig::set('disable_exception_log', true);
                        $this->install();
                    } else {
                        // All work has already been done in another thread.
                        return;
                    }
                } catch (Exception $e) {
                    waLog::log("Error installing plugin ".$this->app_id.".".$this->id." at first run:\n".$e->getMessage()." (".$e->getCode().")\n".$e->getTraceAsString());
                    throw $e;
                }
            } else {
                $is_first_launch = false;
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

                if ($files && !$is_first_launch) {
                    // To avoid running meta-updates in parallel, obtain a named lock
                    // and then read update_time again because it might have changed in another thread
                    $locking_model->exec("SELECT GET_LOCK(?, -1)", ["wa_init_plugin_{$this->app_id}_{$this->id}"]);
                    $app_settings_model->clearCache(array($this->app_id, $this->id));
                    $time = $app_settings_model->get(array($this->app_id, $this->id), 'update_time', null);
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
                waConfig::set('disable_exception_log', true);
                foreach ($files as $t => $file) {
                    try {
                        if (!$is_first_launch && $time < $t) {
                            $this->includeUpdate($file);
                            waFiles::delete($cache_database_dir);
                            $app_settings_model->set(array($this->app_id, $this->id), 'update_time', $t);
                        }
                    } catch (Exception $e) {
                        waLog::log("Error running update of plugin {$this->app_id}.{$this->id}: {$file}\n".$e->getMessage()." (".$e->getCode().")\n".$e->getTraceAsString());
                        throw new waException(sprintf(_ws('Error while running update of %s.%s plugin: %s'), $this->app_id, $this->id, $file), 500, $e);
                    }
                }
            } else {
                $t = 1;
            }

            if ($is_first_launch) {
                $app_settings_model->set(array($this->app_id, $this->id), 'update_time', ifempty($t, 1));
            }
        } finally {
            waConfig::set('is_template', $is_from_template);
            waConfig::set('disable_exception_log', $disable_exception_log);
            if (!empty($is_first_launch) || !empty($files)) {
                $locking_model->exec("SELECT RELEASE_LOCK(?)", ["wa_init_plugin_{$this->app_id}_{$this->id}"]);
            }
        }
    }

    /**
     * @param string $file
     */
    private function includeUpdate($file)
    {
        /**
         * @var waPlugin $this
         */
        include($file);
    }

    private function includeConfig($file)
    {
        return include($file);
    }

    private function includeCode($file)
    {
        $app_id = $this->app_id;
        /**
         * @var string $app_id
         * @var waPlugin $this
         */
        include($file);
    }

    protected function install()
    {

        $file_db = $this->path.'/lib/config/db.php';
        if (file_exists($file_db)) {
            $schema = $this->includeConfig($file_db);
            $model = new waModel();
            $model->createSchema($schema);
        }
        // check install.php
        $file = $this->path.'/lib/config/install.php';
        if (file_exists($file)) {
            $this->includeCode($file);
            // clear db scheme cache, see waModel::getMetadata
            try {
                // remove files
                $path = waConfig::get('wa_path_cache').'/db/';
                waFiles::delete($path, true);
            } catch (waException $e) {
                waLog::log($e->__toString());
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
                $this->includeCode($file);
            } catch (Exception $ex) {
                if ($force) {
                    waLog::log(sprintf("Error while uninstall %s at %s: %s", $this->id, $this->app_id, $ex->getMessage(), 'installer.log'));
                } else {
                    throw $ex;
                }
            }
        }

        $file_db = $this->path.'/lib/config/db.php';
        if (file_exists($file_db)) {
            $schema = $this->includeConfig($file_db);
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

    /**
     * Returns URL of plugin's root directory.
     * @param bool $absolute Whether absolute URL must be returned.
     * @return string
     */
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

    /**
     * Adds a JavaScript file URL to the array returned by {$wa->js()}.
     * @param string $url JavaScript file URL, relative or absolute, depending on $is_plugin parameter value.
     * @param bool $is_plugin Whether a relative or absolute file URL must be contained in $url parameter.
     * @return null
     */
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

    /**
     * Adds a CSS file URL to the array returned by {$wa->css()}.
     * @param string $url CSS file URL, relative or absolute, depending on $is_plugin parameter value.
     * @param bool $is_plugin Whether a relative or absolute file URL must be contained in $url parameter.
     * @return null
     */
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
     * Returns plugin's settings values.
     * @param string|null $name Optional key to return one setting's value. If empty, all settings' values are returned.
     * @return mixed
     */
    public function getSettings($name = null)
    {
        if ($this->settings === null) {
            $this->settings = self::getSettingsModel()->get($this->getSettingsKey());
            $settings_config = $this->getSettingsConfig();
            foreach ($this->settings as $key => $value) {
                #decode non string values
                if (!is_numeric($value)) {
                    if (
                        isset($settings_config[$key]['control_type'])
                        && in_array($settings_config[$key]['control_type'], [waHtmlControl::INPUT, waHtmlControl::TEXTAREA])
                    ) {
                        continue;
                    }
                    $json = json_decode($value, true);
                    if (is_array($json)) {
                        $this->settings[$key] = $json;
                    }
                }
            }
            #merge user settings from database with raw default settings
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

                } elseif ((ifset($row['control_type']) == waHtmlControl::FILE)) {
                    $settings[$name] = $this->getSettings($name);

                } elseif (!empty($row['control_type']) || isset($row['value'])) {
                    $this->settings[$name] = isset($row['value']) ? $row['value'] : null;
                    self::getSettingsModel()->del($this->getSettingsKey(), $name);

                }

            }
        }

        foreach ($settings as $name => $value) {
            $type_is_file = ifset($settings_config, $name, 'control_type', null) == waHtmlControl::FILE;
            $value_is_file = $value instanceof waRequestFile;
            if ($type_is_file && $value_is_file) {

                /**
                 * @var waRequestFile $file
                 */
                $file = $value;
                if ($file->uploaded()) {

                    $path = wa()->getDataPath(sprintf('plugins/%s/', $this->id), true, $this->app_id);
                    $time = time();
                    $file_name = "{$name}.{$time}.{$file->extension}";
                    if (!file_exists($path) || !is_writable($path)) {
                        $message = _wp('File could not be saved due to the insufficient file write permissions for the %s folder.');
                        throw new waException(sprintf($message, 'wa-data/public/'.$this->app_id.'/data/'));
                    } elseif (!$file->moveTo($path, $file_name)) {
                        throw new waException(_wp('Failed to upload file.'));
                    }
                    $value = $file_name;
                }

            }

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

    /**
     * Render template when root directory of templates is folder "template/" of current application plugin
     * This method take into account which UI version of webasyst is currently set up for current application
     * @param string $scope - scope (root folder without "-legacy" prefix) inside "templates/" of current application plugin
     * @param string $template_path - relative template path inside scope
     * @param array $assign - assign for templates
     * @param string|null|true $cache_id
     * If NULL - without caching
     * If TRUE cache_id is full path of template
     * Otherwise custom cache_id as specified in passed parameter
     * @return mixed
     * @throws waException
     */
    protected function renderTemplate($scope, $template_path, $assign = [], $cache_id = null)
    {
        $scope = trim(trim($scope), '/');
        $full_templates_path = $this->buildFullTemplatePath($scope, $template_path);
        if ($cache_id === true) {
            $cache_id = $full_templates_path;
        }
        return wa()->getView()->renderTemplate($full_templates_path, $assign, false, $cache_id);
    }

    /**
     * Build full template path with taking into account which UI
     * @param string $scope
     * @param string $template_path
     * @return string
     * @throws waException
     */
    protected function buildFullTemplatePath($scope, $template_path)
    {
        $app_id = $this->app_id;
        $plugin_id = $this->id;

        $scope = trim(trim($scope), '/');

        if (wa()->whichUI($app_id) !== '2.0') {
            $scope .= '-legacy';
        }

        $templates_dir = wa()->getAppPath('plugins/' . $plugin_id . '/templates/' . $scope, $app_id);
        return $templates_dir . '/' . $template_path;
    }

    /**
     * @return bool
     * @see waLicensing
     */
    public function isAnyPremiumFeatureEnabled()
    {
        return false;
    }
}
