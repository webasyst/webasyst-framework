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

class installerAppsRemoveAction extends waViewAction
{
    function execute()
    {
        $module = 'apps';
        $url = parse_url(waRequest::server('HTTP_REFERER'), PHP_URL_QUERY);
        if (preg_match('/(^|&)module=(update|apps|plugins)($|&)/', $url, $matches)) {
            $module = $matches[2];
        }

        $app_ids = waRequest::get('app_id');
        try {
            if (!$app_ids || !is_array($app_ids)) {
                throw new waException(_w('Application not found'));
            }
            $vendors = array();
            foreach ($app_ids as $app_id=>&$info) {
                if (!is_array($info)) {
                    $info = array('vendor'=>$info);
                }
                $vendors[] = $info['vendor'];
                unset($info);
            }
            $vendors = array_unique($vendors);

            $apps = new waInstallerApps();
            $app_list = $apps->getApplicationsList(true, $vendors);
            $deleted_apps = array();

            if (installerHelper::isDeveloper()) {
                throw new waException(_w('Unable to delete application (developer version is on)'));
            }
            foreach ($app_list as $info) {
                $app_id = $info['slug'];
                if (isset($app_ids[$app_id]) && ($app_ids[$app_id]['vendor'] == $info['vendor'])) {
                    if (isset($info['system']) && $info['system']) {
                        throw new waException(sprintf(_w('Can not delete system application "%s"'), $info['name']));
                    }
                    $apps->updateRoutingConfig($app_id, false);
                    $apps->updateAppConfig($app_id, null);
                    //remove db tables and etc

                    $paths = array();

                    $app_instance = waSystem::getInstance($app_id);
                    $plugins = $app_instance->getConfig()->getPlugins();
                    foreach ($plugins as $plugin_id => $plugin) {
                        if ($plugin && ($plugin_instance = $app_instance->getPlugin($plugin_id))) {
                            $plugin_instance->uninstall();
                        }
                        $apps->updateAppPluginsConfig($app_id, $plugin_id, null);

                        //wa-apps/$app_id/plugins/$slug
                        $paths[] = wa()->getAppPath("plugins/{$plugin_id}", $app_id);
                        foreach ($paths as $path) {
                            waFiles::delete($path, true);
                        }
                        $paths = array();
                    }

                    $app_instance->getConfig()->uninstall();
                    //XXX called at uninstall
                    //$paths[] = wa()->getAppCachePath(null, $app_id);//wa-cache/apps/$app_id/
                    $paths[] = wa()->getTempPath(null, $app_id);//wa-cache/temp/$app_id/
                    $paths[] = wa()->getAppCachePath(null, $app_id);//wa-cache/apps/$app_id/

                    $paths[] = wa()->getDataPath(null, true, $app_id);//wa-data/public/$app_id/
                    $paths[] = wa()->getDataPath(null, false, $app_id);//wa-data/protected/$app_id/
                    //XXX uncomplete code
                    //$paths[] = wa()->   null, false, $app_id);//wa-log/$app_id/
                    //XXX uncomplete code
                    //$paths[] = wa()->getAppPath(null, $app_id);//wa-config/$app_id/

                    $paths[] = wa()->getAppPath(null, $app_id);//wa-apps/$app_id/

                    foreach ($paths as $path) {
                        waFiles::delete($path, true);
                    }
                    $deleted_apps[] = $info['name'];
                }
            }
            if (!$deleted_apps) {
                throw new waException(_w('Application not found'));
            }
            $message = _w('Application %s has been deleted', 'Applications %s have been deleted', min(2, count($deleted_apps)), false);
            $message = sprintf($message, implode(', ', $deleted_apps));
            $msg = installerMessage::getInstance()->raiseMessage($message);
            $this->redirect(array('module'=>$module, 'msg'=>$msg));
        } catch(Exception $ex) {
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
            $this->redirect(array('module'=>$module, 'msg'=>$msg));
        }

    }
}
//EOF