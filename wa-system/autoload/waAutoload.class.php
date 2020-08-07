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
 * @subpackage autoload
 */
class waAutoload
{
    protected static $static_cache = array();
    protected static $registered = false;
    protected static $instance = null;
    protected $classes = array();
    protected $base_path = null;
    protected $system_classes;

    protected function __construct()
    {
        $this->base_path = realpath(dirname(__FILE__).'/../..');

        // load system classes
        if (!isset(self::$static_cache['system_classes'])) {
            $rules_path = dirname(__FILE__) . '/system_classes.php';
            if (!file_exists($rules_path)) {
                throw new Exception(sprintf('Unable to load system classes'));
            }
            self::$static_cache['system_classes'] = include($rules_path);
        }
        $this->system_classes = self::$static_cache['system_classes'];
    }

    /**
     * @return waAutoload
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function register()
    {
        if (self::$registered) {
            return;
        }

        ini_set('unserialize_callback_func', 'spl_autoload_call');
        if (false === spl_autoload_register(array(self::getInstance(), 'autoload'))) {
            throw new Exception(sprintf('Unable to register %s::autoload as an autoloading method.', get_class(self::getInstance())));
        }

        self::$registered = true;
    }

    /**
     * Unregister waAutoload from spl autoloader.
     *
     * @return void
     */
    public static function unregister()
    {
        spl_autoload_unregister(array(self::getInstance(), 'autoload'));
        self::$registered = false;
    }

    public function autoload($class)
    {
        if ($path = $this->get($class)) {
            if (!file_exists($path)) {

                // Clear autoload cache of loaded apps
                if (!isset($this->system_classes[$class])
                    && class_exists('waSystem', false)
                    && !waSystemConfig::isDebug()
                ) {
                    foreach (array_keys(wa()->getApps()) as $app_id) {
                        if (waSystem::isLoaded($app_id)) {
                            waAppConfig::clearAutoloadCache($app_id);
                        }
                    }
                }

                $msg = sprintf('Not found file [%1$s] for class [%2$s]', $path, $class);
                if ($class == 'waException') {
                    throw new Exception($msg, 500);
                } else {
                    throw new waException($msg, 500);
                }
            }

            require_once $path;

            if (!class_exists($class, false)
                && !interface_exists($class, false)
                && (function_exists('trait_exists') && !trait_exists($class, false))
            ) {
                $msg = sprintf('Not found class [%2$s] in file [%1$s]', $path, $class);
                if ($class == 'waException') {
                    throw new Exception($msg, 500);
                } else {
                    throw new waException($msg, 500);
                }
            }
        }
    }

    public function get($class)
    {
        if (isset($this->system_classes[$class])) {
            return $this->base_path.'/wa-system/'.$this->system_classes[$class];
        } elseif (substr($class, 0, 2) == 'wa') {
            if (strpos($class, '.') !== false) {
                return null;
            }

            if (substr($class, 0, 4) === 'waDb') {
                $file = $this->base_path.'/wa-system/database/'.$class.'.class.php';
                if (is_readable($file)) {
                    return $file;
                }
            } elseif (substr($class, -5) == 'Model') {
                $path = $this->base_path.'/wa-system/webasyst/lib/models/'.substr($class, 0, -5).'.model.php';
                if (is_readable($path)) {
                    return $path;
                }
            } elseif (substr($class, 0, 9) === 'waContact') {
                if (substr($class, 0, 16) === 'waContactAddress') {
                    // formatters live in the same file as waContactAddressField
                    $result = $this->base_path.'/wa-system/contact/waContactAddressField.class.php';
                } else {
                    $result = $this->base_path.'/wa-system/contact/'.$class.'.class.php';
                }
                if (is_readable($result)) {
                    return $result;
                }
            }

            $dir = preg_replace("/^wai?([A-Z][a-z]+).*?$/", "$1", $class);
            $path = $this->base_path.'/wa-system/'.strtolower($dir).'/'.$class.'.'.(substr($class, 0, 3) === 'wai' ? 'interface' : 'class').'.php';
            if (file_exists($path)) {
                return $path;
            } else {
                $dir = preg_replace("/^wa.*?([A-Z][a-z]+)$/", "$1", $class);
                $path = $this->base_path.'/wa-system/'.strtolower($dir).'/'.$class.'.class.php';
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        if (isset($this->classes[$class])) {
            return $this->base_path.'/'.$this->classes[$class];
        }
        return null;
    }

    public function add($class, $path = null)
    {
        if (is_array($class)) {
            foreach ($class as $class_name => $path) {
                if (!isset($this->classes[$class_name])) {
                    $this->classes[$class_name] = $path;
                }
            }
        } else {
            $this->classes[$class] = $path;
        }
    }

    /**
     * Get all classes that are available for autoloading.
     * @return array classname => file path relative to wa-root, no leading slash
     */
    public function getClasses()
    {
        $result = $this->classes;
        foreach ($this->system_classes as $class => $path) {
            $result[$class] = 'wa-system/'.$path;
        }
        return $result;
    }



    public function getClassByFilename($filename, $namespace)
    {
        $file_parts = explode('.', $filename);
        if (count($file_parts) <= 2) {
            return false;
        }
        array_pop($file_parts);
        $class = null;
        switch (end($file_parts)) {
            case 'handler':
                $class = $namespace;
                for ($i = 0; $i < count($file_parts); $i++) {
                    $class .= ucfirst($file_parts[$i]);
                }
                break;
            case 'class':
                $class = $file_parts[0];
                break;
            case 'trait':
            case 'interface':
            default:
                $class = $file_parts[0];
                for ($i = 1; $i < count($file_parts); $i++) {
                    $class .= ucfirst($file_parts[$i]);
                }
                break;
        }
        return $class;
    }
}
