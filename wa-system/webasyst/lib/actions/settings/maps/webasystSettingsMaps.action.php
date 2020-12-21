<?php

class webasystSettingsMapsAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $map_adapters = wa()->getMapAdapters();

        $model = new waAppSettingsModel();
        $selected_map_adapter = $model->get('webasyst', 'map_adapter', 'google');
        $backend_selected_map_adapter = $model->get('webasyst', 'backend_map_adapter', 'google');

        $this->view->assign(array(
            'map_adapters' => $map_adapters,
            'selected_map_adapter' => $selected_map_adapter,
            'is_map_disabled' => $selected_map_adapter === 'disabled',
            'backend_selected_map_adapter' => $backend_selected_map_adapter,
            'is_backend_map_disabled' => $backend_selected_map_adapter === 'disabled',
        ));
    }
}
