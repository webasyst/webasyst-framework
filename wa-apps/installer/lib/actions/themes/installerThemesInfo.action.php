<?php
class installerThemesInfoAction extends waViewAction
{
    public function execute()
    {
        $filter = array();

        $filter['enabled'] = true;
        $filter['extras'] = 'themes';

        $app = false;
        $messages = array();
        $applications = installerHelper::getApps($messages, $update_counter = null, $filter);

        $search = array();

        $search['slug'] = waRequest::get('slug');
        $search['vendor'] = waRequest::get('vendor', 'webasyst');
        $theme_search = array();
        $theme_search['slug'] = waRequest::get('theme_slug');
        $theme_search['vendor'] = waRequest::get('theme_vendor', 'webasyst');
        if ( array_filter($search, 'strlen') && ($app = installerHelper::search($applications, $search)) ) {
            $theme = new waTheme($theme_search['slug'], $search['slug'], true);
            $theme_search['slug'] = $search['slug']."/themes/".$theme_search['slug'];
            if($info = installerHelper::search($app['extras']['themes'], $theme_search)) {
                if(!$theme['path']) {
                    $theme['name'] = $info['name'];
                    $theme['description'] = $info['description'];
                    $theme['vendor'] = $info['vendor'];
                    $theme['install_info'] = $info;
                } else {
                    $theme['updates_info'] = $info;
                }
                if(!empty($info['payware'])) {
                    $theme['payware'] = $info['payware'];
                }
                if(!empty($info['img'])) {
                    $theme['cover'] = $info['img'];
                }
            }
            $this->view->assign('identity_hash', installerHelper::getHash());
            $this->view->assign('theme', $theme);
            $this->view->assign('messages', $messages);
        } else {
            throw new waException(_w('Theme not found'), 404);
        }
    }

}
