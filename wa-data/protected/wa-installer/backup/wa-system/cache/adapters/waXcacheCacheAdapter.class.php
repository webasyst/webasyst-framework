<?php

/**
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @copyright 2014 Serge Rodovnichenko
 * @package wa-system
 * @subpackage cache
 */

/**
 * Cache adapter class to store data with XCache
 *
 * Required keys for $options
 *  - 'prefix' - Unique prefix for variables stored in cache. This value
 *               must be unique for the server!
 *
 */
class waXcacheCacheAdapter extends waCacheAdapter
{
    protected function init()
    {
        parent::init();

        if(!extension_loaded('XCache')) {
            throw new waException("XCache module not loaded");
        }

        if(version_compare(phpversion("XCache"), "3.1.0", "<")) {
            throw new waException("XCache version 3.1.0 or newer required");
        }

        if(!isset($this->options["prefix"])) {
            throw new waException("Prefix for XCache is not set");
        }
    }

    /**
     *
     * @param string $key Key name
     * @param string $app_id Application ID
     * @param string|bool $group
     * @return string
     */
    public function key($key, $app_id, $group = null)
    {
        $key = parent::key($key, $app_id, $group);

        return $this->options["prefix"] . $key;
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {
        if(!xcache_isset($key)) {
            return TRUE;
        }

        return xcache_unset($key);
    }

    /**
     * @return boolean
     */
    public function deleteAll()
    {
        xcache_unset_by_prefix($this->options["prefix"]);
        return TRUE;
    }

    public function deleteGroup($group)
    {
        xcache_unset_by_prefix($group);
        return TRUE;
    }

    /**
     * Retrieves from cache
     *
     * @param string $key
     * @return mixed Cached value or NULL
     */
    public function get($key)
    {
        $v = xcache_get($key);
        if($v) {
            return unserialize($v);
        }

        return NULL;
    }

    /**
     * Stores value in the cache
     *
     * @param string $key
     * @param mixed $value Value to store in the cache
     * @param int $expiration
     * @param string|bool $group
     * @return bool
     */
    public function set($key, $value, $expiration = null, $group = null)
    {
        return xcache_set($key, serialize($value), ($expiration ? $expiration: 0));
    }

}
