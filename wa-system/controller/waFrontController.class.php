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
 * @subpackage controller
 */
class waFrontController
{
    /**
     * @var waSystem
     */
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

    public function dispatch()
    {
        $env = $this->system->getEnv();
        if ($env == 'frontend') {
            $module = 'frontend';
        } else {
            $module = waRequest::get($this->options['module'], $this->system->getEnv());
        }
        $module = waRequest::param('module', $module);
        $action = waRequest::param('action', waRequest::get($this->options['action']));
        $plugin = waRequest::param('plugin', $env == 'backend' ? waRequest::get('plugin', '') : '');
        // event init
        if (!waRequest::request('background_process')) {
            if (method_exists($this->system->getConfig(), 'onInit')) {
                $this->system->getConfig()->onInit();
            }
        }
        if ($this->system->getEnv() == 'backend') {
            if ($widget = waRequest::get('widget')) {
                $this->executeWidget($widget, $action);
            } else {
                $this->execute($plugin, $module, $action);
            }
        } else {
            $this->execute($plugin, $module, $action);
        }
    }

    public function executeWidget($widget, $action = null)
    {
        $widget = $this->system->getWidget(waRequest::get('id'));
        $app_id = $widget->getInfo('app_id');
        if ($app_id != 'webasyst') {
            waSystem::pushActivePlugin($widget->getInfo('widget'), $app_id.'_widget');
        }
        $widget->loadLocale($app_id == 'webasyst');
        return $widget->run($action);
    }

    /** Execute appropriate controller and return it's result.
      * Throw 404 exception if no controller found. */
    public function execute($plugin = null, $module = null, $action = null, $default = false)
    {
        if (!$this->system->getConfig()->checkRights($module, $action)) {
            throw new waRightsException(_ws("Access denied."));
        }
        // current app prefix
        $prefix = $this->system->getConfig()->getPrefix();

        // Load plugin locale and set plugin as active
        if ($plugin) {
            $plugin_path = $this->system->getAppPath('plugins/'.$plugin, $this->system->getApp());
            if (!file_exists($plugin_path.'/lib/config/plugin.php')) {
                $plugin = null;
            } else {
                $plugin_info = include($plugin_path.'/lib/config/plugin.php');
                // check rights
                if (isset($plugin_info['rights']) && $plugin_info['rights']) {
                    if (!$this->system->getUser()->getRights($this->system->getConfig()->getApplication(), 'plugin.'.$plugin)) {
                        throw new waRightsException(_ws("Access denied"), 403);
                    }
                }
                waSystem::pushActivePlugin($plugin, $prefix);
                if (is_dir($plugin_path.'/locale')) {
                    waLocale::load($this->system->getLocale(), $plugin_path.'/locale', waSystem::getActiveLocaleDomain(), false);
                }
            }
        }

        // custom login and signup
        if (wa()->getEnv() == 'frontend') {
            if (!$plugin && !$action && ($module == 'login')) {
                $login_action = $this->system->getConfig()->getFactory('login_action');
                if ($login_action) {
                    $controller = $this->system->getDefaultController();
                    $controller->setAction($login_action);
                    $r = $controller->run();
                    return $r;
                }
            } elseif (!$plugin && !$action && ($module == 'signup')) {
                $signup_action = $this->system->getConfig()->getFactory('signup_action');
                if ($signup_action) {
                    $controller = $this->system->getDefaultController();
                    $controller->setAction($signup_action);
                    $r = $controller->run();
                    return $r;
                }
            }
        }

        //
        // Check possible ways to handle the request one by one
        //

        // list of failed class names (for debugging)
        $class_names = array();

        // Single Controller (recomended)
        $class_name = $prefix.($plugin ? ucfirst($plugin).'Plugin' : '').ucfirst($module).($action ? ucfirst($action) : '').'Controller';
        if (class_exists($class_name, true)) {
            /**
             * @var $controller waController
             */
            $controller = new $class_name();
            $r = $controller->run();
            if ($plugin) {
                waSystem::popActivePlugin();
            }
            return $r;
        }
        $class_names[] = $class_name;

        // Single Action
        $class_name = $prefix.($plugin ? ucfirst($plugin).'Plugin' : '').ucfirst($module).($action ? ucfirst($action) : '').'Action';
        if (class_exists($class_name)) {
            // get default view controller
            /**
             * @var $controller waDefaultViewController
             */
            $controller = $this->system->getDefaultController();
            $controller->setAction($class_name);
            $r = $controller->run();
            if ($plugin) {
                waSystem::popActivePlugin();
            }
            return $r;
        }
        $class_names[] = $class_name;

        // Controller Multi Actions, Zend/Symfony style
        $class_name = $prefix.($plugin ? ucfirst($plugin).'Plugin' : '').ucfirst($module).'Actions';
        if (class_exists($class_name, true)) {
            $controller = new $class_name();
            $r = $controller->run($action);
            if ($plugin) {
                waSystem::popActivePlugin();
            }
            return $r;
        }
        $class_names[] = $class_name;

        // Plugin is no longer active
        if ($plugin) {
            waSystem::popActivePlugin();
        }

        // Last chance: default action for this module
        if ($action && $default) {
            return $this->execute($plugin, $module);
        }

        // Too bad. 404.
        throw new waException(sprintf('Empty module and/or action after parsing the URL "%s" (%s/%s).<br />Not found classes: %s', $this->system->getConfig()->getCurrentUrl(), $module, $action,implode(', ',$class_names)), 404);
    }
}

// EOF