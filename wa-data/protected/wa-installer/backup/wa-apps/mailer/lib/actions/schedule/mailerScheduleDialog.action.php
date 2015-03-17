<?php
class mailerScheduleDialogAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::get('id');
        $scheduled_campaign = new mailerMessageModel();
        $campaign = $scheduled_campaign->getById($id);
        // today by default
        $schedule_date_server = time();
        $schedule_date = strtotime(waDateTime::date('Y-m-d H:i:s', $schedule_date_server, wa()->getUser()->getTimezone()));
        // +1 hour by default
        $schedule_hour = waDateTime::date('H', strtotime('+1 hour', $schedule_date));
        // if now is 45 min or greater
        if (waDateTime::date('i', $schedule_date, wa()->getUser()->getTimezone()) >= 45) {
            $schedule_hour =  waDateTime::date('H', strtotime('+2 hour', $schedule_date));
        }
        $schedule_min = 0;

        // campaign already scheduled
        if (strlen($campaign['send_datetime']) === 19) {
            $schedule_date_server = strtotime($campaign['send_datetime']);
            $schedule_hour = date('H', $schedule_date);
            $schedule_min = date('i', $schedule_date);
        }

        $this->view->assign('cron_ok', wa()->getSetting('last_cron_time') + 3600*2 > time());
        $this->view->assign('schedule_date', $schedule_date_server);
        $this->view->assign('schedule_hour', $schedule_hour);
        $this->view->assign('schedule_min', $schedule_min);
        $this->view->assign('campaign', $campaign);
        $this->view->assign('last_cron_time', wa()->getSetting('last_cron_time'));
    }
}