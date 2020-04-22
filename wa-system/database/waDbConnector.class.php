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
 * @subpackage database
 */
class waDbConnector
{
    private static $connections = array();
    private static $config;
    
    protected function __construct() {}

    /**
     * Returns connection to the database
     *
     * @param string $name
     * @param bool $writable
     * @throws waDbException
     * @return resource
     */
    public static function getConnection($name = 'default', $writable = true)
    {
        if (is_array($name)) {
            $settings = $name;
            $name = md5(var_export($name, true));
            if (!isset($settings['type'])) {
                $settings['type'] = function_exists('mysqli_connect') ? 'mysqli' : 'mysql';
            }
        }
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        } else {
            if (empty($settings)) {
                $settings = self::getConfig($name);
                if ($settings['type'] === 'mysql' && !extension_loaded('mysql')) {
                    $settings['type'] = 'mysqli';
                }
            }
            $class = "waDb".ucfirst(strtolower($settings['type']))."Adapter";
            if (!class_exists($class)) {
                throw new waDbException(sprintf("Database adapter %s not found", $class));
            }
            return self::$connections[$name] = new $class($settings);
        }
    }

    protected static function getConfig($name)
    {
        if (self::$config === null) {
            self::$config = waSystem::getInstance()->getConfig()->getDatabase();
        }
        if (!isset(self::$config[$name])) {
            throw new waDbException(sprintf("Unknown Database Connection %s", $name));
        }
        if (!isset(self::$config[$name]['type'])) {
            self::$config[$name]['type'] = function_exists('mysqli_connect') ? 'mysqli' : 'mysql';
        }
        return self::$config[$name];
    }

}