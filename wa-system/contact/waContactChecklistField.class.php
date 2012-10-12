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
class waContactChecklistField extends waContactSelectField
{
    /**
     * Return 'Checklist' type, unless redefined in subclasses
     * @return string
     */
    public function getType()
    {
        return 'Checklist';
    }

    public function getInfo()
    {
        $info = parent::getInfo();
        $info['hrefPrefix'] = isset($this->options['hrefPrefix']) ? $this->options['hrefPrefix'] : '';
        return $info;
    }
}

// EOF