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
 *
 * Interface waiPluginApp for payment and shipping app side classes
 */

interface waiPluginApp
{
    /**
     *
     * @param $plugin_id string
     * @param $key string
     * @return array
     */
    public function getSettings($plugin_id, $key);

    /**
     *
     * @param $plugin_id string
     * @param $key string
     * @param $name
     * @param $value
     * @return array
     */
    public function setSettings($plugin_id, $key, $name, $value);

    /**
     * @return string
     */
    public function getAppId();

    /**
     * @param $method
     * @return mixed
     */
    public function execCallbackHandler($method);
}
