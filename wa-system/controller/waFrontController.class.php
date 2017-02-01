<?php
/**
 * Part of application that handles request dispatching inside the app.
 * Determines which controller class to evoke depending on GET/routing parameters.
 *
 * See:
 * - $this->dispatch()
 * - waSystem->dispatch()
 * - waAPIController->dispatch()   // login only
 */
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
 * @subpackage controller
 */
class waFrontController
{
    /** @var waSystem */
    protected $system;
    protected $options = array(
        'module' => 'module',
        'action' => 'action'
    );

    public function __construct($options = array())
    {
        $this->system = waSystem::getInstance();
        foreach ($options as $k => $v) {
            $this->options[$k] = $v;
        }
    }

    /**
     * Run appropriate controller from current app (or its plugin),
     * depending on environment and parameters from GET or routing.
     */
    public function dispatch()
    {
        // event init
        if (!waRequest::request('background_process')) {
            if (method_exists($this->system->getConfig(), 'onInit')) {
                $this->system->getConfig()->onInit();
            }
        }

        list($plugin, $module, $action, $is_widget) = $this->getDispatchParams();

        if ($this->system->getEnv() == 'backend' && $is_widget) {
            $this->executeWidget($action);
        } else {
            $this->execute($plugin, $module, $action);
        }
    }

    /**
     * Read from GET/routing and validate parameters for dispatch()
     */
    protected function getDispatchParams()
    {
        $env = $this->system->getEnv();
        $action = waRequest::get($this->options['action'], null, 'string');
        if ($env == 'frontend') {
            $module = 'frontend';
            $is_widget = $plugin = null;
        } else {
            // Dispatch params are allowed via GET in backend
            $module = waRequest::get($this->options['module'], 'backend', 'string');
            $is_widget = waRequest::get('widget', null, 'string');
            $plugin = waRequest::get('plugin', null, 'string');
        }

        // Routing parameters override those from GET
        $plugin = waRequest::param('plugin', $plugin, 'string');
        $module = waRequest::param('module', $module, 'string');
        $action = waRequest::param('action', $action, 'string');

        // Make sure parameters are sane
        foreach (array($plugin, $module, $action) as $i => $v) {
            if ($v && !$this->isDispatchParamValid($v)) {
                throw new waException('Bad parameters ('.$i.')', 400);
            }
        }

        return array($plugin, $module, $action, $is_widget);
    }

    /** Validator for $this->getDispatchParams() */
    protected function isDispatchParamValid($v)
    {
        return preg_match('~^[a-z_][a-z0-9_]*$~i', $v);
    }

    protected function executeWidget($action = null)
    {
        $widget = $this->system->getWidget(waRequest::get('id'));
        if (!$widget->isAllowed()) {
            throw new waException(_ws('You don’t have permissions to view this widget'), 403);
        }
        $app_id = $widget->getInfo('app_id');
        if ($app_id != 'webasyst') {
            waSystem::pushActivePlugin($widget->getInfo('widget'), $app_id.'_widget');
        }
        $widget->loadLocale($app_id == 'webasyst');
        return $widget->run($action);
    }

    /**
     * Run appropriate controller of current app or it's plugin.
     * Throw 404 exception if no controller found.
     */
    public function execute($plugin = null, $module = null, $action = null, $default = false)
    {
        if (!$plugin && !$this->system->getConfig()->checkRights($module, $action)) {
            throw new waRightsException(_ws("Access denied."));
        }

        // custom login and signup
        if (!$plugin && !$action && wa()->getEnv() == 'frontend') {
            if ($module == 'login' || $module == 'signup') {
                $action = $this->system->getConfig()->getFactory($module.'_action');
                if ($action) {
                    $controller = $this->system->getDefaultController();
                    $controller->setAction($action);
                    return $this->runController($controller);
                }
            }
        }

        // Load plugin locale and set plugin as active
        if ($plugin) {
            $plugin_path = $this->system->getAppPath('plugins/'.$plugin.'/lib/config/plugin.php', $this->system->getApp());
            if (!file_exists($plugin_path)) {
                throw new waException(_ws('Plugin not found'), 404);
            }

            $plugin_info = include($plugin_path);

            // check rights
            if (isset($plugin_info['rights']) && $plugin_info['rights']) {
                if (!$this->system->getUser()->getRights($this->system->getConfig()->getApplication(), 'plugin.'.$plugin)) {
                    throw new waRightsException(_ws("Access denied"), 403);
                }
            }

            // Load plugin, including updates check, locale, etc.
            $this->system->getPlugin($plugin, true);
        }

        try {
            // emulating `finally` in php<5.5
            $exception = $result = null;

            list($controller, $params) = $this->getController($plugin, $module, $action, $default);
            $result = $this->runController($controller, $params);
        } catch (Exception $e) {
            $exception = $e;
        }

        // Plugin is no longer active
        if ($plugin) {
            waSystem::popActivePlugin();
        }

        if ($exception) {
            throw $exception;
        }
        return $result;
    }

    /** Helper for $this->execute() */
    protected function getController($plugin, $module, $action, $try_default = false, $class_names = array())
    {
        // app prefix for class names
        $prefix = $this->system->getConfig()->getPrefix();

        //
        // Check possible ways to handle the request one by one
        //

        // Single Controller (recomended)
        $class_name = $prefix.($plugin ? ucfirst($plugin).'Plugin' : '').ucfirst($module).($action ? ucfirst($action) : '').'Controller';
        if (class_exists($class_name)) {
            return array(new $class_name(), null);
        }
        $class_names[] = $class_name;

        // Single Action
        $class_name = $prefix.($plugin ? ucfirst($plugin).'Plugin' : '').ucfirst($module).($action ? ucfirst($action) : '').'Action';
        if (class_exists($class_name)) {
            /** @var $controller waDefaultViewController */
            $controller = $this->system->getDefaultController();
            $controller->setAction($class_name);
            return array($controller, null);
        }
        $class_names[] = $class_name;

        // Controller Multi Actions, Zend/Symfony style
        $class_name = $prefix.($plugin ? ucfirst($plugin).'Plugin' : '').ucfirst($module).'Actions';
        if (class_exists($class_name)) {
            return array(new $class_name(), $action);
        }
        $class_names[] = $class_name;

        // Last chance: default action for this module
        if ($action && $try_default) {
            return $this->getController($plugin, $module, null, false, $class_names);
        }

        // Too bad. 404.
        throw new waException(sprintf('Empty module and/or action after parsing the URL "%s" (%s/%s).<br />Not found classes: %s', $this->system->getConfig()->getCurrentUrl(), $module, $action,implode(', ',$class_names)), 404);
    }

    /**
     * Helper for $this->execute()
     * Makes sense to override this in subclasses to add error handling, event dispatching, etc.
     */
    protected function runController($controller, $params = null)
    {
        return $controller->run($params);
    }
}

// EOF