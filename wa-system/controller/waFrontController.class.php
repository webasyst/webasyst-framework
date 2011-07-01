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

    public function dispatch($route = null)
    {
            if ($this->system->getEnv() == 'frontend') {
                $this->system->getRouting(true)->dispatch($route);
            }
            $module = waRequest::get($this->options['module'], $this->system->getConfig()->getEnviroment());
            $module = waRequest::param('module', $module);
            $action = waRequest::param('action', waRequest::get($this->options['action']));
            $plugin = waRequest::param('plugin', waRequest::get('plugin', ''));
            if ($widget = waRequest::param('widget')) {
                $this->executeWidget($widget, $action);
            } elseif ($this->system->getEnv() == 'backend') {
                $url = explode("/", $this->system->getConfig()->getRequestUrl(true));
                if (isset($url[2]) && isset($url[3]) && $url[2] == 'widgets') {
                    $this->executeWidget($url[3], $action);
                } else {
                    $this->execute($plugin, $module, $action);
                }
            } else {
                $this->execute($plugin, $module, $action);
            }
    }

    public function executeWidget($widget, $action = null)
    {
        $prefix = $this->system->getConfig()->getPrefix('prefix');
        $class_name = $prefix.ucfirst($widget)."Widget";
        if (class_exists($class_name, true)) {
            $controller = new $class_name();
            return $controller->run($action);
        } else {
            throw new waException(sprintf('Widget "%s" not found by URL (%s).', $widget, $this->system->getConfig()->getRequestUrl(false)), 404);
        }

    }

    /** Execute appropriate controller and return it's result.
      * Throw 404 exception if no controller found. */
    public function execute($plugin = null, $module = null, $action = null, $default = false)
    {
        // current app prefix
        $prefix = $this->system->getConfig()->getPrefix('prefix');

        // Load plugin locale and set plugin as active
        if ($plugin) {
            waSystem::pushActivePlugin($plugin, $prefix);
            $localePath = wa()->getAppPath('plugins/'.$plugin.'/locale', wa()->getApp());
            if (is_dir($localePath)) {
                waLocale::load(wa()->getLocale(), $localePath, waSystem::getActiveLocaleDomain(), false);
            }
        }

        //
        // Check possible ways to handle the request one by one
        //

        // list of failed class names (for debugging)
        $class_names = array();

        // Single Controller (recomended)
        $class_name = $prefix.($plugin ? ucfirst($plugin).'plugin' : '').ucfirst($module).($action ? ucfirst($action) : '').'Controller';
        if (class_exists($class_name, true)) {
            $controller = new $class_name();
            $r = $controller->run();
            if ($plugin) {
                waSystem::popActivePlugin();
            }
            return $r;
        }
        $class_names[] = $class_name;

        // Controller Multi Actions, Zend/Symfony style
        $class_name = $prefix.($plugin ? ucfirst($plugin).'plugin' : '').ucfirst($module).'Actions';
        if (class_exists($class_name, true)) {
            $controller = new $class_name();
            $r = $controller->run($action);
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
            $controller = $this->system->getFactory('default_controller', 'waDefaultViewController');
            $controller->setAction($class_name);
            $r = $controller->run();
            if ($plugin) {
                waSystem::popActivePlugin();
            }
            return $r;
        }

        // Plugin is no longer active
        if ($plugin) {
            waSystem::popActivePlugin();
        }

        // Last chance: default action for this module
        if ($action && $default) {
            return $this->execute($plugin, $module);
        }
        $class_names[] = $class_name;

        // Too bad. 404.
        throw new waException(sprintf('Empty module and/or action after parsing the URL "%s" (%s/%s).<br />Not found classes: %s', $this->system->getConfig()->getCurrentUrl(), $module, $action,implode(', ',$class_names)), 404);
    }
}

// EOF