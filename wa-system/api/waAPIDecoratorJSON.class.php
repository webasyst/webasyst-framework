<?php

class waAPIDecoratorJSON extends waAPIDecorator
{

    public function decorate($response)
    {
        if (is_array($response)) {
            $response = $this->parseArray($response);
        }
        return waUtils::jsonEncode($response);
    }

    /**
     * Iterate through all arrays in response.
     * If an array like array('uid' => array(111,222)) is found,
     * replace it with array(111,222),
     * because array('uid' => ...) is a wrapper for XML structuring.
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
