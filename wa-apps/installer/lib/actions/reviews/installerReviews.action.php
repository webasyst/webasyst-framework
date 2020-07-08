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

class installerReviewsAction extends waViewAction
{
    public function execute()
    {
        $this->setLayout(new installerBackendStoreLayout());

        $this->view->assign('store_review_core_url', $this->getStoreReviewCoreUrl());
        $this->view->assign('store_review_core_params', $this->getStoreReviewCoreParams());
        $this->view->assign('title', _w('Reviews'));
    }

    /**
     * @return null|string
     */
    protected function getStoreReviewCoreUrl()
    {
        $url = null;
        try {
            $wa_installer = installerHelper::getInstaller();
            $url = $wa_installer->getStoreReviewCoreUrl();
            $parsed_url = parse_url($url);
            $scheme = ($parsed_url['scheme'] === 'http') ? '//' : $parsed_url['scheme'].'://';
            $port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
            $url = $scheme.$parsed_url['host'].$port.ifset($parsed_url['path']);
        } catch (Exception $e) {
        }

        return $url;
    }

    protected function getStoreReviewCoreParams()
    {
        $params = null;
        try {
            $init_data = wa('installer')->getConfig()->getTokenData();
            $params = (array)$init_data;
            $params['locale'] = wa()->getUser()->getLocale();
        } catch (Exception $e) {
        }

        return $params;
    }
}
