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
class waContactUrlField extends waContactStringField
{
    public function init()
    {
        if (!isset($this->options['formats']['js'])) {
            $this->options['formats']['js'] = new waContactUrlJsFormatter();
        }
        if (!isset($this->options['validators'])) {
            $this->options['validators'] = new waUrlValidator($this->options);
        }
    }
    
    protected function setValue($value) {
        if (is_array($value) && isset($value['value'])) {
            $value = $value['value'];
        }
        $value = (string)$value;
        if ($value && !strpos($value, '://')) {
            $value = 'http://'.$value;
        }
        return $value;
    }
}

// EOF