<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2023 Webasyst LLC
 * @package wa-system
 * @subpackage validator
 */
class waDateIsoValidator extends waValidator
{
    /**
     * @param array|string $value
     * @return bool
     */
    public function isValid($value)
    {
        if (is_array($value)) {
            $_value  = (!empty($value['year']) && !is_bool($value['year']) ? $value['year'] : '');
            $_value .= (!empty($value['month']) && !is_bool($value['month']) ? '-'.$value['month'] : '');
            $_value .= (!empty($value['day']) && !is_bool($value['day']) ? '-'.$value['day'] : '');
            $value = $_value;
        } else {
            $value = trim($value);
        }
        if (!parent::isValid($value)) {
            return false;
        }
        $date_parse = date_parse_from_format('Y-m-d', $value);
        if (!empty($date_parse['warnings'])) {
            $this->setError(reset($date_parse['warnings']));
        } elseif (!empty($date_parse['errors'])) {
            $this->setError(reset($date_parse['errors']));
            $this->setError('ISO 8601 format YYYY-MM-DD');
        }

        return !$this->getErrors();
    }
}
