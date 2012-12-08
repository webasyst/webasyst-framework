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

class installerUpdateAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('action', 'update');
        $counter = array('total'=>0,'applicable'=>0);
        $app_list = array();

        $this->view->assign('error', false);
        $messages = installerMessage::getInstance()->handle(waRequest::get('msg'));
        try {
            $app_list = installerHelper::getApps($messages, $counter);
            foreach ($app_list as &$info) {
                if ($info['slug'] == 'installer') {
                    $info['name'] = _w('Webasyst Framework');
                    break;
                }
            }
            unset($info);
        } catch(Exception $ex) {
            $messages[] = array('text'=>$ex->getMessage(), 'result'=>'fail');
        }

        installerHelper::checkUpdates($messages);

        $this->view->assign('messages', $messages);
        //$this->view->assign('install_counter', $model->get($this->getApp(), 'install_counter'));
        $this->view->assign('update_counter', $counter['total']);
        $this->view->assign('update_counter_applicable', $counter['applicable']);
        $this->view->assign('apps', $app_list);
        $this->view->assign('identity_hash', installerHelper::getHash());
        $this->view->assign('title', _w('Updates'));
    }
}
//EOF