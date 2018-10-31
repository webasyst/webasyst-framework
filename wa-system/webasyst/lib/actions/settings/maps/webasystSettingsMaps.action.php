<?php

class webasystSettingsMapsAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $selected_map_adapter = $model->get('webasyst', 'map_adapter', 'google');

        $map_adapters = wa()->getMapAdapters();

        $this->view->assign(array(
            'map_adapters'         => $map_adapters,
            'selected_map_adapter' => $selected_map_adapter,
        ));
    }
}