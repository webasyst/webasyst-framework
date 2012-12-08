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

class installerAppsAction extends waViewAction
{
    public function execute()
    {
        $extended = false;
        $this->view->assign('action', 'update');
        $this->view->assign('error', false);

        $messages = installerMessage::getInstance()->handle(waRequest::get('msg'));

        $update_counter = 0;
        $this->view->assign('apps', installerHelper::getApps($messages, $update_counter));

        installerHelper::checkUpdates($messages);

        $this->view->assign('identity_hash', installerHelper::getHash());
        $this->view->assign('messages', $messages);
        $this->view->assign('update_counter', $update_counter);

        $this->view->assign('extended', $extended);
        $this->view->assign('title', _w('Installer'));
    }
}
//EOF