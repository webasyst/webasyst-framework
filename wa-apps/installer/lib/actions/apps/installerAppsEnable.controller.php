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

class installerAppsEnableController extends waJsonController
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

            foreach ((array)$app_ids as $app_id) {
                if (isset($app_list[$app_id])) {
                    installerHelper::getInstaller()->installWebAsystApp($app_id);

                    $params = array(
                        'type' => 'apps',
                        'id'   => $app_id,
                        'ip'   => waRequest::getIp(),
                    );

                    $this->logAction('item_enable', $params);
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
