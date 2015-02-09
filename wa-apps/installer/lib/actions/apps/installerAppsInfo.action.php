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
        if (!waRequest::get('_')) {
            $this->setLayout(new installerBackendLayout());
        }
        $this->view->assign('action', 'update');
        $this->view->assign('error', false);

        try {
            $slug = waRequest::get('slug');
            $options = array(
                'vendor'       => waRequest::get('vendor', '', waRequest::TYPE_STRING_TRIM),
                'edition'      => waRequest::get('edition', '', waRequest::TYPE_STRING_TRIM),
                'action'       => true,
                'requirements' => true,
            );
            $this->view->assign('app', $app = installerHelper::getInstaller()->getItemInfo($slug, $options));
            $this->view->assign('title', sprintf(_w('Application "%s"'), $app['name']));

        } catch (Exception $ex) {
            $this->view->assign('error', $ex->getMessage());
        }

        $this->view->assign('identity_hash', installerHelper::getHash());
        $this->view->assign('promo_id', installerHelper::getPromoId());
        $this->view->assign('domain', installerHelper::getDomain());
        if (!empty($app['is_premium']) && ($app['theme'] == 'premium')) {
            $this->setTemplate(preg_replace('@(\.html)$@', 'Premium$1', $this->getTemplate()));
        }
    }
}
//EOF
