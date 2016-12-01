<?php 
/**
 * Interface for a simple cache. One object = one cached value.
 */
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

interface waiCache
{
    public function __construct($key, $ttl = -1, $app_id = null);
    public function get();
    public function set($value);
    public function delete();
    public function isCached();
}