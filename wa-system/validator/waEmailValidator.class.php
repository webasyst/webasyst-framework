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
class waEmailValidator extends waRegexValidator
{
    // crazy regex X_____x
    // does not work for UTF-8 domains, e.g. .рф
    const REGEX_EMAIL = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:[a-z0-9](?:[\\-a-z0-9]*[a-z0-9])*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';

    // It's dangerous (and useless) to make more restrictions here as the email specifications
    // allow for too much flexibiility. It is more likely that the user will (accidentally or deliberately) enter an incorrect
    // email address than in an invalid one, so actually validating is a great deal of work for very little benefit,
    // with possible costs if you do it incorrectly.
    // Elegance is in simplicity :)
    // const REGEX_EMAIL = '~^[^\s@]+@[^\s@]+\.[^\s@\.]{2,}$~u';

    protected function init()
    {
        $this->setMessage('not_match', _ws('Invalid Email'));
        $this->setPattern(self::REGEX_EMAIL);
    }

    public function isValid($value)
    {
        $value = is_scalar($value) ? (string)$value : '';

        if ($this->hasMalwareSubstrings($value)) {
            $this->setError($this->getMessage('not_match', array('value' => $value)));
            return false;
        }

        if (strlen($value) > 0 && !preg_match("/^[a-z0-9~@+:\[\]\.-]+$/i", $value)) {
            $idna = new waIdna();
            $value = $idna->encode($value);
        }
        return parent::isValid($value);
    }

    private function hasMalwareSubstrings($value)
    {
        $value = mb_strtolower($value);
        return strpos($value, '<script') !== false;
    }
}
