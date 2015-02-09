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
 * @subpackage plugin
 */

interface waiPluginSettings
{
    public function set($key, $name, $value);
    public function get($key, $name = null, $default = null);
    public function del($key, $name);
}
