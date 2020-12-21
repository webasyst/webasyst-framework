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
 * @package wa-installer
 */
if (!defined('WA_ROOT')) {
    $root_path = preg_replace('@([/\\\\]+)@', '/', dirname(__FILE__).'/');
    $root_path = preg_replace('@(/)wa-installer/lib/?$@', '$1', $root_path);
    define('WA_ROOT', $root_path);
}
function wa_installer_autoload($name)
{
    static $depended_classes;

    if ($depended_classes === null) {
        foreach ([
            'wainstaller'             => 'wa-installer/lib/classes/wainstaller.class.php',
            'wainstallercontroller'   => 'wa-installer/lib/classes/wainstallercontroller.class.php',
            'wainstallerapps'         => 'wa-installer/lib/classes/wainstallerapps.class.php',
            'wainstallerrequirements' => 'wa-installer/lib/classes/wainstallerrequirements.class.php',
            'wainstallerlocale'       => 'wa-installer/lib/classes/wainstallerlocale.class.php',
            'wainstallerfile'         => 'wa-installer/lib/classes/wainstallerfile.class.php',
            'waInstallerDownloadException' => 'wa-installer/lib/classes/waInstallerDownloadException.class.php',
        ] as $class => $path) {
            $depended_classes[strtolower($class)] = $path;
        }
    }

    $name = strtolower($name);
    $result = false;
    if (isset($depended_classes[$name])) {
        require_once WA_ROOT.$depended_classes[$name];
        $result = true;
    }
    return $result;
}

ini_set('unserialize_callback_func', 'spl_autoload_call');
ini_set('include_path', './');
if (false === spl_autoload_register('wa_installer_autoload')) {
    throw new Exception('Unable to register wa_installer_autoload as an autoloading method.');
}
if (!function_exists('_w')) {
    function _w($string)
    {
        static $t;
        if (!$t) {
            $t = new waInstallerLocale();
        }
        return $t->_($string);
    }
}
if (!function_exists('_wd')) {
    function _wd($domain, $string)
    {
        static $t;
        if (!$t) {
            $t = new waInstallerLocale();
        }
        return $t->_($string);
    }
}
