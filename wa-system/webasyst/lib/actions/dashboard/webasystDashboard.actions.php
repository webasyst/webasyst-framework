<?php

class webasystDashboardActions extends waActions
{
    protected static $widget_model;

    public function widgetResizeAction()
    {
        $id = waRequest::get('id');
        $size = waRequest::post('size');
        if ($size) {
            if (!wa()->getWidget($id)->isAllowed()) {
                throw new waException(_ws('Widget not found'), 404);
            }
            $this->getWidgetModel()->updateById($id, array('size' => $size));
            $this->displayJson(array());
        } else {
            throw new waException(_ws('Incorrect size'));
        }
    }

    public function widgetMoveAction()
    {
        $id = waRequest::get('id');
        $block = waRequest::post('block');
        $sort = waRequest::post('sort');

        // check widget
        $widget = wa()->getWidget($id);
        if (!$widget->isAllowed()) {
            throw new waException(_ws('Widget not found'), 404);
        }

        $response = array(
            'old' => array(
                'block' => $widget->getInfo('block'),
                'sort' => $widget->getInfo('sort'),
            )
        );
        $response['result'] = $this->getWidgetModel()->move($widget->getInfo(), $block, $sort, waRequest::post('new_block'));
        $this->displayJson($response);
    }

    public function widgetDeleteAction()
    {
        $id = waRequest::post('id');
        if (!wa()->getWidget($id)->isAllowed()) {
            throw new waException(_ws('Widget not found'), 404);
        }

        $this->getWidgetModel()->delete($id);
        $this->displayJson(array('result' => 1));
    }

    public function sidebarAction()
    {
        $apps = array('webasyst' => wa()->getAppInfo('webasyst'))  + wa()->getUser()->getApps();

        $locale = wa()->getUser()->getLocale();

        $widgets = array();
        foreach ($apps as $app_id => $app) {
            foreach (wa($app_id)->getConfig()->getWidgets() as $w_id => $w) {
                if (!empty($w['rights'])) {
                    if (!waWidget::checkRights($w['rights'])) {
                        continue;
                    }
                }
                if (!empty($w['locale']) && ($w['locale'] != $locale)) {
                    continue;
                }
                $w['size'] = $w['sizes'][0][0] . 'x' . $w['sizes'][0][1];
                $widgets[$app_id][] = $w;
            }
        }
        foreach ($apps as $app_id => $app) {
            if (!isset($widgets[$app_id])) {
                unset($apps[$app_id]);
            }
        }
        $this->display(array('widgets' => $widgets, 'apps' => $apps));
    }

    public function widgetAddAction()
    {
        $data = waRequest::post();
        if (isset($data['new_block'])) {
            $new_block = $data['new_block'];
            unset($data['new_block']);
        } else {
            $new_block = false;
        }

        $dashboard_id = waRequest::request('dashboard_id', null, 'int');
        if ($dashboard_id && !wa()->getUser()->isAdmin('webasyst')) {
            throw new waException('Access denied', 403);
        }

        $widgets = wa($data['app_id'])->getConfig()->getWidgets();
        $data['name'] = $widgets[$data['widget']]['name'];
        $id = $this->getWidgetModel()->add($data, $new_block, ifempty($dashboard_id, null));
        $w = wa()->getWidget($id)->getInfo();
        $w['size'] = explode('x', $w['size']);
        $w['sizes'] = $widgets[$data['widget']]['sizes'];
        foreach ($w['sizes'] as $s) {
            if ($s == array(1, 1)) {
                $w['has_sizes']['small'] = true;
            } elseif ($s == array(2, 1)) {
                $w['has_sizes']['medium'] = true;
            } elseif ($s == array(2, 2)) {
                $w['has_sizes']['big'] = true;
            }
        }
        $w['has_settings'] = $widgets[$data['widget']]['has_settings'];
        $this->displayJson(array(
            'id' => $id,
            'html' => $this->display(array('w' => $w), $this->getPluginRoot().'templates/actions/dashboard/DashboardWidget.html', true)
        ));
    }

    public function widgetSettingsAction()
    {
        $id = waRequest::get('id');
        $widget = wa()->getWidget($id);
        if (!$widget->isAllowed()) {
            throw new waException(_ws('Widget not found'), 404);
        }

        $widget->loadLocale(true);
        $this->display(array(
            'widget' => $widget->getInfo(),
            'settings_controls' => $widget->getControls(array(
                'id' => $widget->getInfo('widget'),
                'namespace' => 'widget_'.$id,
                'description_wrapper' => '<br><span class="hint">%s</span>',
                'control_wrapper' => '<div class="name">%s</div><div class="value">%s %s</div>',
                'title_wrapper' => '%s',
            )),
        ));
    }

    public function widgetSaveAction()
    {
        $id = waRequest::get('id');
        $widget = wa()->getWidget($id);
        if (!$widget->isAllowed()) {
            throw new waException(_ws('Widget not found'), 404);
        }

        $namespace = 'widget_'.$id;
        $settings = waRequest::post($namespace, array());
        $settings_defenitions = $widget->getSettings();
        foreach (waRequest::file($namespace) as $name => $file) {
            if (isset($settings_defenitions[$name])) {
                $settings[$name] = $file;
            }
        }

        $response = $widget->setSettings($settings);
        $response['message'] = _w('Saved');
        $response['block'] = $widget->getInfo('block');
        $response['sort'] = $widget->getInfo('sort');
        $this->displayJson($response);
    }

    public function closeTutorialAction()
    {
        wa()->getUser()->setSettings('webasyst', 'widget_tutorial_closed', 1);
        $this->displayJson('ok');
    }

    public function editPublicAction()
    {
        if (!wa()->getUser()->isAdmin('webasyst')) {
            throw new waException('Access denied', 403);
        }

        $dashboard_id = waRequest::request('dashboard_id', 0, 'int');

        $dashboard_model = new waDashboardModel();
        $dashboard = $dashboard_model->getById($dashboard_id);
        if (!$dashboard) {
            throw new waException(_w('Not found'), 404);
        }

        // fetch widgets
        $widgets = array();
        $widget_model = new waWidgetModel();
        $rows = $widget_model->getByDashboard($dashboard_id);
        foreach ($rows as $row) {
            $app_widgets = wa($row['app_id'])->getConfig()->getWidgets();
            if (isset($app_widgets[$row['widget']])) {
                $row['size'] = explode('x', $row['size']);
                $row = $row + $app_widgets[$row['widget']];
                $row['href'] = wa()->getAppUrl($row['app_id'])."?widget={$row['widget']}&id={$row['id']}";
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

        $dashboard_url = wa()->getConfig()->getRootUrl(true).wa()->getConfig()->getBackendUrl(false);
        $dashboard_url .= "/dashboard/{$dashboard['hash']}/";

        $this->display(array(
            'dashboard' => $dashboard,
            'dashboard_url' => $dashboard_url,
            'header_date' => _ws(waDateTime::date('l')).', '.trim(str_replace(date('Y'), '', waDateTime::format('humandate')), ' ,/'),
            'widgets' => $widgets,
        ));
    }

    public function dashboardDeleteAction()
    {
        if (!wa()->getUser()->isAdmin('webasyst')) {
            throw new waException('Access denied', 403);
        }

        $id = waRequest::post('id', 0, 'int');
        if ($id) {
            $dashboard_model = new waDashboardModel();
            $dashboard_model->delete($id);
        }
        $this->displayJson(array('result' => 1));
    }

    public function dashboardSaveAction()
    {
        if (!wa()->getUser()->isAdmin('webasyst')) {
            throw new waException('Access denied', 403);
        }

        $id = waRequest::request('id', 0, 'int');
        $data = waRequest::request('dashboard', array(), 'array');

        $dashboard_model = new waDashboardModel();
        $data = array_intersect_key($data, $dashboard_model->getEmptyRow());
        unset($data['id'], $data['hash']);

        if ($id) {
            $dashboard_model->updateById($id, $data);
        } else {
            $data['hash'] = self::generateHash();
            $id = $dashboard_model->insert($data);
        }

        $this->displayJson($dashboard_model->getById($id));
    }

    public static function generateHash()
    {
        $result = '';
        $chars = '123456789';
        for($i = 1; $i < 20; $i++) {
            if ($i % 5 == 0) {
                $result .= '00';
            } else {
                $result .= $chars{mt_rand(0, strlen($chars)-1)};
            }
        }
        return $result;
    }

    /**
     * @return waWidgetModel
     */
    protected function getWidgetModel()
    {
        if (!self::$widget_model) {
            self::$widget_model = new waWidgetModel();
        }
        return self::$widget_model;
    }
}