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

class installerSettingsAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign(array(
            'site_app_exists'     => wa()->appExists('site'),
            'system_settings_url' => wa()->getAppUrl('site').'#/system-settings/',
            'version'             => wa()->getVersion('webasyst'),
        ));
        $this->setLayout(new installerBackendStoreLayout());
        $this->getLayout()->assign('no_ajax', true);
    }
}
