<?php

class waContactPasswordField extends waContactField
{
    public function getHTML($params = array(), $attrs = '')
    {
        $value = isset($params['value']) ? $params['value'] : '';
        return '<input '.$attrs.' type="password" name="'.$this->getHTMLName($params).'" value="'.htmlspecialchars($value).'">';
    }
}