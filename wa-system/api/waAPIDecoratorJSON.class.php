<?php
/**
 * API decorator, transforms the response data in JSON string.
 * 
 * @package    Webasyst
 * @category   wa-system/API
 * @author     Webasyst
 * @copyright  (c) 2011-2014 Webasyst
 * @license    LGPL
 */
class waAPIDecoratorJSON implements waAPIDecoratorAdapter
{
    /**
     * Returns the JSON representation of a response data.
     * 
     * @param   mixed  $response
     * @return  string
     * @link    http://php.net/json.constants
     */
    public function decorate($response)
    {
        if (is_array($response)) {
            $response = $this->parseArray($response);
        }
        if (waSystemConfig::isDebug() && defined('JSON_PRETTY_PRINT')) {
            return json_encode($response, JSON_PRETTY_PRINT);
        } else {
            return json_encode($response);
        }
    }

    /**
     * Проходим все массивы в ответе и если находим массив
     * вида array('_element' => array(111,222)) заменяем его на
     * array(111,222), т.к. array('_element' => ...) это
     * обертка для структурирования XML.
     * 
     * @param  array|object  $array
     * @return array
     * 
     * @todo  Check description and algorithm
     */
    protected function parseArray($array)
    {
        foreach ($array as $key => $value) {
            if ($key === '_element'){
                unset($array[$key]);
            }
            if (is_array($value)) {
               $array[$key] = $this->parseArray($value);
            }
        }
        return $array;
    }
}
