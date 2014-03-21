<?php
/**
 * API response decorator.
 * 
 * @package    Webasyst
 * @category   wa-system/API
 * @author     Webasyst
 * @copyright  (c) 2011-2014 Webasyst
 * @license    LGPL
 */
class waAPIDecorator
{
    /**
     * @var array Decorators
     */
    protected static $instances = array();
    
    /**
     * @var  array  Supported formats (decorator's adapters)
     */
    protected static $formats = array('JSON', 'XML');

    /**
     * Get decorator's adapter for selected format.
     * 
     * @param   string  $format  Data type of response, see $known_formats
     * @return  object
     * @throws  waAPIException
     */
    public static function getInstance($format = 'JSON')
    {
        $class = 'waAPIDecorator'.strtoupper($format);

        if (!isset(self::$instances[$class])) {
            if (!in_array($class, self::$formats) || !class_exists($class)) {
                throw new waAPIException(2, 'Unknown API decorator');
            }
            self::$instances[$class] = new $class;
        }

        return self::$instances[$class];
    }
    
    /**
     * Get known formats of the response.
     * 
     * @return  array
     */
    public static function getFormats()
    {
        return self::$formats;
    }
}
