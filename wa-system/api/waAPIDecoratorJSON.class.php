<?php

class waAPIDecoratorJSON extends waAPIDecorator
{

    public function decorate($response)
    {
        if (is_array($response)) {
            $response = $this->parseArray($response);
        }
        if (waSystemConfig::isDebug() && (version_compare(PHP_VERSION, '5.4.0') >= 0)) {
            return json_encode($response, JSON_PRETTY_PRINT);
        } else {
            return json_encode($response);
        }
    }

    /**
     * Проходим все массивы в ответе и если находим массив
     * вида array('uid' => array(111,222)) заменяем его на
     * array(111,222), т.к. array('uid' => ...) это
     * обертка для структурирования хмл.
     * @param array $arr
     * @return array
     */
    protected function parseArray($arr)
    {
        foreach ($arr as $key => $val) {
            if ($key === '_element') {
                unset($arr[$key]);
            }
            if (is_array($val)) {
                $arr[$key] = $this->parseArray($val);
            }
        }
        return $arr;
    }
}
