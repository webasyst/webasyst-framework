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
class waLoginValidator extends waRegexValidator
{
    const REGEX_LOGIN = '/^[a-z0-9\.@_-]+$/i';

    protected function init()
    {
        $this->setOption(array(
            'required' => true,
            'min_length' => 4,
            'max_length' => 32
        ));

        parent::init();

        $this->setPattern(self::REGEX_LOGIN);

        $this->setMessage('not_match', 'Invalid login');
    }
}