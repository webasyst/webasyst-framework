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
 * @subpackage widget
 */
class waWidgets 
{
    public static function load($param1, $param2 = null)
    {
        $result = array();
        if ($param2 !== null) {
            foreach ($param2 as $widget) {
                $result[] = self::getInfo($param1, $widget);
            }
        }
        return $result;
    }

    public static function getInfo($app, $id)
    {
        $path = "widgets/".$id."/widget.php";
        $file = waSystem::getInstance()->getConfig()->getAppsPath($app, $path);
        if (file_exists($file)) {
            $data = include($file);
            if (isset($data['src'])) {
                $data['src'] = waSystem::getInstance()->getRootUrl().$data['src'];
            }
            $data['app'] = $app;
            $data['id'] = $id;
            return $data;
        }
        return null;
    }
}