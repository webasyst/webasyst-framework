<?php

class waContactPasswordField extends waContactField
{
    public function getHTML($value = '', $attrs = '')
    {
        return '<input '.$attrs.' type="password" name="'.$this->id.'" value="'.htmlspecialchars($value).'">';
    }
}