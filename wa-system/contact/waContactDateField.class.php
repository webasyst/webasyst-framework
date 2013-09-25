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
class waContactDateField extends waContactField
{
    public function getInfo()
    {
        $info = parent::getInfo();
        $info['format'] = waDateTime::getFormatJS('date');
        return $info;
    }

    protected function init()
    {
        if (!isset($this->options['formats'])) {
            $this->options['formats'] = array();
        }
        if (!isset($this->options['formats']['js'])) {
            if (isset($this->options['formats']['locale'])) {
                $this->options['formats']['html'] = $this->options['formats']['value'] = $this->options['formats']['js'] = $this->options['formats']['locale'];
            } else {
                $this->options['formats']['html'] = $this->options['formats']['value'] = $this->options['formats']['js'] = new waContactDateLocalFormatter();
            }
        }
        if (!isset($this->options['formats']['locale'])) {
            $this->options['formats']['locale'] = $this->options['formats']['js'];
        }

        parent::init();
    }

    public function format($data, $format = null)
    {
        if ($data == '0000-00-00') {
            $data = '';
        }
        return parent::format($data, $format);
    }
}

// EOF