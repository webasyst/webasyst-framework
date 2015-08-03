<?php

class webasystDashboardActions extends waActions
{
    protected static $widget_model;

    public function widgetResizeAction()
    {
        $id = waRequest::get('id');
        $size = waRequest::post('size');
        if ($size) {
            $this->getWidget($id);
            $this->getWidgetModel()->updateById($id, array('size' => $size));
            $this->displayJson(array());
        } else {
            throw new waException(_ws('Incorrect size'));
        }
    }

    public function widgetMoveAction()
    {
        $id = waRequest::get('id');
        // get new position
        $block = waRequest::post('block');
        $sort = waRequest::post('sort');
        // check widget
        $w = $this->getWidget($id);
        $r = $this->getWidgetModel()->move($w, $block, $sort, waRequest::post('new_block'));
        $this->displayJson(array('result' => $r));
    }

    public function widgetDeleteAction()
    {
        $id = waRequest::post('id');
        $w = $this->getWidget($id);
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

        $widgets = wa($data['app_id'])->getConfig()->getWidgets();
        $data['name'] = $widgets[$data['widget']]['name'];
        $id = $this->getWidgetModel()->add($data, $new_block);
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
        $w = $this->getWidget($id);

        $widget = waSystem::getInstance()->getWidget($id);
        $widget->loadLocale(true);

        $namespace = 'widget_'.$id;

        $params = array();
        $params['id'] = $w['widget'];
        $params['namespace'] = $namespace;
        $params['title_wrapper'] = '%s';
        $params['description_wrapper'] = '<br><span class="hint">%s</span>';
        $params['control_wrapper'] = '<div class="name">%s</div><div class="value">%s %s</div>';

        $settings_controls = $widget->getControls($params);

        $this->display(array('widget' => $w, 'settings_controls' => $settings_controls));
    }

    public function widgetSaveAction()
    {
        $id = waRequest::get('id');
        if (!$id) {
            throw new waException(_ws("Can't save plugin settings: unknown plugin id"));
        }
        $namespace = 'widget_'.$id;
        /**
         * @var shopPlugin $plugin
         */
        $widget = waSystem::getInstance()->getWidget($id);
        $settings = waRequest::post($namespace, array());
        $files = waRequest::file($namespace);
        $settings_defenitions = $widget->getSettings();
        foreach ($files as $name => $file) {
            if (isset($settings_defenitions[$name])) {
                $settings[$name] = $file;
            }
        }
        $response = $widget->setSettings($settings);
        $response['message'] = _w('Saved');
        $this->displayJson($response);
    }

    public function closeTutorialAction()
    {
        wa()->getUser()->setSettings('webasyst', 'widget_tutorial_closed', 1);
        $this->displayJson('ok');
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

    /**
     * @param int $id
     * @return array
     * @throws waException
     */
    protected function getWidget($id)
    {
        $w = $this->getWidgetModel()->getById($id);
        if (!$w || ($w['contact_id'] != $this->getUserId())) {
            throw new waException(_ws('Widget not found'));
        }
        return $w;
    }
}