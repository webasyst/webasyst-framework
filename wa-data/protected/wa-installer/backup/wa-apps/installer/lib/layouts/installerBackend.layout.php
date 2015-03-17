<?php

class installerBackendLayout extends waLayout
{
    public function execute()
    {
        $messages = installerMessage::getInstance()->handle(waRequest::get('msg'));
        installerHelper::checkUpdates($messages);
        if ($m = $this->view->getVars('messages')) {
            $messages = array_merge($m, $messages);
        }
        $this->view->assign('messages', $messages);
        $plugins = 'wa-plugins/payment';
        $apps = wa()->getApps();
        if (isset($apps['shop'])) {
            $plugins = 'shop';
        } else {
            ksort($apps);
            foreach ($apps as $app => $info) {
                if (!empty($info['plugins'])) {
                    $plugins = $app;
                    break;
                }
            }
        }

        $model = new waAppSettingsModel();
        $this->view->assign('update_counter', $model->get($this->getApp(), 'update_counter'));
        $this->view->assign('module', waRequest::get('module', 'backend'));
        $this->view->assign(
            'default_query',
            array(
                'plugins' => $plugins,
            )
        );
    }
}
