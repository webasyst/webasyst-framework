<?php

class waAPIDecorator
{

    /**
     * @param string $format JSON|XML
     * @return waAPIDecorator
     * @throws waAPIException
     */
    public static function factory($format = null)
    {
        if (!$format) {
            $format = 'json';
        }
        $class = 'waAPIDecorator'.strtoupper($format);
        if (class_exists($class)) {
            return new $class();
        } else {
            throw new waAPIException(2, 'Unknown decorator');
        }
    }

    /**
     * @param array $response
     * @return string
     */
    public function decorate($response)
    {

    }
}
