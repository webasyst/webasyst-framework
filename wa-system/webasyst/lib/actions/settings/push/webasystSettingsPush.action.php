<?php

class webasystSettingsPushAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $selected_push_adapter = $model->get('webasyst', 'push_adapter', null);

        $push_adapters = wa()->getPushAdapters();

        if ($selected_push_adapter && !array_key_exists($selected_push_adapter, $push_adapters)) {
            $selected_push_adapter = null;
            $model->set('webasyst', 'push_adapter', null);
        }

        $is_push_enabled = false;
        if (!empty($selected_push_adapter)) {
            try {
                $push = wa()->getPush();
                $is_push_enabled = !empty($push) && $push->isEnabled();
            } catch (waException $e) {}
        }

        $this->view->assign(array(
            'selected_push_adapter' => $selected_push_adapter,
            'push_adapters'         => $push_adapters,
            'is_push_enabled'       => $is_push_enabled,
        ));
    }
}