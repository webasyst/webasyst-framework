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
 * @subpackage validator
 */
class waPhoneNumberValidator extends waRegexValidator
{
    // simple pattern
    // TODO: make it more sophisticated
    const REGEX_NUMBER = '/^[0-9\-\(\)\/\+\s]*$/';

    protected function init()
    {
        $this->setMessage('not_match', _ws('Incorrect phone number value'));
        $this->setPattern(self::REGEX_NUMBER);
    }

    public function isValid($value)
    {
        $value = is_scalar($value) ? (string)$value : '';
        if (strlen($value) <= 0) {
            return false;
        }
        return parent::isValid($value);
    }
}

// EOF
