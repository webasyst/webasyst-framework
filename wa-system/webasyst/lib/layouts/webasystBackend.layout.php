<?php

/**
 * Class webasystBackendLayout
 *
 * This layout for root webasyst backend page, where is show dashboard sidebar
 */
class webasystBackendLayout extends waLayout
{
    use webasystHeaderTrait;

    public function execute()
    {
        $this->view->assign([
            'apps' => $this->getApps(),
            'logo' => (new webasystLogoSettings())->get(),
            'counts' => wa()->getStorage()->read('apps-count'),
            'backend_url' => wa()->getConfig()->getBackendUrl(true),
            'current_app' => wa()->getApp(),
            'reuqest_uri' => waRequest::server('REQUEST_URI'),
            'root_url' => wa()->getRootUrl(),
            'dashboard_module_url' => wa()->getAppUrl('webasyst') . 'webasyst/dashboard/',
            'public_dashboards' => $this->getPublicDashboards(),
            'notifications' => $this->getAnnouncements(['one_per_app' => false]),
            'selected_sidebar_item' => $this->getSelectedSidebarItem(),
            'teams' => $this->getTeams()
        ]);
    }

    protected function getSelectedSidebarItem()
    {
        $request_uri = waRequest::server('REQUEST_URI');
        $dashboard_module_url = wa()->getAppUrl('webasyst') . 'webasyst/dashboard/';

        $is_prefix = strpos($request_uri, $dashboard_module_url) === 0;
        if ($is_prefix) {
            return trim(substr($request_uri, strlen($dashboard_module_url)), '/');
        }

        return 'my';
    }

    public function getApps()
    {
        // return array('webasyst' => wa()->getAppInfo('webasyst'))  + wa()->getUser()->getApps();
        return wa()->getUser()->getApps();
    }

    protected function getPublicDashboards()
    {
        $is_admin = wa()->getUser()->isAdmin('webasyst');

        $public_dashboards = [];
        if ($is_admin) {
            $dashboard_model = new waDashboardModel();
            $public_dashboards = $dashboard_model->order('name')->fetchAll('id');
        }

        return $public_dashboards;
    }

    protected function getTeams()
    {
        $ids = $this->getUser()->getGroups();
        $gm = new waGroupModel();
        return $gm->getById($ids);
    }
}
