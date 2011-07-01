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
    private static $handlers = array();
    private static $config;
    
    protected function __construct() {}
    
    /**
     * Returns connection to database
     *
     * @param int $code
     * @return res
     */
    public static function getConnection($name = 'default')
    {
        if (isset(self::$handlers[$name])) {
            return self::$handlers[$name];
        }
        else {
        	$config = self::getConfig(); 
        	if (!isset($config[$name])) {
        		throw new waDbException("Unknown name of the DataBase Connection");
        	}
        	$type = isset($config[$name]['type']) ? $config[$name]['type'] : 'mysql';
        	$adapter = self::getAdapter($type); 
            $handler = $adapter->connect($config[$name]);
            if ($handler) {
                return self::$handlers[$name] = array(
            		'handler' => $handler,
            		'adapter' => $adapter
            	);
            } else {
            	throw new waDbException("Couldn't connect to the server");
            }
        }
    }
    
    /**
     * Returns adaptor of database by type
     * 
     * @param string $type
     * @return waDbAdapter
     */
    protected static function getAdapter($type)
    {
    	$adaptor = "waDb".ucfirst(strtolower($type))."Adapter";
    	return new $adaptor();
    }
        
    protected static function getConfig()
    {
    	if (!self::$config) {
    		self::$config = waSystem::getInstance()->getConfig()->getDatabase(); 
    	}
    	return self::$config;
    }

}