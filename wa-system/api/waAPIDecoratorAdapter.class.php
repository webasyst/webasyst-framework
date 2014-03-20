<?php
/**
 * Interface of API decorator.
 * 
 * @package    Webasyst
 * @category   System/API
 * @author     Webasyst
 * @copyright  (c) 2011-2014 Webasyst
 * @license    LGPL
 */
interface waAPIDecoratorAdapter
{
    /**
     * Convert response data in string.
     * 
     * @param  mixed  $response
     * @return string
     */
    public function decorate($response);
}
