<?php

class webasystDashboardDashboardAction extends webasystDashboardViewAction
{
    public function preExecute()
    {
        // This page doesn't exist in old webasyst UI
        if (wa()->whichUI() !== '2.0') {
            $this->redirect(wa()->getConfig()->getBackendUrl(true));
        }
        parent::preExecute();
    }

    public function execute()
    {
        $id = $this->getDashboardId();

        $dashboard_model = new waDashboardModel();
        $dashboard = $dashboard_model->getById($id);
        if (!$dashboard) {
            throw new waException('Not found', 404);
        }

        $this->showDashboardLayout($dashboard);
    }

    protected function getDashboardId()
    {
        $id = waRequest::param('id');
        if ($id === NULL) {
            $id = waRequest::post('id', NULL, 'int');
        }
        return $id;
    }

    public function showDashboardLayout($dashboard)
    {
        // fetch widgets
        $widgets = array();
        $hash_data = array();
        $widget_model = new waWidgetModel();
        $rows = $widget_model->getByDashboard($dashboard['id']);
        $base_href = wa()->getConfig()->getBackendUrl(true)."dashboard/{$dashboard['hash']}/";
        foreach ($rows as $row) {
            $app_widgets = wa($row['app_id'])->getConfig()->getWidgets();
            if (isset($app_widgets[$row['widget']])) {
                $hash_data[] = join('.', array($row['id'], $row['block'], $row['sort'], $row['size']));
                $row['size'] = explode('x', $row['size']);
                $row = $row + $app_widgets[$row['widget']];
                $row['href'] = $base_href."?widget_id={$row['id']}";
                foreach ($row['sizes'] as $s) {
                    if ($s == array(1, 1)) {
                        $row['has_sizes']['small'] = true;
                    } elseif ($s == array(2, 1)) {
                        $row['has_sizes']['medium'] = true;
                    } elseif ($s == array(2, 2)) {
                        $row['has_sizes']['big'] = true;
                    }
                }
                $widgets[$row['block']][] = $row;
            }
        }

        $this->view->assign([
            'dashboard'             => $dashboard,
            'widgets'               => $widgets,
            'public_dashboards'     => $this->getPublicDashboards(),
            'selected_sidebar_item' => $this->getSelectedSidebarItem(),
            'dashboard_module_url'  => wa()->getAppUrl('webasyst') . 'webasyst/dashboard/',
            'backend_url'           => wa()->getConfig()->getBackendUrl(true),
            'is_admin'              => wa()->getUser()->isAdmin('webasyst'),
        ]);
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

    protected function getSelectedSidebarItem()
    {
        $request_uri = waRequest::server('REQUEST_URI');
        $dashboard_module_url = wa()->getAppUrl('webasyst') . 'webasyst/dashboard/';

        $is_prefix = strpos($request_uri, $dashboard_module_url) === 0;
        if ($is_prefix) {
            $str = trim(substr($request_uri, strlen($dashboard_module_url)), '/');
            // with removing get-params
            return preg_replace('/\/\?\S*/', '', $str);
        }

        return 'my';
    }
}
