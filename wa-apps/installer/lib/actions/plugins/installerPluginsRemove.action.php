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

class installerPluginsRemoveAction extends installerExtrasRemoveAction
{

    protected function preExecute()
    {
        $this->extras_type = 'plugins';
    }

    protected function removeExtras($app_id, $extras_id)
    {
        try {
            $paths = array();
            if (strpos($app_id, 'wa-plugins/') !== 0) {
                try {
                    $plugin_instance = waSystem::getInstance($app_id)->getPlugin($extras_id);
                    if (!$plugin_instance) {
                        return false;
                    }
                    $plugin_instance->uninstall();
                } catch (Exception $ex) {
                    waLog::log($ex->getMessage(), 'installer.log');
                    $info = array(
                        'app_id' => $app_id,
                        'id'     => $extras_id,
                    );
                    $plugin_instance = new installerDummyPlugin($info);
                    $plugin_instance->uninstall(true);
                }

                $this->installer->updateAppPluginsConfig($app_id, $extras_id, null);
                //wa-apps/$app_id/plugins/$slug
                $paths[] = wa()->getAppPath("{$this->extras_type}/{$extras_id}", $app_id);
                $paths[] = wa()->getTempPath(null, $app_id); //wa-cache/temp/$app_id/
                $paths[] = wa()->getAppCachePath(null, $app_id); //wa-cache/apps/$app_id/
            } else {
                $type = str_replace('wa-plugins/', '', $app_id);
                $paths[] = wa()->getConfig()->getPath('plugins').'/'.$type.'/'.$extras_id; //wa-plugins/$type/$extras_id
                $paths[] = wa()->getAppCachePath(null, $type.'_'.$extras_id); //wa-cache/apps/$app_id/
            }

            $retry_paths = array();
            foreach ($paths as $path) {
                try {
                    waFiles::delete($path, true);
                } catch (waException $ex) {
                    waLog::log($ex->getMessage(), 'installer/remove.'.ifset($type).$this->extras_type.'.log');
                    $retry_paths[] = $path;
                }
            }


            if ($retry_paths) {
                sleep(5);
                foreach ($retry_paths as $path) {
                    try {
                        waFiles::delete($path, true);
                    } catch (waException $ex) {
                        waLog::log($ex->getMessage(), 'installer/remove.'.ifset($type).$this->extras_type.'.log');
                    }
                }
            }

            return true;
        } catch (Exception $ex) {
            //TODO check config
            if (strpos($app_id, 'wa-plugins/') !== 0) {
                if (file_exists(reset($paths).'/lib/plugin.php')) {
                    $this->installer->updateAppPluginsConfig($app_id, $extras_id, true);
                }
            }
            throw $ex;
        }

    }
}
