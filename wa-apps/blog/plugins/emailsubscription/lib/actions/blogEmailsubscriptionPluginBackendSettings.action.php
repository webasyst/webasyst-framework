<?php

class blogEmailsubscriptionPluginBackendSettingsAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('cron_schedule_time', waSystem::getSetting('cron_schedule',0,'blog'));
    }
}
