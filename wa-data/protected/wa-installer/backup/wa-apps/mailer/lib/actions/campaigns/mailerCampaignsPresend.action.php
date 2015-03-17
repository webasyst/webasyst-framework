<?php

/**
 * Returns HTML to insert into CampaignsSettings page
 * when user clicks the 'Send to selected recipients' link.
 * Validates campaign and either show error messages, or a button to proceed.
 * When user clicks the button, a POST is sent to this controller again,
 * and the sending starts.
 */
class mailerCampaignsPresendAction extends waViewAction
{
    public function execute()
    {
        $campaign_id = waRequest::get('campaign_id', 0, 'int');
        if (!$campaign_id) {
            throw new waException('No campaign id given.', 404);
        }

        // Campaign data
        $mm = new mailerMessageModel();
        $campaign = $mm->getById($campaign_id);
        if (!$campaign) {
            throw new waException('Campaign not found.', 404);
        }
        if ($campaign['status'] != mailerMessageModel::STATUS_DRAFT && $campaign['status'] != mailerMessageModel::STATUS_PENDING) {
            echo "<script>window.location.hash = '#/campaigns/report/{$campaign_id}/';</script>";
            exit;
        }

        // Access control
        if (mailerHelper::campaignAccess($campaign) < 2) {
            throw new waException('Access denied.', 403);
        }

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        // Start sending the campaign if POST came and validation passes.
        $errormsg = self::localValidate($campaign, $params);
        if (waRequest::post('send') && !$errormsg) {
            $errormsg = self::eventValidate($campaign, $params);
            if (!$errormsg) {
                mailerHelper::prepareRecipients($campaign, $params);
                // check schedule campaigns (we should 'prepare' Recipients, check events, but not send campaign)
                if ($campaign['status'] != mailerMessageModel::STATUS_PENDING) {
                    echo "<script>window.location.hash='#/campaigns/report/{$campaign_id}/';</script>";
                    exit;
                }
            }
        }

        $this->view->assign('errormsg', $errormsg);
        $this->view->assign('cron_command', 'php '.wa()->getConfig()->getRootPath().'/cli.php mailer send<br>php '.wa()->getConfig()->getRootPath().'/cli.php mailer check');
        $this->view->assign('cron_ok', wa()->getSetting('last_cron_time') + 3600*2 > time());
        $this->view->assign('last_cron_time', wa()->getSetting('last_cron_time'));
        $this->view->assign('return_path_ok', $this->isReturnPathOk($campaign, $params));
        $this->view->assign('unique_recipients', $params['recipients_count']);
        $this->view->assign('routing_ok', !!wa()->getRouteUrl('mailer', true));

        $this->view->assign('scheduled', $campaign['status'] == mailerMessageModel::STATUS_PENDING );
        $this->view->assign('scheduled_time', $campaign['send_datetime'] );
    }

    /** Local validation: check basic campaign properties. */
    public static function localValidate($campaign, &$params)
    {
        $errormsg = array();
        if (!trim($campaign['body'])) {
            $errormsg[] = _w('No message body.');
        }
        if (!trim($campaign['subject'])) {
            $errormsg[] = _w('No message subject.');
        }

        // Check if there are recipients selected
        $action = waRequest::post('send') ? null : 'UpdateDraftRecipientsTable'; // if we actually dont send - will update draft recipients table and count
        $params['recipients_count'] = mailerHelper::countUniqueRecipients($campaign, $params, null, $errors, $action);
        if ($params['recipients_count'] <= 0) {
            $errormsg[] = _w('No recipients selected.');
        }

        // Check if this campaign has more recipients than it is allowed
        $max_recipients_count = wa('mailer')->getConfig()->getOption('max_recipients_count');
        if ($max_recipients_count && $max_recipients_count < $params['recipients_count']) {
            $errormsg[] = _w('Maximum recipients limit has been exceeded:').' '.$max_recipients_count;
        }

        // Being paranoid: check that wa-data and wa-cache are available for writing
        foreach(array(wa()->getDataPath('', false, 'mailer'), waConfig::get('wa_path_cache')) as $path) {
            if (!file_exists($path)) {
                @waFiles::create($path);
            }
            if (!is_writable($path)) {
                $errormsg[] = sprintf_wp('%s is not writable', $path);
            }
        }

        // Check if daily limit exceeded
        $max_recipients_daily = wa('mailer')->getConfig()->getOption('max_recipients_daily');
        if ($max_recipients_daily) {
            $mlm = new mailerMessageLogModel();
            if ($max_recipients_daily < $mlm->countSentToday() + $params['recipients_count']) {
                $errormsg[] = _w('Maximum recipients daily limit has been exceeded:').' '.$max_recipients_daily;
            }
        }

        return $errormsg;
    }

    /** Allows plugins to validate campaign before sending. */
    public static function eventValidate($campaign_or_id, $params=null)
    {

        if (is_array($campaign_or_id)) {
            $campaign = $campaign_or_id;
        } else {
            $mm = new mailerMessageModel();
            $campaign = $mm->getById($campaign_or_id);
        }
        if ($params === null) {
            $mpm = new mailerMessageParamsModel();
            $params = $mpm->getByMessage($campaign['id']);
        }

        /**@/**
         * @event campaign.validate
         *
         * Allows to validate and cancel campaign before sending
         *
         * @param array[string]array $params['campaign'] input: row from mailer_message
         * @param array[string]array $params['params'] input: campaign params from mailer_message_params, key => value
         * @param array[string]array $params['errors'] output: list of error message strings to show to user
         * @return void
         */
        $evt_params = array(
            'campaign' => &$campaign, // INPUT
            'params' => &$params,     // INPUT
            'errors' => array(),      // OUTPUT
        );
        wa()->event('campaign.validate', $evt_params);
        return (array) $evt_params['errors'];
    }

    /** Returns false if there's a problem connecting to this campaign's return path */
    protected function isReturnPathOk($campaign)
    {
        if (empty($campaign['return_path'])) {
            return array(
                'status'=>true
            );
        }

        $rpm = new mailerReturnPathModel();
        $rp = $rpm->getByEmail($campaign['return_path']);
        if (!$rp) {
            return array(
                'status'=>false,
                'reason'=>1,
                'return-path'=>$campaign['return_path']
            );
        }

        // Check if SSL is supported
        if (!defined('OPENSSL_VERSION_NUMBER') && !empty($data['ssl'])) {
            return array(
                'status'=>false,
                'reason'=>2,
                'return-path'=>$campaign['return_path']
            );
        }

        // check return-path form smtp sender
        $mm = new mailerMessage($campaign);
        if ($mm->testReturnPathSmtpSender() === false) {
            return array(
                'status'=>false,
                'reason'=>4,
                'return-path'=>$campaign['return_path']
            );
        }

        // Check cache in session
        $status = wa()->getStorage()->get('mailer_rp_status_'.$rp['id']);
        if (isset($status)) {
            return array(
                'reason'=>5,
                'status'=>$status
            );
        }

        // Try to connect using given settings
        try {
            $mail_reader = new waMailPOP3($rp);
            $mail_reader->count();
            wa()->getStorage()->set('mailer_rp_status_'.$rp['id'], true);
            return array(
                'status'=>true
            );
        } catch (Exception $e) {
        }

        wa()->getStorage()->set('mailer_rp_status_'.$rp['id'], false);
        return array(
            'status'=>false,
            'reason'=>3
        );
    }
}

