<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage contact
 */
class waContactCheckboxField extends waContactField
{
    public function set(waContact $contact, $value, $params = array(), $add = false)
    {
        if ($this->isMulti()) {
            throw new waException('Multi-checkboxes are not implemented.');
        }

        if (!$value) {
            return '';
        }

        // Only update timestamp if checkbox was not set before the save
        $old = $contact->get($this->id);
        return $old ? $old : time();
    }

    public function format($data, $format = null)
    {
        $result = parent::format($data, $format);
        if (in_array('list', explode(',', $format))) {
            return $result ? _ws('Yes') : _ws('No');
        }
        return $result;
    }

    public function getHTML($params = array(), $attrs = '')
    {
        $value = isset($params['value']) ? $params['value'] : '';
        return '<input type="hidden" name="'.$this->getHTMLName($params).'" value=""><input type="checkbox"'.($value ? ' checked="checked"' : '').' name="'.$this->getHTMLName($params).'" value="'.ifempty($value, '1').'" '.$attrs.'>';
    }

}

