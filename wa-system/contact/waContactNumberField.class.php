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
class waContactNumberField extends waContactStringField
{
    public function init()
    {
        if (!isset($this->options['validators'])) {
            $this->options['validators'] = new waNumberValidator($this->options);
        }
    }
}

// EOF