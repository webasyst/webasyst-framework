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

class installerSettingsRemoveAction extends waViewAction
{
    public function execute()
    {
        try {
            $message = array();
            $settings = waRequest::get('setting');
            if ($settings) {
                $model = new waAppSettingsModel();
                $changed = false;
                foreach ((array)$settings as $setting) {
                    if (in_array($setting, array('auth_form_background'))) {
                        $images_path = wa()->getDataPath(null, true, 'webasyst');
                        if ($images = waFiles::listdir($images_path)) {
                            foreach ($images as $image) {
                                if (is_file($images_path.'/'.$image) && !preg_match('@\.(jpe?g|png|gif|bmp)$@', $image)) {


                                    waFiles::delete($images_path."/".$image);
                                }
                            }
                            $message[] = _w('Image deleted');
                        }
                    } else {
                        $changed = true;
                    }
                    $model->del('webasyst', $setting);
                }
                if ($changed) {
                    $message[] = _w('Settings saved');
                }
            }
            $params = array(
                'module' => 'settings',
                'msg'    => installerMessage::getInstance()->raiseMessage(implode(', ', $message)),
            );
            $this->redirect($params);
        } catch (waException $ex) {
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
            $params = array(
                'module' => 'settings',
                'msg'    => $msg
            );
            $this->redirect($params);
        }
    }
}
