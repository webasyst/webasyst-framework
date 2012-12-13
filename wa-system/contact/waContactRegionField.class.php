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
class waContactRegionField extends waContactField
{
    protected $rm = null;
    public function getInfo()
    {
        $data = parent::getInfo();
        $data['region_countries'] = array_fill_keys($this->getRegionCountries(), 1);
        return $data;
    }

    public function getRegionCountries()
    {
        if (!$this->rm) {
            $this->rm = new waRegionModel();
        }
        return $this->rm->getCountries();
    }

    public function format($data, $format = null, $full_composite=null)
    {
        if (empty($full_composite['country'])) {
            return $data;
        }
        if (!$this->rm) {
            $this->rm = new waRegionModel();
        }
        $row = $this->rm->getByField(array(
            'country_iso3' => $full_composite['country'],
            'code' => $data,
        ));
        if (!$row) {
            return $data;
        }
        return $row['name'];
    }
}

