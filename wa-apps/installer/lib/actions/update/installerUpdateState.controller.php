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

class installerUpdateStateController extends waJsonController
{
    public function execute()
    {
        $updater = new waInstaller(waInstaller::LOG_TRACE);
        $this->response['state'] = $updater->getFullState(waRequest::get('mode', 'apps'));
        $this->response['current_state'] = $updater->getState();
        $response = $this->getResponse();
        $response->addHeader('Content-Type', 'application/json; charset=utf-8');
        $response->sendHeaders();
    }
}
