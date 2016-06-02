<?php

class webasystConfig extends waAppConfig
{
    public function getAppPath($path = null)
    {
        return $this->getRootPath() . '/wa-system/' . $this->application . ($path ? '/' . $path : '');
    }

    public function getWidgetPath($widget_id)
    {
        return $this->getRootPath()."/wa-widgets/".$widget_id;
    }

    public function initUserWidgets($force = false, waContact $contact = null)
    {
        if (!$contact) {
            $contact = wa()->getUser();
        }
        if (!$force && $contact->getSettings('webasyst', 'dashboard')) {
            return;
        }

        $contact_id = $contact->getId();
        $contact->setSettings('webasyst', 'dashboard', 1);

        // Delete existing widgets
        $widget_model = new waWidgetModel();
        foreach($widget_model->getByContact($contact_id) as $row) {
            $widget_model->delete($row['id']);
        }

        // Read config file and fetch list of widget blocks
        // depending on contact's locale.
        $locale_blocks = $this->getInitWidgetsConfig();
        if (!$locale_blocks) {
            return;
        }
        $locale = $contact->getLocale();
        if (isset($locale_blocks[$locale])) {
            $blocks = $locale_blocks[$locale];
        } else {
            $blocks = reset($locale_blocks);
        }

        // Read config block by block and save widgets to DB
        $block_id = 0;
        $app_widgets = array();
        $empty_row = $widget_model->getEmptyRow();
        foreach ($blocks as $block) {
            if (isset($block['widget'])) {
                $block = array($block);
            }
            $sort = 0;
            foreach($block as $widget_data) {
                $app_id = ifset($widget_data['app_id'], 'webasyst');
                if (!isset($app_widgets[$app_id])) {
                    $app_widgets[$app_id] = array();
                    if (wa()->appExists($app_id) && ($app_id == 'webasyst' || $contact->getRights($app_id, 'backend'))) {
                        $app_widgets[$app_id] = wa($app_id)->getConfig()->getWidgets();
                    }
                }

                $widget_data['widget'] = ifset($widget_data['widget'], '');
                if (empty($app_widgets[$app_id][$widget_data['widget']])) {
                    waLog::log('Unable to instantiate widget app_id='.$app_id.' widget='.$widget_data['widget'].' for user contact_id='.$contact_id.' (does not exist or no access to app)');
                    continue;
                }

                $w = $app_widgets[$app_id][$widget_data['widget']];
                if (!empty($w['rights'])) {
                    if (!waWidget::checkRights($w['rights'])) {
                        waLog::log('Unable to instantiate widget app_id='.$app_id.' widget='.$widget_data['widget'].' for user contact_id='.$contact_id.' (access denied)');
                        continue;
                    }
                }

                $widget_data['name'] = $w['name'];
                $widget_data['app_id'] = $app_id;
                $widget_data['dashboard_id'] = null;
                $widget_data['size'] = ifset($widget_data['size'], reset($w['sizes']));
                $widget_data['create_datetime'] = date('Y-m-d H:i:s');
                $widget_data['contact_id'] = $contact_id;
                $widget_data['block'] = $block_id;
                $widget_data['sort'] = $sort;

                $params = ifset($widget_data['params'], array());
                unset($widget_data['params']);

                $id = null;
                try {
                    $id = $widget_model->insert(array_intersect_key($widget_data, $empty_row) + $empty_row);
                    wa()->getWidget($id)->setSettings($params);
                    $sort++;
                } catch (Exception $e) {
                    waLog::log('Unable to instantiate widget app_id='.$app_id.' widget='.$widget_data['widget'].' for user contact_id='.$contact_id.': '.$e->getMessage());
                    $id && $widget_model->delete($id);
                }
            }
            if ($sort > 0) {
                $block_id++;
            }
        }
    }

    protected function getInitWidgetsConfig()
    {
        $custom_config = wa('webasyst')->getConfig()->getConfigPath('init_widgets.php');
        if (file_exists($custom_config)) {
            return include($custom_config);
        } else {
            $basic_config = wa('webasyst')->getConfig()->getAppPath('lib/config/init_widgets.php');
            if (file_exists($basic_config)) {
                return include($basic_config);
            }
        }
        return array();
    }
}

