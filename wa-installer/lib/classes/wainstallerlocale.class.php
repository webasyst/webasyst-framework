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

class waInstallerLocale
{
    private static $strings = array();
    private $locale;
    function __construct($locale)
    {
        $path = dirname(__FILE__).'/../../locale/'.$locale.'.php';
        $this->locale = $locale;
        if(preg_match('/^[a-z]{2}_[A-Z]{2}$/',$locale)&&file_exists($path)){
            if(!isset(self::$strings[$locale])||!is_array(self::$strings[$locale])){
                self::$strings[$locale]= include($path);
            }
            if(!is_array(self::$strings[$locale])){
                self::$strings[$locale] = array();
            }
        }

    }

    public static function listAvailable()
    {
        $available = array();
        $path = dirname(__FILE__).'/../../locale/';
        $content = scandir($path);
        foreach($content as $item){
            if(preg_match('/^([a-z]{2}_[A-Z]{2})\.php$/',$item,$matches)){
                $available[$matches[1]] = $matches[1];
            }
        }
        return $available;
    }

    function _(){
        $args = func_get_args();
        $string = current($args);
        $string = isset(self::$strings[$this->locale][$string])? self::$strings[$this->locale][$string] : $string;
        if (count($args)) {
            $args[0] = $string;
            if ($formated = @call_user_func_array('sprintf', $args)) {
                $string = $formated;
            } else {
                $string = implode(', ',$args);
            }
        }
        return $string;
    }
}