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

class installerBackendAction extends waViewAction
{
    private $user = null;
    private $app_id = null;
    private $allow_add = false;
    function __construct()
    {
        $this->user = $this->getUser();
        $this->app_id = waSystem::getInstance()->getApp();
        if (!$this->user->isAdmin($this->app_id) && !$this->user->getRights($this->app_id)) {
            throw new waException(null, 403);
        }
        parent::__construct();
    }

    public function execute()
    {
        $update_counter = 0;
        $model = new waAppSettingsModel();
        $license = $model->get('webasyst', 'license', false);
        $apps = new waInstallerApps($license);
        $app_list = array();
        $this->view->assign('error', false);
        try {
            $app_list = $apps->getApplicationsList(false, array(), wa()->getDataPath('images', true));
            $update_counter=waInstallerApps::getUpdateCount($app_list);
            $model->ping();
            $this->getConfig()->setCount($update_counter);
        } catch(Exception $ex) {
            //$this->view->assign('error', $ex->getMessage());
        }

        $this->redirect(array('module'=>$update_counter?'update':'apps'));
        $this->view->assign('module', false);
    }

    public function _getTemplate()
    {
        $template = parent::getTemplate();
        if (($id = waRequest::isMobile()) || true) {
            $this->view->assign('mobile_id', $id);
            $template = str_replace('templates/actions/', 'templates/actions-mobile/', $template);
        }
        return $template;
    }
}

//EOF