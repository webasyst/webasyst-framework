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

class installerAppsInfoAction extends waViewAction
{
    public function execute()
    {
        $extended = false;
        $this->view->assign('action', 'update');
        $update_counter = 0;
        $messages = installerMessage::getInstance()->handle(waRequest::get('msg'));


        $this->view->assign('error', false);
        $app = null;
        try {
            $app_list = installerHelper::getApps($messages, $update_counter);
            $slug = waRequest::get('slug');
            $vendor = waRequest::get('vendor');
            $edition = waRequest::get('edition');

            foreach ($app_list as $info) {
                if (($info['slug'] == $slug) && ($info['vendor'] == $vendor) && ($info['edition'] == $edition)) {
                    $app = $info;
                    break;
                }
            }
            if (!$app) {
                throw new waException(_w('Application not found'));
            }

        } catch(Exception $ex) {
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
            $this->redirect(array('module'=>'apps', 'msg'=>$msg));
        }


        $this->view->assign('identity_hash', installerHelper::getHash());
        $this->view->assign('messages', $messages);
        $this->view->assign('update_counter', $update_counter);

        $this->view->assign('app', $app);
        $this->view->assign('title', sprintf(_w('Application "%s"'), $app['name']));
    }
}
//EOF