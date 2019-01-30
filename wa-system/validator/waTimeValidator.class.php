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
class waTimeValidator extends waValidator
{
    const TIME_PATTERN = '~^([01]?[0-9]|2[0-3])($|\:([0-5][0-9]))($|\:([0-5][0-9]$))~';

    protected function init()
    {
        parent::init();
        $this->setMessage('incorrect_time', _ws("Incorrect time"));
    }

    /**
     * @param string|int $value
     * @return bool
     */
    public function isValid($value)
    {
        parent::isValid($value);
        $valid = preg_match(self::TIME_PATTERN, $value) ? true : false;
        if (!$valid) {
            $this->setError($this->getMessage('incorrect_time'), $value);
        }
        return $this->getErrors() ? false : true;
    }

    public function parse($value)
    {
        preg_match(self::TIME_PATTERN, $value, $matches);
        return array(
            'hours'   => ifset($matches, 1, null),
            'minutes' => ifset($matches, 3, null),
            'seconds' => ifset($matches, 5, null),
        );
    }
}