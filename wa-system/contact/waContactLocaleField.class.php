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

    public function init()
    {
        if (!isset($this->options['formats']['value'])) {
            $this->options['formats']['value'] = new waContactLocaleFormatter();
        }
        if (!isset($this->options['formats']['html'])) {
            $this->options['formats']['html'] = $this->options['formats']['value'];
        }
    }

    function getOptions($id = null) {
        if (!$this->locales) {
            $this->locales = waLocale::getAll('name_region', !empty($this->options['all']) ? false: true);
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

class waContactLocaleFormatter extends waContactFieldFormatter
{
    public function format($data)
    {
        $info = waLocale::getInfo($data);
        return ifset($info['name'], $data);
    }
}

// EOF