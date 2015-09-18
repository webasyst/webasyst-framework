<?php

class blogEmailsubscriptionPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        /**
         * @deprecated
         * For backward compatibility reason
         */
        $this->view->assign('cron_schedule_time', waSystem::getSetting('cron_schedule',0, 'blog'));

        
        $this->view->assign(
                'last_emailsubscription_cron_time', 
                waSystem::getSetting('last_emailsubscription_cron_time', 0, array('blog', 'emailsubscription'))
        );
        $this->view->assign('cron_command', 'php '.wa()->getConfig()->getRootPath().'/cli.php blog emailsubscription');
    }
}
