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

class installerThemesRemoveAction extends installerExtrasRemoveAction
{

    protected function preExecute()
    {
        $this->extras_type = 'themes';
    }

    protected function removeExtras($app_id, $extras_id)
    {

        $paths = array();
        $paths[] = wa()->getTempPath(null, $app_id); //wa-cache/temp/$app_id/
        //wa-apps/$app_id/extras/$slug
        $paths[] = wa()->getAppPath("{$this->extras_type}/{$extras_id}", $app_id);
        $paths[] = wa()->getDataPath("{$this->extras_type}/{$extras_id}", true, $app_id, false);
        $paths[] = wa()->getAppCachePath(null, $app_id); //wa-cache/apps/$app_id/

        foreach ($paths as $path) {
            waFiles::delete($path, true);
        }
        return true;
    }
}
