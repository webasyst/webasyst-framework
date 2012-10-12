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
class waContactLocaleField extends waContactSelectField
{
    protected $locales = null;
    
    function getOptions($id = null) {
        if (!$this->locales) {
            $this->locales = waLocale::getAll('name_region');
        }

        if ($id) {
            if (!isset($this->locales[$id])) {
                throw new Exception('Unknown locale: '.$id);
            }
            return $this->locales[$id];
        }
        
        return $this->locales;
    }
}

// EOF