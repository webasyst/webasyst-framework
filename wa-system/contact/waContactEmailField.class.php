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
class waContactEmailField extends waContactStringField
{
    public function init()
    {
        if (!isset($this->options['validators'])) {
            $this->options['validators'] = new waEmailValidator($this->options, array('required' => _ws('This field is required')));
            $this->options['formats']['js'] = new waContactEmailListFormatter();
            $this->options['formats']['top'] = new waContactEmailTopFormatter();
        }
    }
}