<?php

/**
 * Represents a hidden field that always returns the same value.
 * Primarily used with waContactForm class.
 */
class waContactHiddenField extends waContactField
{
    public function getHtmlOne($params = array(), $attrs = '')
    {
        $value = $this->getParameter('value');
        if ($value === null) {
            $value = ifset($params['value'], '');
        }
        $ext = null;
        $multi_suffix = '';
        if (is_array($value)) {
            $ext = $value['ext'];
            $value = $value['value'];
        }

        $name_input = $name = $this->getHTMLName($params);
        if ($this->isMulti()) {
            $name_input .= '[value]';
        }

        $result = '<input type="hidden" name="'.htmlspecialchars($name_input).'" value="'.htmlspecialchars($value).'" '.$attrs.'>';
        if ($ext) {
            $result .= '<input type="hidden" name="'.htmlspecialchars($name.'[ext]').'" value="'.htmlspecialchars($value).'">';
        }

        return $result;
    }

    public function isHidden()
    {
        return true;
    }
}
