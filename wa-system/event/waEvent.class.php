<?php

class waEvent
{
    protected $event_app_id = null;
    protected $name = null;
    protected $event_system = null;
    protected $options = array();

    protected $result = array();
    protected $execution_time = array();

    protected static $handlers = null;
    protected static $plugins = null;

    /**
     * waEvent constructor.
     * @param string $app_id
     * @param string $name Event name
     * @param array $options
     * @throws waException
     */
    public function __construct($app_id, $name, $options = array())
    {
        if (wa()->appExists($app_id)) {
            $event_system = wa($app_id);
        } else {
            $event_system = wa();
        }

        $this->name = $name;
        $this->event_app_id = $app_id;
        $this->event_system = $event_system;
        $this->options = $options;
    }

    #######
    # RUN #
    #######

    /**
     * The main method for handling hooks.
     *
     * @param mixed &$params
     * @return array
     * @throws waException
     */
    public function run(&$params = null)
    {
        $this->setStaticData();

        // Super event hook in wa-config/SystemConfig is called for all events
        if (method_exists($this->event_system->getConfig(), 'eventHook')) {
            $r = $this->event_system->getConfig()->eventHook($this->event_app_id, $this->name, $params);
            if (is_array($r)) {
                return $r;
            }
        }
        // Make sure active app stays the same after the event
        $old_app = wa()->getApp();

        $handlers = array_merge(
            ifset(self::$handlers, $this->event_app_id, $this->name, array()),
            ifset(self::$handlers, $this->event_app_id, '*', array()),
            ifset(self::$handlers, '*', $this->name, array()),
            ifset(self::$handlers, '*', '*', array()) //Why, why Mr. Anderson? Think again, you do not need to subscribe to all the hooks
        );

        /**
         * Handler information
         *
         * @var array $handler
         *  $app_id string required
         *  $plugin_id string
         *  $file string path_to_file in wa-apps/app_id/lib/handlers
         *  $object object the object to call
         *  $class string required Class name.
         *  $method string|array required Method or array of methods to call in this class.
         *  $regex string required Regular expression according to the requested event.
         *
         */
        foreach ($handlers as $handler) {
            $start_execution = microtime(true);
            $plugin_id = ifset($handler, 'plugin_id', null);
            $object = ifset($handler, 'object', null);

            if (is_object($object)) {
                $this->result += $this->runCustom($params, $handler);
            } elseif ($plugin_id) {
                $this->result += $this->runPlugins($params, $handler);
            } else {
                $this->result += $this->runApps($params, $handler);
            }

            $this->addExecutionTime($handler, $start_execution);
        }

        $this->logExecutionTime();

        //Return active apps
        wa($old_app, 1);

        // Super event hook in wa-config/SystemConfig is called for all events
        if (method_exists($this->event_system->getConfig(), 'eventHookAfter')) {
            $r = $this->event_system->getConfig()->eventHookAfter($this->event_app_id, $this->name, $params, $this->result);
            if (is_array($r)) {
                return $r;
            }
        }

        return $this->result;
    }

    protected function runCustom(&$params, $handler)
    {
        $object = $handler['object'];
        $methods = $handler['method'];
        $regex = $handler['regex'];

        $result = array();
        if (!$this->isValidEventName($regex)) {
            return $result;
        }

        foreach ($methods as $method) {
            if (is_string($method) && method_exists($object, $method)) {
                $result[get_class($object).'-'.$method.'-plugin'] = $object->{$method}($params, $this->name);
            }
        }

        return $result;
    }

    /**
     * Calls handlers apps. Handles to the first successful result.
     *
     * @param mixed $params
     * @param array $handler
     * @return array
     * @throws waException
     */
    protected function runApps(&$params, $handler)
    {
        $app_id = $handler['app_id'];
        $file = ifset($handler, 'file', null);
        $class = $handler['class'];
        $methods = $handler['method'];
        $regex = $handler['regex'];
        $result = array();

        if (!$this->isValidEventName($regex) || !wa()->appExists($app_id) || isset($this->result[$app_id])) {
            return $result;
        }

        wa($app_id);

        //If you did not find the class, try to include the file and check the class again.
        if (!class_exists($class) && (!$this->includeAppsHandlerFile($app_id, $file) || !class_exists($class))) {
            $this->debugLog('Event handler class does not exist: '.$class);
            return $result;
        }

        /**
         * @var $handler waEventHandler
         */
        wa()->pushActivePlugin(null, $app_id);
        try {
            $apps_handler = new $class();

            foreach ($methods as $method) {
                if (!method_exists($class, $method)) {
                    $this->debugLog("Event handler method does not exist: '.$class.'->'.$method.'()");
                    continue;
                }

                $apps_result = $apps_handler->$method($params, $this->name);

                if ($apps_result !== null) {
                    $result[$app_id] = $apps_result;
                    break;
                }
            }
        } catch (Exception $e) {
            waLog::log('Event handling error in '.$class.': '.$e->getMessage());
        }
        wa()->popActivePlugin();

        return $result;
    }

    /**
     * Causes plugin handlers. Handles to the first successful result. From each plugin, there can be only one result.
     *
     * @param mixed $params
     * @param array $handler
     * @return array
     * @throws waException
     */
    protected function runPlugins(&$params, $handler)
    {
        $app_id = $handler['app_id'];
        $plugin_id = $handler['plugin_id'];
        $class = $handler['class'];
        $methods = $handler['method'];
        $regex = $handler['regex'];
        $result = array();

        $plugin_info = $this->getPluginInfo($app_id, $plugin_id);

        if ($this->event_app_id != $app_id) {
            $result_key = $app_id.'_'.$plugin_id;
        } else {
            $result_key = $plugin_id;
        }

        if (!$this->isValidEventName($regex) || !wa()->appExists($app_id) || isset($this->result[$result_key.'-plugin'])) {
            return $result;
        }

        wa($app_id);

        if (!class_exists($class)) {
            $this->debugLog('Event handler class does not exist: '.$class);
            return $result;
        }

        // Activate _wp() for current plugin
        wa()->pushActivePlugin($plugin_id, $app_id);

        // Load plugin locale
        $this->includePluginLocale($plugin_id, $app_id);

        try {
            $plugin = new $class($plugin_info);

            foreach ($methods as $method) {
                if (!method_exists($class, $method)) {
                    $this->debugLog("Event handler method does not exist: '.$class.'->'.$method.'()");
                    continue;
                }

                $plugin_result = $plugin->$method($params, $this->name);

                if ($plugin_result !== null) {
                    if (isset($this->options['array_keys']) && is_array($plugin_result)) {
                        foreach ($this->options['array_keys'] as $k) {
                            if (!isset($plugin_result[$k])) {
                                $plugin_result[$k] = '';
                            }
                        }
                    }
                    $result[$result_key.'-plugin'] = $plugin_result;

                    // Only one result can be returned per event per plugin.
                    // So we ignore all other methods matched by regex wildcard
                    // after we get a result.
                    break;
                }
            }
        } catch (Exception $e) {
            $this->debugLog('Event handling error in '.$class.":\n".$e->getMessage()."\n".$e->getTraceAsString());
        }

        wa()->popActivePlugin();

        return $result;
    }

    ############
    # SET DATA #
    ############

    /**
     * The method loads information from the file cache and sets it to the memory cache.
     * If nothing is found in the file cache, then it collects information from plugins and applications.
     *
     * @var CONST WA_EVENT_CLEAR_CACHE This is a constant that will always reset the memory cache.
     *
     * @return void
     * @throws waException
     */
    protected function setStaticData()
    {
        // Don't do anything if already loaded to memory
        if (is_array(self::$handlers) && is_array(self::$plugins)) {
            return;
        }

        self::reset();

        // Get from cache unless disabled for development
        if (!defined('WA_EVENT_CLEAR_CACHE')) {
            $cache_export = self::getCacheExport();
            $cache = $cache_export->get();

            self::$plugins = ifset($cache, 'plugins', null);
            self::$handlers = ifset($cache, 'handlers', null);
        }

        // Collect app and plugin handlers if not found in cache
        if (!is_array(self::$handlers) || !is_array(self::$plugins)) {
            $this->setHandlers();
            $this->setPlugins();

            // Write to cache unless disabled
            if (isset($cache_export)) {
                $cache_export->set(array(
                    'plugins'  => self::$plugins,
                    'handlers' => self::$handlers
                ));
            }
        }
    }

    /**
     * Get handlers from apps and plugins and set to cache memory
     *
     * @return void
     * @throws waException
     */
    protected function setHandlers()
    {
        self::$handlers = array();

        //Get app handlers
        try {
            $apps = wa()->getApps(true);
        } catch (Exception $e) {
            $this->debugLog($e->getMessage());
            $apps = array();
        }


        foreach ($apps as $event_app_id => $app_info) {
            $this->parseAppsHandlersFiles($event_app_id);
            $this->parseAppsWildCard($event_app_id);
        }

        //Get Plugin handlers
        $this->parsePluginsHandlers();
    }

    /**
     * Set in memory cache data about plugins
     *
     * @return void
     * @throws waException
     */
    protected function setPlugins()
    {
        self::$plugins = array();

        try {
            $apps = wa()->getApps();
        } catch (Exception $e) {
            $this->debugLog($e->getMessage());
            $apps = array();
        }

        foreach ($apps as $app_id => $app) {
            $plugins = wa($app_id)->getConfig()->getPlugins();

            if ($plugins) {
                foreach ($plugins as $plugin_id => $plugin) {
                    if (!empty($plugin['handlers'])) {
                        self::$plugins[$app_id][$plugin_id] = $plugin;
                    }
                }
            }
        }
    }

    /**
     * If you need to use a custom method in testing
     *
     * @param array $handler
     * @return null
     * @throws waException
     */
    public static function addCustomHandler($handler)
    {
        $instance = new self('set_static_data', null);

        $event_app_id = ifset($handler, 'event_app_id', '*');
        $event = ifset($handler, 'event', false);
        $object = ifset($handler, 'object', false);
        $methods = ifset($handler, 'method', false);

        $regex = $instance->getRegex($event);

        if (!$event || !$object || !is_object($object) || !$methods) {
            $keys = array(
                'event'  => !!$event,
                'object' => !!$object,
                'method' => !!$methods,
            );
            $keys = implode(', ', $keys);
            $instance->debugLog("Custom handler have invalid key : `{$keys}`");
            return null;
        }

        //if run before first event
        $instance->setStaticData();

        if ($instance->isMask($event)) {
            $event = '*';
        }

        self::$handlers[$event_app_id][$event][] = array(
            'object' => $object,
            'regex'  => $regex,
            'method' => $methods,
        );

        return null;
    }
    ##############
    # PARSE DATA #
    ##############

    /**
     * Get the handler from the application and parse its name into its components
     *
     * @param string $app_id
     * @return void
     * @throws waException
     */
    protected function parseAppsHandlersFiles($app_id)
    {
        $files = $this->getAppsHandlersFiles($app_id);

        foreach ($files as $file) {
            if (substr($file, -12) == '.handler.php') {

                //Remove .handler.php and get app_id and full event_name
                $handler_info = explode('.', substr($file, 0, -12), 2);
                if (count($handler_info) < 2) {
                    $this->debugLog('Incorrect handler file name - '.$file);
                    continue;
                }

                $class_name = $event = $handler_info[1];
                $regex = $this->getRegex($event);
                $event_app_id = $handler_info[0];

                if (strpos($class_name, '.') !== false) {
                    $class_name = strtok($class_name, '.').ucfirst(strtok(''));
                }
                $class_name = $app_id.ucfirst($event_app_id).ucfirst($class_name)."Handler";

                self::$handlers[$event_app_id][$event][] = array(
                    'app_id' => $app_id,
                    'regex'  => $regex,
                    'file'   => $file,
                    'class'  => $class_name,
                    'method' => array('execute') //default value in handler file
                );
            }
        }
    }

    /**
     * Get the wildcard file for applications. We retrieve information about handlers from it.
     *
     * @param string $parse_app_id
     * @return void
     * @throws waException
     */
    protected function parseAppsWildCard($parse_app_id)
    {
        $wildcard = $this->getAppsWildCard($parse_app_id);

        if ($wildcard && is_array($wildcard)) {

            foreach ($wildcard as $wc_info) {
                $event = ifset($wc_info, 'event', '');
                $class = ifset($wc_info, 'class', '');
                $method = ifset($wc_info, 'method', '');

                //Validate keys
                if (!$event || !$method || !$class) {
                    $keys = array(
                        'event'  => !!$event,
                        'class'  => !!$class,
                        'method' => !!$method,
                    );
                    $keys = implode(', ', $keys);
                    $this->debugLog("For the wildcard from the application {$parse_app_id} wrong key(s) `{$keys}`");
                    continue;
                }

                $event_app_id = ifset($wc_info, 'event_app_id', $parse_app_id);
                $app_id = ifset($wc_info, 'app_id', $parse_app_id);
                $regex = $this->getRegex($event);

                if ($this->isMask($event)) {
                    $event = '*';
                }

                self::$handlers[$event_app_id][$event][] = array(
                    'app_id' => $app_id,
                    'regex'  => $regex,
                    'file'   => ifset($wc_info, 'file', ''),
                    'class'  => $class,
                    'method' => (array)$method,
                );
            }
        }
    }

    /**
     * Extracting handlers from plugin settings
     *
     * @return void
     * @throws waException
     */
    protected function parsePluginsHandlers()
    {
        $apps_plugins = $this->getPlugins();

        foreach ($apps_plugins as $app_id => $plugins) {
            foreach ($plugins as $plugin_id => $plugin) {
                $handlers = $plugin['handlers'];

                //A handler can be an array, a string, or an array with arrays.
                //In the latter case, there should be formatted data.
                foreach ($handlers as $event => $handler) {
                    if ($event === "*" && is_array($handler)) {
                        $this->parsePluginWildCardHandlers($handler, $plugin);
                    } else {
                        $class_name = $app_id.ucfirst($plugin_id).'Plugin';

                        $regex = $this->getRegex($event);
                        if ($this->isMask($event)) {
                            $event = '*';
                        }
                        $handler_data = array(
                            'app_id'    => $app_id,
                            'plugin_id' => $plugin_id,
                            'regex'     => $regex,
                            'class'     => $class_name,
                        );

                        if ($handler) {
                            $handler_data['method'] = (array)$handler;
                            self::$handlers[$app_id][$event][] = $handler_data;
                        }
                    }
                }
            }
        }
    }

    /**
     * Extracting wildcard handlers from plugin settings
     *
     * @param array $handlers
     * @param array $plugin
     */
    protected function parsePluginWildCardHandlers($handlers, $plugin)
    {
        foreach ($handlers as $wc_info) {
            $event = ifset($wc_info, 'event', null);
            $event_app_id = ifset($wc_info, 'event_app_id', $plugin['app_id']);
            $method = ifset($wc_info, 'method', '');
            $class = ifset($wc_info, 'class', $plugin['app_id'].ucfirst($plugin['id']).'Plugin');

            if (!$event || !$method) {
                $key = $event ? 'event' : 'method';
                $this->debugLog("For the {$plugin['id']} plugin from the application {$plugin['app_id']} wrong key `{$key}`");
                continue;
            }

            $regex = $this->getRegex($event);
            if ($this->isMask($event)) {
                $event = '*';
            }

            self::$handlers[$event_app_id][$event][] = array(
                'app_id'    => $plugin['app_id'],
                'plugin_id' => $plugin['id'],
                'regex'     => $regex,
                'class'     => $class,
                'method'    => (array)$method,
            );
        }
    }

    ###########
    # HELPERS #
    ###########

    # Get info from another class. Need to write tests

    /**
     * Get files name from wa-apps/app_id/lib/handlers
     *
     * @param string $app_id
     * @return array
     * @throws waException
     */
    protected function getAppsHandlersFiles($app_id)
    {
        $handlers_path = wa()->getAppPath('lib/handlers', $app_id);
        return waFiles::listdir($handlers_path);
    }

    /**
     * Include file from wa-apps/app_id/lib/handlers
     *
     * @param string $app_id
     * @param string $file_name
     * @return bool|mixed
     * @throws waException
     */
    protected function includeAppsHandlerFile($app_id, $file_name)
    {
        $apps_path = wa()->getConfig()->getPath('apps');
        $DS = DIRECTORY_SEPARATOR;
        $file_path = $apps_path.$DS.$app_id.$DS.'lib'.$DS.'handlers'.$DS.$file_name;
        $result = false;

        if ($file_name && file_exists($file_path) && is_file($file_path)) {
            $result = include_once($file_path);
        }

        return $result;
    }

    /**
     * Load locale for plugin
     * @param $plugin_id
     * @param string $app_id
     * @return void
     * @throws waException
     */
    protected function includePluginLocale($plugin_id, $app_id)
    {
        $locale_path = wa()->getAppPath('plugins/'.$plugin_id.'/locale', $app_id);

        if (is_dir($locale_path)) {
            waLocale::load(wa()->getLocale(), $locale_path, wa()->getActiveLocaleDomain(), false);
        }
    }

    /**
     * Include file from wa-apps/app_id/lib/handlers/wildcard.php
     *
     * @param string $app_id
     * @return array|bool
     * @throws waException
     */
    protected function getAppsWildCard($app_id)
    {
        $apps_path = wa()->getConfig()->getPath('apps');
        $wildcard_path = $apps_path.'/'.$app_id.'/lib/handlers/wildcard.php';
        $wildcard = array();

        if (file_exists($wildcard_path)) {
            $wildcard = include_once($wildcard_path);
        }

        return $wildcard;
    }

    # Get info

    /**
     * Return plugins from memory cache
     *
     * @return array
     * @throws waException
     */
    protected function getPlugins()
    {
        if (self::$plugins === null) {
            $this->setPlugins();
        }

        return self::$plugins;
    }

    /**
     * @param string $app_id
     * @param string $plugin_id
     * @return mixed|null
     * @throws waException
     */
    protected function getPluginInfo($app_id, $plugin_id)
    {
        $all_plugins = $this->getPlugins();
        $plugin_info = ifset($all_plugins, $app_id, $plugin_id, array());

        return $plugin_info;
    }

    /**
     * Get regular expression from event name
     *
     * Regular expressions in the name of the event do not change. But they must start with '/' or '~'
     * You can pass the mask. EVENT_NAME.*
     * In this case, the EVENT_NAME will be escaped. Any other event name will also be escaped.
     *
     * @param $event
     * @return string
     */
    protected function getRegex($event)
    {
        if (substr($event, -2, 2) === '.*') {
            //Escape last dot and other previous regex symbol. After add a regular expression .*
            $regex = '/'.preg_quote(substr($event, 0, -1)).'.*/';
        } elseif ($event[0] !== '/' && $event[0] !== '~') {
            $regex = '/'.preg_quote($event).'/';
        } else {
            $regex = $event;
        }

        return $regex;
    }

    # Validation block

    /**
     * Check the mask or regular expression name of the event
     *
     * @param $event
     * @return bool
     */
    protected function isMask($event)
    {
        if ($event && (substr($event, -2, 2) === '.*' || $event[0] === '/' || $event[0] === '~')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check whether the requested event passes the regular expression
     *
     * @param $regex
     * @return bool
     */
    protected function isValidEventName($regex)
    {
        try {
            $result = preg_match($regex, $this->name);
        } catch (Exception $e) {
            $this->debugLog('Invalid regular expression format - '.$regex);
            $result = false;
        }

        return (bool)$result;
    }

    # Other

    /**
     * Log if debug is on
     *
     * @param string $text
     */
    protected function debugLog($text)
    {
        if (waSystemConfig::isDebug()) {
            waLog::log($text);
        }
    }

    /**
     * If launched by backend user and the key 'event_log_execution' is added to the cookie then log the runtime of each handler
     */
    protected function logExecutionTime()
    {
        $is_on_log = waRequest::cookie('event_log_execution', false);

        if ($is_on_log && $this->execution_time && wa()->getUser()->get('is_user')) {
            waLog::log(
                "===Start log block=== \nRecorded: ".wa()->getUser()->getName()."\n".
                var_export($this->execution_time, true)."\n===End log block===", 'webasyst/waEventExecutionTime.log');
        }
    }

    /**
     * @param $handler
     * @param $start_execution
     * @throws waException
     */
    protected function addExecutionTime($handler, $start_execution)
    {
        if (waRequest::cookie('event_log_execution') && wa()->getUser()->get('is_user')) {
            $handler['execution_time'] = round(microtime(true) - $start_execution, 4);
            $this->execution_time[$this->name][] = $handler;
        }
    }

    /**
     * @return waVarExportCache
     */
    protected static function getCacheExport()
    {
        return new waVarExportCache('handlers', -1, 'system/waEvent/');
    }

    /**
     * Clear file cache
     */
    public static function clearCache()
    {
        $cache = self::getCacheExport();
        $cache->delete();
    }

    /**
     * Reset memory cache
     */
    public static function reset()
    {
        self::$handlers = null;
        self::$plugins = null;
    }
}
