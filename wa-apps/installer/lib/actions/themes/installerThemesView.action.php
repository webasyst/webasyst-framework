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
        if (waRequest::get('full_width')) {
            $params['full_width'] = 1;
        }
        if (waRequest::get('hide_back')) {
            $params['hide_back'] = 1;
        }
        return 'themes/?'.http_build_query($params);
    }
}
