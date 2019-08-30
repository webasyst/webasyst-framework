<?php

class webasystSettingsPushAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $selected_push_adapter = $model->get('webasyst', 'push_adapter', null);

        $push_adapters = wa()->getPushAdapters();

        $this->view->assign(array(
            'selected_push_adapter' => $selected_push_adapter,
            'push_adapters'         => $push_adapters,
        ));
    }
}