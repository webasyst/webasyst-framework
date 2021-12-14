<?php

class webasystSettingsTemplateSMSSidebarAction extends webasystSettingsTemplateAction
{
    public function execute()
    {
        $channels = $this->getVerificationChannelModel()->getByType(waVerificationChannelModel::TYPE_SMS);

        if (!$this->channel instanceof waVerificationChannelSMS) {
            $this->channel = new waVerificationChannelNull();
        }

        $this->setTemplate('settings/sidebar/SidebarSMS.html', true);

        $this->view->assign(array(
            'channel'  => $this->channel,
            'channels' => $channels,
        ));
    }
}
