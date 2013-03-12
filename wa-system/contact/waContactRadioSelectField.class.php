<?php

class waContactRadioSelectField extends waContactSelectField
{
    public function getHTML($params = array(), $attrs = '')
    {
        $value = isset($params['value']) ? $params['value'] : '';
        $html = '';
        foreach ($this->getOptions() as $k => $v) {
            $html .= '<label><input type="radio"'.($k == $value ? ' checked="checked"' : '').' name="'.$this->getHTMLName($params).'" value="'.$k.'"> '.htmlspecialchars($v).'</label>';
        }
        return '<p>'.$html.'</p>';
    }
}