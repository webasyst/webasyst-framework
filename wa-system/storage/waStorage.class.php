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
 * @subpackage storage
 */
abstract class waStorage
{
    protected $options = array();

    public function __construct($options = array())
    {
        $this->init($options);
    }

    public function init($options = array())
    {
        foreach ($options as $name => $value) {
            $this->options[$name] = $value;
        }
    }

    public function getOptions()
    {
        return $this->options;
    }

    abstract public function read($key);

    abstract public function regenerate($destroy = false);

    abstract public function remove($key);

    abstract public function write($key, $data);

    public function get($key)
    {
        return $this->read($key);
    }

    public function del($key)
    {
        $this->remove($key);
    }

    public function set($key, $data)
    {
        $this->write($key, $data);
    }
}
