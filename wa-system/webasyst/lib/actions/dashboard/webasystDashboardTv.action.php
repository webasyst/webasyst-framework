<?php
/**
 * This action can possibly run for UNAUTHORIZED user. Beware!
 */
class webasystDashboardTvAction extends waViewAction
{
    public function execute()
    {
        $url = explode("/", wa()->getConfig()->getRequestUrl(true, true));
        $widgets_hash = ifset($url[2]);

        $dashboard_model = new waDashboardModel();
        $dashboard = $dashboard_model->getByField('hash', $widgets_hash);
        if (!$dashboard) {
            throw new waException('Not found', 404);
        }

        $widget_id = waRequest::request('widget_id', 0, 'int');
        if ($widget_id) {
            $this->showWidget($dashboard, $widget_id);
        } else {
            $this->showLayout($dashboard);
        }
    }

    public function showWidget($dashboard, $widget_id)
    {
        $widget = wa()->getWidget($widget_id);
        if ($dashboard['id'] != $widget->getInfo('dashboard_id')) {
            throw new waException('Not found', 404);
        }

        $app_id = $widget->getInfo('app_id');
        if ($app_id != 'webasyst') {
            wa($app_id, true);
            waSystem::pushActivePlugin($widget->getInfo('widget'), $app_id.'_widget');
        }
        $widget->loadLocale($app_id == 'webasyst');
        $widget->run(null);
        exit;
    }

    public function showLayout($dashboard)
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

        sort($hash_data);
        $dashboard_status = md5($dashboard['name'].'/'.join('/', $hash_data));

        if (waRequest::request('check_status')) {
            echo json_encode(array(
                'status' => 'ok',
                'data' => $dashboard_status,
            ));
            exit;
        }

        $this->view->assign(array(
            'dashboard_status' => $dashboard_status,
            'dashboard' => $dashboard,
            'widgets' => $widgets,
        ));
    }
}
