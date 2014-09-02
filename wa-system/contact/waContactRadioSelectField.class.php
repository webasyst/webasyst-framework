<?php

class waContactRadioSelectField extends waContactSelectField
{
    public function getHTML($params = array(), $attrs = '')
    {
        $value = isset($params['value']) ? $params['value'] : '';
        
        $disabled = '';
        if (wa()->getEnv() === 'frontend' && isset($params['my_profile']) && $params['my_profile'] == '1') {
            $disabled = 'disabled="disabled"';
        }
        
        $html = '';
        foreach ($this->getOptions() as $k => $v) {
            $html .= '<label><input type="radio"'.($k == $value ? ' checked="checked"' : '').' '.$disabled.' '.$attrs.' name="'.$this->getHTMLName($params).'" value="'.$k.'"> '.htmlspecialchars($v).'</label>';
        }
        return '<p>'.$html.'</p>';
    }
}