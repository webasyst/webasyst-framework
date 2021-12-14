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

class installerWidgetsRemoveAction extends installerExtrasRemoveAction
{

    protected function preExecute()
    {
        $this->extras_type = 'widgets';
    }

    protected function removeExtras($app_id, $extras_id)
    {
        $paths = array();
        if ($app_id == 'webasyst') {
            $paths[] = wa()->getConfig()->getPath('widgets').'/'.$extras_id; //wa-widgets/$extras_id
        } else {
            $paths[] = wa()->getAppPath("{$this->extras_type}/{$extras_id}", $app_id);//wa-apps/$app_id/widgets/$extras_id
        }
        $paths[] = wa()->getTempPath(null, $app_id); //wa-cache/temp/$app_id/
        $paths[] = wa()->getAppCachePath(null, $app_id); //wa-cache/apps/$app_id/

        try {
            $model = new waWidgetModel();
            $model->deleteByWidget($app_id, $extras_id);
        } catch (Exception $ex) {
            waLog::log($ex->getMessage(), 'installer.log');
        }

        foreach ($paths as $path) {
            waFiles::delete($path, true);
        }
        return true;
    }
}
