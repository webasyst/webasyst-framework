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

class installerThemesViewAction extends waViewAction
{
    public function execute()
    {

        $filter = array();

        $filter['enabled'] = true;
        $filter['extras'] = 'themes';

        $options = array(
            'installed' => true,
        );
        $applications = installerHelper::getInstaller()->getApps($options, $filter);


        $search = array();

        $search['slug'] = array($app_id = waRequest::get('slug'));
        $search['slug'] = array_unique($search['slug']);

        $search['vendor'] = waRequest::get('vendor', 'webasyst');
        $installer = installerHelper::getInstaller();


        $options = array(
            'local'     => true,
            'apps'      => true,
            'filter'    => (array)waRequest::get('filter'),
            'inherited' => array_keys($applications),
        );
        $list = $installer->getExtras($search['slug'], 'themes', $options);

        /**
         * @var $themes waTheme[]
         */
        $themes = array();
        $inherited = array();
        foreach ($list as $id => $item_list) {
            if (!empty($item_list['themes'])) {
                foreach ($item_list['themes'] as $theme_id => $theme) {
                    if (($id == $app_id) || !empty($theme['inherited'][$app_id])) {
                        $themes[$theme_id] = new waTheme($theme_id, $app_id, true, true);

                        $themes[$theme_id]['name'] = empty($theme['name']) ? $theme_id : $theme['name'];
                        $themes[$theme_id]['description'] = empty($theme['description']) ? '' : $theme['description'];

                        if (!empty($theme['price'])) {
                            $themes[$theme_id]['price'] = $theme['price'];
                        }
                        if (!empty($theme['compare_price'])) {
                            $themes[$theme_id]['compare_price'] = $theme['compare_price'];
                        }
                        if (isset($theme['commercial'])) {
                            $themes[$theme_id]['commercial'] = $theme['commercial'];
                        }
                        if (!empty($theme['icon'])) {
                            $themes[$theme_id]['cover'] = $theme['icon'];
                        }
                        if (!isset($inherited[$theme_id])) {
                            $inherited[$theme_id] = array();
                        }

                        if (!empty($theme['inherited'])) {
                            $inherited[$theme_id] = array_merge($inherited[$theme_id], $theme['inherited']);
                        }

                        if (!isset($inherited[$theme_id][$id])) {
                            $inherited[$theme_id][$id] = array(
                                'slug' => $id.'/themes/'.$theme_id,
                            );
                        }
                    } else {
                        if (!isset($inherited[$theme_id])) {
                            $inherited[$theme_id] = array();
                        }

                        if (!empty($theme['inherited'])) {
                            $inherited[$theme_id] = array_merge($inherited[$theme_id], $theme['inherited']);
                        }
                    }
                }
            }
        }
        foreach ($themes as $theme_id => &$theme) {
            $theme['inherited'] = $inherited[$theme_id];
            unset($theme);
        }

        $app = $installer->getItemInfo($app_id);

        $this->view->assign('themes', $themes);
        $this->view->assign('app_id', $app_id);
        $this->view->assign('app', $app);
        $this->view->assign('slug', $search['slug']);
        $this->view->assign('vendor', $search['vendor']);
        $return_url = waRequest::get('return_url', waRequest::server('HTTP_REFERER'));
        if ($return_hash = waRequest::get('return_hash')) {
            if ($return_hash = preg_replace('@^#@', '', $return_hash)) {
                $return_url .= '#'.$return_hash;
            }
        }
        $this->view->assign('return_url', $return_url);
    }
}
//EOF
