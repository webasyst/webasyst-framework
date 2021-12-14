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
 * @package installer
 */

class installerThemesViewAction extends installerItemsAction
{
    protected $module = 'theme';

    protected function buildStorePath($params)
    {
        return 'themes/?'.http_build_query($params);
    }
}
