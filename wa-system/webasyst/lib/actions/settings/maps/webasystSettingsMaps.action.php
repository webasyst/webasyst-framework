<?php

class webasystSettingsMapsAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $map_adapters = wa()->getMapAdapters();

        $model = new waAppSettingsModel();
        $selected_map_adapter = $model->get('webasyst', 'map_adapter', 'google');

        $this->view->assign(array(
            'selected_map_adapter' => $selected_map_adapter,
            'is_map_disabled'      => $selected_map_adapter === 'disabled',
            'map_adapters'         => $map_adapters,
        ));
    }
}
