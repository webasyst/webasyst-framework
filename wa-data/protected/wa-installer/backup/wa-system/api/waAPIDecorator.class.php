<?php

class waAPIDecorator
{

    /**
     * @param waAPIMethod $method
     * @param string $format JSON|XML
     * @return waAPIDecorator
     * @throws waAPIException
     */
    public static function factory($format = null)
    {
        $class = 'waAPIDecorator'.strtoupper($format);
        if (class_exists($class)){
            return new $class();
        } else {
            throw new waAPIException(2, 'Unknown decorator');
        }
    }

    /**
     * @param $response
     * @return string
     */
    public function decorate($response)
    {

    }
}
