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
 * @subpackage cache
 */

abstract class waFileCache implements waiCache
{
    /**
     * Key
     *
     * @var string
     */
    protected $key;

    /**
     * Expire time in sec
     *
     * @var int
     */
    protected $ttl;
    protected $app_id;
    protected $value = null;

    public function __construct($key, $ttl = -1, $app_id = null)
    {
        $this->key = trim($key, '/');
        $this->ttl = $ttl;
        $this->app_id = $app_id;
    }

    protected function getFilePath()
    {
        return waSystem::getInstance()->getCachePath('cache/'.$this->key.'.php', $this->app_id);
    }

    public function get()
    {
        if ($this->value !== null) {
            return $this->value;
        }
        $t = func_num_args() ? func_get_args(0) : null;
        $this->value = $this->readFromFile($this->getFilePath(), $t);
        return $this->value;
    }


    public function set($value)
    {
        $this->value = null;
        return $this->writeToFile($this->getFilePath(), $value);
    }

    public function delete()
    {
        $this->value = null;
        $file = $this->getFilePath();
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    public function isCached()
    {
        return $this->get() === null ? false : true;
    }

    abstract protected function writeToFile($file, $v);
    abstract protected function readFromFile($file);
}