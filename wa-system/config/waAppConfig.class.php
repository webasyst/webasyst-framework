<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage config
 */
class waAppConfig extends SystemConfig
{
    protected $application = null;
    protected $info = array();
    protected $log_actions = null;
    protected $prefix;
    protected $plugins = null;
    protected $themes = null;
    protected $options = array();
    protected $routes = null;
    protected $loaded_locale = null;

    public function __construct($environment, $root_path, $application = null, $locale = null)
    {
        if ($application) {
            $this->application = $application;
        } else {
            $this->application = substr(get_class($this), 0, -6);
        }
        parent::__construct($environment, $root_path);
        if ($locale) {
            $this->setLocale($locale);
        }

        $this->checkUpdates();
    }

    public function getApplication()
    {
        return $this->application;
    }

    public function getLogActions()
    {
        if ($this->log_actions === null) {
            $path = $this->getAppPath().'/lib/config/logs.php';
            if (file_exists($path)) {
                $this->log_actions = include($path);
            } else {
                $this->log_actions = array();
            }
            // add system actions for design and pages
            if (!empty($this->info['themes'])) {
                $actions = array('template_add', 'template_edit', 'template_delete',
                    'theme_upload', 'theme_download', 'theme_delete', 'theme_reset', 'theme_duplicate', 'theme_rename', );
                foreach ($actions as $action) {
                    if (!isset($this->log_actions[$action])) {
                        $this->log_actions[$action] = array();
                    }
                }
            }
            if (!empty($this->info['pages'])) {
                $actions = array('page_add', 'page_edit', 'page_delete', 'page_move');
                foreach ($actions as $action) {
                    if (!isset($this->log_actions[$action])) {
                        $this->log_actions[$action] = array();
                    }
                }
            }
        }
        return $this->log_actions;
    }

    protected function configure()
    {

    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption($name = null)
    {
        if (!$name) {
            return $this->options;
        }
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function init()
    {
        $files = array(
            $this->getAppPath().'/lib/config/config.php', // defaults
            $this->getPath('config').'/apps/'.$this->application.'/config.php' // custom
            );
        foreach ($files as $file_path) {
            if (file_exists($file_path)) {
                $config = include($file_path);
                if ($config && is_array($config)) {
                    foreach ($config as $name => $value) {
                        $this->options[$name] = $value;
                    }
                }
            }
        }

        $this->info = include($this->getAppPath().'/lib/config/app.php');
        if (wa()->getEnv() == 'backend' && isset($this->info['csrf']) && $this->info['csrf'] && waRequest::method() == 'post') {
            if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
                throw new waException('CSRF Protection', 403);
            }
        }
        waAutoload::getInstance()->add($this->getClasses());

        if (file_exists($this->getAppPath().'/lib/config/factories.php')) {
            $this->factories = include($this->getAppPath().'/lib/config/factories.php');
        }
    }

    protected function checkUpdates()
    {
        try {
            $app_settings_model = new waAppSettingsModel();
            $time = $app_settings_model->get($this->application, 'update_time');
        } catch (waDbException $e) {
            if ($e->getCode() == 2002 && !waSystemConfig::isDebug()) {
                return;
            } else {
                // table doesn't exist
                $time = null;
            }
        } catch (waException $e) {
            return;
        }
        if (!$time) {
            try {
                $this->install();
            } catch (waException $e) {
                waLog::log($e->__toString());
                throw $e;
            }
            $ignore_all = true;
        } else {
            $ignore_all = false;
        }

        if (!self::isDebug()) {
            $cache = new waVarExportCache('updates', 0, $this->application);
            if ($cache->isCached() && $cache->get() <= $time) {
                return;
            }
        }
        $path = $this->getAppPath().'/lib/updates';
        $cache_database_dir = $this->getPath('cache').'/db';
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
            if (!self::isDebug()) {
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
                        include($file);
                        waFiles::delete($cache_database_dir);
                        $app_settings_model->set($this->application, 'update_time', $t);
                    }
                } catch (Exception $e) {
                    if (waSystemConfig::isDebug()) {
                        echo $e;
                    }
                    // log errors
                    waLog::log($e->__toString());
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
            if (!isset($app_settings_model)) {
                $app_settings_model = new waAppSettingsModel();
            }
            $app_settings_model->set($this->application, 'update_time', $t);
        }

        if (isset($this->info['edition']) && $this->info['edition']) {
            if (!isset($app_settings_model)) {
                $app_settings_model = new waAppSettingsModel();
            }
            if (!$app_settings_model->get($this->application, 'edition')) {
                $file_sql = $this->getAppPath('lib/config/app.'.$this->info['edition'].'.sql');
                if (file_exists($file_sql)) {
                    self::executeSQL($file_sql, 1);
                }
                $app_settings_model->set($this->application, 'edition', $this->info['edition']);
            }
        }

    }

    public function install()
    {
        $file_db = $this->getAppPath('lib/config/db.php');
        if (file_exists($file_db)) {
            $schema = include($file_db);
            $model = new waModel();
            $model->createSchema($schema);
        } else {
            // check app.sql
            $file_sql = $this->getAppPath('lib/config/app.sql');
            if (file_exists($file_sql)) {
                self::executeSQL($file_sql, 1);
            }
        }
        $file = $this->getAppConfigPath('install');
        if (file_exists($file)) {
            $app_id = $this->application;
            include($file);
        }
    }

    /**
     * Execute sql from file
     *
     * @static
     * @param string $file_sql
     * @param int $type
     *          0 - execute all queries,
     *          1 - ignore drop table,
     *          2 - execute only drop table
     * @return void
     */
    public static function executeSQL($file_sql, $type = 0)
    {
        $sqls = file_get_contents($file_sql);
        $sqls = preg_split("/;\r?\n/", $sqls);
        $model = new waModel();
        foreach ($sqls as $sql) {
            if (trim($sql)) {
                // ignore drop table
                if ($type == 1 && preg_match('/drop[\s\t\r\n]+table/is', $sql)) {
                    continue;
                }
                // execute only drop table
                elseif ($type == 2 && !preg_match('/drop[\s\t\r\n]+table/is', $sql)) {
                    continue;
                }
                $model->exec($sql);
            }
        }
    }

    public function uninstall()
    {
        // check uninstall.php
        $file = $this->getAppConfigPath('uninstall');
        if (file_exists($file)) {
            include($file);
        }

        $file_db = $this->getAppPath('lib/config/db.php');
        if (file_exists($file_db)) {
            $schema = include($file_db);
            $model = new waModel();
            foreach ($schema as $table => $fields) {
                $sql = "DROP TABLE IF EXISTS ".$table;
                $model->exec($sql);
            }
        } else {
            // check app.sql
            $file_sql = $this->getAppPath('lib/config/app.sql');
            if (file_exists($file_sql)) {
                self::executeSQL($file_sql, 2);
            }
        }
        // Remove all app settings
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->del($this->application);

        $contact_settings_model = new waContactSettingsModel();
        $contact_settings_model->deleteByField('app_id', $this->application);
        // Remove all rights to app
        $contact_rights_model = new waContactRightsModel();
        $contact_rights_model->deleteByField('app_id', $this->application);
        // Remove logs
        $log_model = new waLogModel();
        $log_model->deleteByField('app_id', $this->application);
        // Remove cache
        waFiles::delete($this->getPath('cache').'/apps/'.$this->application);
    }

    public function setLocale($locale, $bind = true)
    {
        if ($this->loaded_locale != $locale) {
            $this->loaded_locale = $locale;
            waLocale::load($locale, $this->getAppPath('locale'), $this->application, $bind);
        }
        if ($bind && waLocale::getDomain() != $this->application) {
            waLocale::load($locale, $this->getAppPath('locale'), $this->application, $bind);
        }
    }

    public function getClasses()
    {
        $cache_file = waConfig::get('wa_path_cache').'/apps/'.$this->application.'/config/autoload.php';
        if (self::isDebug() || !file_exists($cache_file)) {
            waFiles::create(waConfig::get('wa_path_cache').'/apps/'.$this->application.'/config');
            $paths = array($this->getAppPath().'/lib/');
            if (file_exists($this->getAppPath().'/plugins')) {
                $paths[] = $this->getAppPath().'/plugins/';
            }
            if (file_exists($this->getAppPath().'/api')) {
                $v = waRequest::request('v', 1, 'int');
                if (file_exists($this->getAppPath().'/api/v'.$v)) {
                    $paths[] = $this->getAppPath().'/api/v'.$v.'/';
                }
            }
            $result = array();
            $length = strlen($this->getRootPath());
            foreach ($paths as $path) {
                $files = $this->getPHPFiles($path);
                foreach ($files as $file) {
                    $class = $this->getClassByFilename(basename($file));
                    if ($class) {
                        $result[$class] = substr($file, $length + 1);
                    }
                }
            }
            if (!file_exists($cache_file)) {
                waUtils::varExportToFile($result, $cache_file);
            }
            return $result;
        }
        return include($cache_file);
    }

    protected function getPHPFiles($path)
    {
        if (!($dh = opendir($path))) {
            throw new waException('Filed to open dir: '.$path);
        }
        $result = array();
        while (($f = readdir($dh)) !== false) {
            if ($this->isIgnoreFile($f)) {
                continue;
            } elseif (is_dir($path.$f)) {
                $result = array_merge($result, $this->getPHPFiles($path.$f.'/'));
            } elseif (substr($f, -4) == '.php') {
                $result[] = $path.$f;
            }
        }
        closedir($dh);
        return $result;
    }

    protected function isIgnoreFile($f)
    {
        return $f === '.' || $f === '..' || $f === '.svn';
    }

    protected function getClassByFilename($filename)
    {
        $file_parts = explode('.', $filename);
        if (count($file_parts) <= 2) {
            return false;
        }
        $class = null;
        switch ($file_parts[1]) {
            case 'class':
                return $file_parts[0];
            default:
                $result = $file_parts[0];
                for ($i = 1; $i < count($file_parts) - 1; $i++) {
                    $result .= ucfirst($file_parts[$i]);
                }
                return $result;
        }
    }

    public function getAppPath($path = null)
    {
        return $this->getRootPath().DIRECTORY_SEPARATOR.'wa-apps'.DIRECTORY_SEPARATOR.$this->application.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    public function getAppConfigPath($name)
    {
        return $this->getAppPath("lib/config/".$name.".php");
    }

    public function getConfigPath($name, $user_config = true, $app = null)
    {
        if ($app === null) {
            $app = $this->application;
        }
        return parent::getConfigPath($name, $user_config, $app);
    }

    public function getRouting($route = array())
    {
        if ($this->routes === null) {
            $path = $this->getConfigPath('routing.php', true, $this->application);
            if (!file_exists($path)) {
                $path = $this->getConfigPath('routing.php', false, $this->application);
            }
            if (file_exists($path)) {
                $this->routes = include($path);
            } else {
                $this->routes = array();
            }
        }
        return $this->routes;
    }

    public function getPrefix()
    {
        if (!$this->prefix) {
            $this->prefix = $this->getInfo('prefix');
            if (!$this->prefix) {
                $this->prefix = $this->getApplication();
            }
        }
        return $this->prefix;
    }

    public function getName()
    {
        return $this->getInfo('name');
    }

    public function getInfo($name = null)
    {
        if ($name === null) {
            return $this->info;
        } else {
            return isset($this->info[$name]) ? $this->info[$name] : null;
        }
    }

    public function getPluginPath($plugin_id)
    {
        return $this->getAppPath()."/plugins/".$plugin_id;
    }

    public function getPluginInfo($plugin_id)
    {
        if ($this->plugins === null) {
            $this->getPlugins();
        }
        return isset($this->plugins[$plugin_id]) ? $this->plugins[$plugin_id] : array();
    }

    public function getPlugins()
    {
        if ($this->plugins === null) {
            $locale = wa()->getLocale();
            $file = waConfig::get('wa_path_cache')."/apps/".$this->application.'/config/plugins.'.$locale.'.php';
            if (!file_exists($file) || SystemConfig::isDebug()) {
                waFiles::create(waConfig::get('wa_path_cache')."/apps/".$this->application.'/config');
                // read plugins from file wa-config/[APP_ID]/plugins.php
                $path = $this->getConfigPath('plugins.php', true);
                if (!file_exists($path)) {
                    $this->plugins = array();
                    return $this->plugins;
                }
                $all_plugins = include($path);
                $this->plugins = array();
                foreach ($all_plugins as $plugin_id => $enabled) {
                    if ($enabled) {
                        $plugin_config = $this->getPluginPath($plugin_id)."/lib/config/plugin.php";
                        if (!file_exists($plugin_config)) {
                            continue;
                        }
                        $plugin_info = include($plugin_config);
                        waSystem::pushActivePlugin($plugin_id, $this->application);
                        // Load plugin locale if it exists
                        $locale_path = wa()->getAppPath('plugins/'.$plugin_id.'/locale', $this->application);
                        if (is_dir($locale_path)) {
                            waLocale::load($locale, $locale_path, wa()->getActiveLocaleDomain(), false);
                        }
                        $plugin_info['name'] = _wp($plugin_info['name']);
                        if (isset($plugin_info['title'])) {
                            $plugin_info['title'] = _wp($plugin_info['title']);
                        }
                        if (isset($plugin_info['description'])) {
                            $plugin_info['description'] = _wp($plugin_info['description']);
                        }
                        waSystem::popActivePlugin();
                        $plugin_info['id'] = $plugin_id;
                        if (isset($plugin_info['img'])) {
                            $plugin_info['img'] = 'wa-apps/'.$this->application.'/plugins/'.$plugin_id.'/'.$plugin_info['img'];
                        }
                        if (isset($plugin_info['rights']) && $plugin_info['rights']) {
                            $plugin_info['handlers']['rights.config'] = 'rightsConfig';
                        }
                        if (isset($plugin_info['frontend']) && $plugin_info['frontend']) {
                            $plugin_info['handlers']['routing'] = 'routing';
                        }
                        $this->plugins[$plugin_id] = $plugin_info;
                    }
                }
                waUtils::varExportToFile($this->plugins, $file);
            } else {
                $this->plugins = include($file);
            }
        }
        return $this->plugins;
    }

    /**
     *
     * Update general plugin sort
     * @param string $plugin plugin id
     * @param int $sort 0 is first
     */
    public function setPluginSort($plugin, $sort)
    {
        $path = $this->getConfigPath('plugins.php', true);
        if (file_exists($path) && ($plugins = include($path)) && !empty($plugins[$plugin])) {
            $sort = max(0, min(intval($sort), count($plugins) - 1));
            $order = array_flip(array_keys($plugins));
            if ($order[$plugin] != $sort) {
                $b = array($plugin => $plugins[$plugin]);
                unset($plugins[$plugin]);
                $a = array_slice($plugins, 0, $sort, true);
                $c = array_slice($plugins, $sort, null, true);
                $plugins = array_merge($a, $b, $c);
                if (waUtils::varExportToFile($plugins, $path)) {
                    waFiles::delete(waConfig::get('wa_path_cache')."/apps/".$this->application.'/config', true);
                } else {
                    throw new waException("Fail while update plugins sort order");
                }
            }
        }
    }

    public function checkRights($module, $action)
    {
        return true;
    }

    public function onCount()
    {
        return null;
    }

    public function setCount($n = null)
    {
        $count = wa()->getStorage()->get('apps-count');
        if (!$count) {
            $count = array();
        }
        if ($n) {
            $count[$this->application] = $n;
            wa()->getStorage()->set('apps-count', $count);
        } elseif ($count && isset($count[$this->application])) {
            unset($count[$this->application]);
            wa()->getStorage()->set('apps-count', $count);
        }
    }
}
