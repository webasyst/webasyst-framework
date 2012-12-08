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

class installerSettingsClearCacheController extends waJsonController
{
    public function execute()
    {
        try {
            $path_cache = waConfig::get('wa_path_cache');
            waFiles::delete($path_cache, true);
            waFiles::protect($path_cache);
            $app_path = waConfig::get('wa_path_apps');

            $apps = new waInstallerApps();

			$app_list = $apps->getApplicationsList(true);
            foreach ($app_list as $app) {

                if (isset($app['enabled']) && $app['enabled']) {
                    $path_cache = $app_path.'/'.$app['slug'].'/js/compiled';
                    waFiles::delete($path_cache, true);
                }
            }
            $this->response['message'] = _w('Cache cleared');
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }
}
//EOF