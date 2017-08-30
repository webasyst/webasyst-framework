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
class waContactDateLocalFormatter extends waContactFieldFormatter
{
    public function format($data)
    {
        if (is_array($data)) {
            $value =& $data['value'];
        } else {
            $value =& $data;
        }
        if ($value) {
            $format = isset($this->options['format']) ? $this->options['format'] : 'date';
            $value = waDateTime::format($format, $value, waDateTime::getDefaultTimeZone());
        }
        unset($value); // being paranoid

        return $data;
    }
}

// EOF