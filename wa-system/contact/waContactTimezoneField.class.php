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
class waContactTimezoneField extends waContactSelectField
{
    protected $timezones = null;
    
    function getOptions($id = null) {
        if (!$this->timezones) {
            $this->timezones = waSystem::getInstance()->getDateTime()->getTimezones();
        }

        if ($id) {
            if (!isset($this->timezones[$id])) {
                throw new waException('Unknown timezone: '.$id);
            }
            return $this->timezones[$id];
        }
        return $this->timezones;
    }
}

// EOF