<?php

class installerPluginsInfoAction extends waViewAction
{
    public function execute()
    {
        $filter = array();


        $filter['enabled'] = true;
        $filter['extras'] = 'plugins';

        $options = array(
            'installed' => true,
        );

        $search = array();

        $search['slug'] = preg_replace('@^(wa-plugins/)?([^/]+)/.+$@', '$1$2', waRequest::get('slug'));
        if (strpos($search['slug'], 'wa-plugins/') === 0) {
            $options['system'] = true;
        }
        $applications = installerHelper::getInstaller()->getApps($options, $filter);
        $plugin_search = array();
        $plugin_search['id'] = preg_replace('@^.+/@', '', waRequest::get('slug'));
        if (array_filter($search, 'strlen') && ($app = installerHelper::search($applications, $search))) {
            $plugin_search['slug'] = $search['slug']."/plugins/".$plugin_search['id'];
            $options = array(
                'action'       => true,
                'requirements' => true,
                //XXX   'vendor'       => waRequest::get('plugin_vendor', 'webasyst'),
            );
            $plugin = installerHelper::getInstaller()->getItemInfo($plugin_search['slug'], $options);
            if (!$plugin) {
                $options['local'] = true;
                $plugin = installerHelper::getInstaller()->getItemInfo($plugin_search['slug'], $options);

            }
            if ($plugin) {
                $plugin['app'] = preg_replace('@^(wa-plugins/)?([^/]+)/.+$@', '$1$2', $plugin['slug']);
                $plugin['slug'] = preg_replace('@^wa-plugins/([^/]+)/plugins/(.+)$@', 'wa-plugins/$1/$2', $plugin['slug']);
            }
            $this->view->assign('identity_hash', installerHelper::getHash());
            $this->view->assign('promo_id', installerHelper::getPromoId());
            $this->view->assign('domain', installerHelper::getDomain());
            $this->view->assign('plugin', $plugin);
            $this->view->assign('query', waRequest::get('query', '', waRequest::TYPE_STRING_TRIM).'/');
        } else {
            throw new waException(_w('Plugin not found'), 404);
        }
    }
}
