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
        if (!waRequest::get('_')) {
            $this->setLayout(new installerBackendLayout());
        }

        $options = array(
            'local'  => false, //retrieve only server info
            'filter' => (array)waRequest::get('filter'),
        );
        try {
            $messages = installerMessage::getInstance()->handle(waRequest::get('msg'));
            $this->view->assign('apps', $apps = installerHelper::getInstaller()->getApps($options));
            $vendor_name = null;
            if (!empty($options['filter']['vendor']) && empty($vendor_name)) {
                foreach ($apps as $app) {
                    if (!empty($app['vendor_name'])) {
                        $vendor_name = $app['vendor_name'];
                        break;
                    }
                }
            }
            $this->view->assign('vendor_name', $vendor_name);
        } catch (Exception $ex) {
            $messages[] = array('text' => $ex->getMessage(), 'result' => 'fail');
            $this->view->assign('messages', $messages);
        }
    }
}
//EOF
