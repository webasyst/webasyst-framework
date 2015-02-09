<?php

class mailerScheduleSaveController extends waJsonController
{
    public function execute()
    {
        // scheduled campaign id
        $message_id = waRequest::post('id', 0, 'int');
        // scheduled datetime
        $datetime = waRequest::post('schedule_datetime', array());

        foreach($datetime as $d) {
            if (strlen($d) === 0) {
                $this->errors = array('time' => 'no date');
                return;
            }
        }

        $mm = new mailerMessageModel();
        // if we have message id
        if ($message_id) {
            // getting campaign by id
            $campaign = $mm->getById($message_id);
            // if we don't have one or it already sent?
            if (!$campaign || $campaign['status'] > 0) {
                $this->response = $message_id;
                return;
            }

            // Access control
            if (mailerHelper::campaignAccess($campaign) < 2) {
                throw new waException('Access denied.', 403);
            }
        } else {
            // Access control
            if (mailerHelper::isAuthor() < 2) {
                throw new waException('Access denied.', 403);
            }
        }

        // validate income datetime
        if ($this->combineDate($datetime)) {

            $params['send_datetime'] = $datetime;
            $params['status'] = mailerMessageModel::STATUS_PENDING;

        } else {
            $this->errors = array('params' => 'bad params');
        }

        // check for past datetime
        if (strtotime($datetime) >= time()) {
            $mm->updateById($message_id, $params);
        } else {
            $this->errors = array('time' => 'past time');
        }
        if (empty($this->errors) && !empty($campaign)) {
            /**@/**
             * @event campaign.before_sending
             *
             * A sending session started for all campaigns
             *
             * For all campaigns there could be one sending session. It can be triggered by CRON,
             * or by a backend user opening campaign report page (for each campaign).
             *
             * @return void
             */
            wa()->event('campaign.before_sending');

            $this->logAction('scheduled_delayed_sending');
        }

        $this->response = $message_id;
    }

    protected function combineDate(&$dt)
    {
        // if not empty datetime array and we have date, hours, minutes
        if (!empty($dt) && count($dt) === 3){
            $dt = $dt[0]." ".$dt[1].":".$dt[2];
            // to mysql format
            $dt = waDateTime::parse('datetime', $dt);

            return true;
        }
        return false;
    }
}
