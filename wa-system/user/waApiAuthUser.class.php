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
 * @subpackage user
 */
class waApiAuthUser extends waAuthUser
{
    public function __construct($id = null, $options = array())
    {
        waUser::__construct($id);
        foreach ($options as $name => $value) {
            self::$options[$name] = $value;
        }
        $this->init();
    }

    public function init()
    {
        if (wa()->getEnv() != 'api') {
            throw new waRightsException();
        }

        waUser::init();
        $this->auth = true;
    }
}
