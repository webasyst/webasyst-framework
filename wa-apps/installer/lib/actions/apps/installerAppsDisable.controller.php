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

class installerAppsDisableController extends waJsonController
{
    public function execute()
    {
        try {
            $app_ids = waRequest::post('app_id');

            $options = array(
                'installed' => true, //list all local apps
                'status'    => false,//check app status at app.php
            );
            $app_list = installerHelper::getApps($options);

            $installer = installerHelper::getInstaller();
            foreach ((array)$app_ids as $app_id) {
                if (isset($app_list[$app_id]) && empty($app_list[$app_id]['system'])) {
                    $installer->updateAppConfig($app_id, false);
                    $paths = array();

                    $paths[] = wa()->getAppCachePath(null, $app_id); //wa-cache/apps/$app_id/
                    $paths[] = wa()->getTempPath(null, $app_id); //wa-cache/temp/$app_id/

                    foreach ($paths as $path) {
                        waFiles::delete($path, true);
                    }

                    $params = array(
                        'type' => 'apps',
                        'id'   => $app_id,
                        'ip'   => waRequest::getIp(),
                    );

                    $this->logAction('item_disable', $params);
                }
            }

            $errors = installerHelper::flushCache();
            if ($errors) {
                $this->response['message'] .= "<br>"._w('But with errors:')."<br>".implode("<br>", $errors);
            }
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }
}
