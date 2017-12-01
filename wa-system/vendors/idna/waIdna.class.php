<?php

require_once(dirname(__FILE__).'/idna_convert.class.php');

class waIdna extends idna_convert
{
    private static $instance;

    public static function dec($str)
    {
        if(!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance->decode($str);
    }

    public static function enc($str)
    {
        if(!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance->encode($str);
    }
}
