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
class waNumberValidator extends waRegexValidator
{
    const REGEX_NUMBER = '~^-?[0-9]+([\.,][0-9]+)?$~';

    protected function init()
    {
        $this->setMessage('not_match', _ws('Incorrect numerical value'));
        $this->setPattern(self::REGEX_NUMBER);
    }
}

// EOF