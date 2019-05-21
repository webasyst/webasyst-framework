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

    /**
     * If you are not sure that the recorded cache will clear itself - use this flag.
     * In this case, the Framework will definitely remove it when the $this->ttl time expires.
     * @important On $this->writeToFile and $this->delete makes database queries!
     * @var bool
     */
    protected $hard_clean = false;

    public function __construct($key, $ttl = -1, $app_id = null, $hard_clean = false)
    {
        $this->key = trim($key, '/');
        $this->ttl = $ttl;
        $this->app_id = $app_id;
        $this->hard_clean = $hard_clean;
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
        $result = $this->writeToFile($this->getFilePath(), $value);
        if ($this->hard_clean) {
            $this->getCacheModel()->add($this->getCacheName(), $this->ttl);
        }

        // Update the value so that you can continue working with the object.
        // But only if that makes sense.
        // This will eliminate the excessive reading of the cache file, if after set we need get.
        if ($result && $this->ttl !== 0) {
            $this->value = $value;
        }

        return $result;
    }

    public function delete()
    {
        $this->value = null;

        if ($this->hard_clean) {
            $this->getCacheModel()->deleteByField('name', $this->getCacheName());
        }

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

    protected function getCacheName()
    {
        $file = $this->getFilePath();
        $root_path = wa()->getConfig()->getRootPath();
        $name = str_replace($root_path, '', $file);
        return $name;
    }

    /**
     * @return waCacheModel
     */
    protected function getCacheModel()
    {
        static $model;
        if ($model === null) {
            $model = new waCacheModel();
        }
        return $model;
    }
}