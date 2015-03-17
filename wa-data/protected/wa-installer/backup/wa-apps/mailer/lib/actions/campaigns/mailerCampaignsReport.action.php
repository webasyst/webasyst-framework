<?php

/**
 * Report on campaign that is sent or being sent.
 */
class mailerCampaignsReportAction extends waViewAction
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
        if ($campaign['status'] <= 0) {
            throw new waException('Unable to show report for a message draft.', 404);
        }
        if ($campaign['status'] == mailerMessageModel::STATUS_PENDING) {
            throw new waException('Unable to show report for a pending message.', 404);
        }
        if ($campaign['status'] == mailerMessageModel::STATUS_CONTACTS) {
            // Something bad must have happened during initial sending phase
            // (address preparation).
            $m = new mailerMessage($campaign_id);
            $m->status(mailerMessageModel::STATUS_SENDING_ERROR);
            $campaign['status'] = mailerMessageModel::STATUS_SENDING_ERROR;
        }

        // Access control
        if (mailerHelper::campaignAccess($campaign) < 1) {
            throw new waException('Access denied.', 403);
        }

        $campaign['opened_count_available'] = mailerCampaignsArchiveAction::isOpenedCountAvailable($campaign);
        $campaign['has_unsubscribe_link'] = mailerCampaignsArchiveAction::hasUnsubscribeLink($campaign);

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        // Total number of recipients
        $lm = new mailerMessageLogModel();
        $total_recipients = $lm->countByField('message_id', $campaign_id);

        $message_start = $lm->getMessageStart($campaign_id);

        // Recipients stats for pie graph
        $stats = $this->getStats($campaign_id);

        // set $campaign['duration'] and $campaign['estimated_finish_datetime']
        $this->updateCampaignTimes($campaign, $params, $stats);

        // When campaign contains files, deletion confirmation must mention them, so check them out.
        $contains_files = false;
        $files = @scandir(wa()->getDataPath('files/'.$campaign_id, true, 'mailer'));
        if ($files && count($files) > 2) {
            $contains_files = true;
        }

        // CHeck for return-path errors
        $return_path_error = '';
        $check_return_path = false;
        $allow_return_path_edit = false;
        if ($campaign['return_path']) {
            if ($campaign['status'] == mailerMessageModel::STATUS_SENT) {
                $recently_updated = strtotime($campaign['finished_datetime']) > time() - mailerConfig::RETURN_PATH_CHECK_PERIOD;
            } else {
                $recently_updated = strtotime($campaign['send_datetime']) > time() - mailerConfig::RETURN_PATH_CHECK_PERIOD;
                $allow_return_path_edit = mailerHelper::isAdmin();
            }
            if ($recently_updated) {
                $rpm = new mailerReturnPathModel();
                $rp = $rpm->getByEmail($campaign['return_path']);
                $return_path_error = ifempty($rp['last_error'], '');
                $allow_return_path_edit = mailerHelper::isAdmin();
                $check_return_path = true;
            }
        }

        $this->view->assign('recipient_criterias', mailerHelper::getRecipients($campaign['id']));
        $this->view->assign('allow_return_path_edit', $allow_return_path_edit);
        $this->view->assign('check_return_path', $check_return_path);
        $this->view->assign('return_path_error', $return_path_error);
        $this->view->assign('total_recipients', $total_recipients);
        $this->view->assign('contains_files', $contains_files);
        $this->view->assign('campaign', $campaign);
        $this->view->assign('params', $params);
        $this->view->assign('stats', $stats);
        $this->view->assign('message_start_date', $message_start);
        $this->view->assign('message_written', trim($campaign['body']) && trim($campaign['subject']));

        mailerHelper::assignCampaignSidebarVars($this->view, $campaign, $params);
    }

    protected function getStats($campaign_id)
    {
        $mlm = new mailerMessageLogModel();
        $s = $mlm->getStatsByMessage(array($campaign_id));
        $s = ifempty($s[$campaign_id], array());
        $stats = array();
        $stats['recipients_num'] = 0;
        for($i = -4; $i < 6; $i++) {
            $s[$i] = ifset($s[$i], 0);
            $stats['recipients_num'] += $s[$i];
        }
        $stats['bounced_num']       = $s[-1] + $s[-2];
        $stats['exceptions_num']    = $s[-3] + $s[-4];
        $stats['not_sent_num']      = $s[0];
        $stats['unknown_num']       = $s[1];
        $stats['opened_num']        = $s[4] + $s[3] + $s[2];
        $stats['unsubscribed_num']  = $s[5];
        $stats['actualy_sent_num']  = $s[-2] + $s[-1] + $s[1] + $s[2] + $s[3] + $s[4] + $s[5];

        if ($stats['recipients_num'] - $stats['exceptions_num'] > 0) {
            $stats['percent_complete_precise'] = ($stats['actualy_sent_num'])*100.0 / ($stats['recipients_num'] - $stats['exceptions_num']);
        } else {
            $stats['percent_complete_precise'] = 100;
        }
        if ($stats['recipients_num'] > 0) {
            $stats['percent_complete_precise_all'] = ($stats['actualy_sent_num'])*100.0 / $stats['recipients_num'];
        } else {
            $stats['percent_complete_precise_all'] = 100;
        }

        if ($stats['actualy_sent_num'] > 0) {
            list($stats['exceptions_percent'], $stats['exceptions_percent_formatted'])     = self::formatInt($stats['exceptions_num'] * 100 / $stats['actualy_sent_num']);
            list($stats['bounced_percent'], $stats['bounced_percent_formatted'])           = self::formatInt($stats['bounced_num'] * 100 / $stats['actualy_sent_num']);
            list($stats['not_sent_percent'], $stats['not_sent_percent_formatted'])         = self::formatInt($stats['not_sent_num'] * 100 / $stats['actualy_sent_num']);
            list($stats['unknown_percent'], $stats['unknown_percent_formatted'])           = self::formatInt($stats['unknown_num'] * 100 / $stats['actualy_sent_num']);
            list($stats['opened_percent'], $stats['opened_percent_formatted'])             = self::formatInt($stats['opened_num'] * 100 / $stats['actualy_sent_num']);
            list($stats['unsubscribed_percent'], $stats['unsubscribed_percent_formatted']) = self::formatInt($stats['unsubscribed_num'] * 100 / $stats['actualy_sent_num']);
            list($stats['actualy_sent_percent'], $stats['actualy_sent_percent_formatted']) = self::formatInt($stats['actualy_sent_num'] * 100 / $stats['actualy_sent_num']);
        } else {
            $stats['exceptions_percent_formatted']      = $stats['exceptions_percent']      = 0;
            $stats['bounced_percent_formatted']         = $stats['bounced_percent']         = 0;
            $stats['not_sent_percent_formatted']        = $stats['not_sent_percent']        = 0;
            $stats['unknown_percent_formatted']         = $stats['unknown_percent']         = 100;
            $stats['opened_percent_formatted']          = $stats['opened_percent']          = 0;
            $stats['unsubscribed_percent_formatted']    = $stats['unsubscribed_percent']    = 0;
            $stats['actualy_sent_percent_formatted']    = $stats['actualy_sent_percent']    = 0;
        }

        return $stats;
    }

    function getRecipients($campaign)
    {
        // Campaign recipients
        $mrm = new mailerMessageRecipientsModel();
        $recipients = $mrm->getByField('message_id', $campaign['id'], 'id');

        $types = array();
        foreach($recipients as $row) {
            $row['group'] = ifempty($row['group'], '');
            if (empty($types[$row['group']])) {
                $types[$row['group']] = array(
                    'sort' => self::getRecipietnsGroupSort($row),
                    'name' => $row['group'],
                    'criteria' => array(),
                );
            }

            $value = $row['value'];
            if (strlen($value) && $value{0} == '/') {
                $row['href'] = wa()->getAppUrl('contacts').'#'.$value;
            } else if (wa_is_int($value)) {
                $row['href'] = '#/subscribers/';
            } else {
                $row['href'] = null;
            }
            $types[$row['group']]['criteria'][] = $row;
        }
        asort($types);
        return $types;
    }

    public static function getRecipietnsGroupSort($row)
    {
        $value = (string) ifset($row['value'], '');
        if (strlen($value) <= 0 || $value{0} == '/') {
            return 1;
        }
        if (wa_is_int($value)) {
            return 2;
        }
        if ($value{0} != '@') {
            return 3;
        }
        return 4;
    }

    protected function updateCampaignTimes(&$campaign, $params, $stats)
    {
        // Campaign duration
        $start_ts = ifempty($params['fake_send_timestamp'], strtotime($campaign['send_datetime']));
        if ($campaign['status'] == mailerMessageModel::STATUS_SENT) {
            $end_ts = strtotime($campaign['finished_datetime']);
        } else {
            $end_ts = time();
            if ($stats['actualy_sent_num'] >= 50 && $end_ts - $start_ts >= 20 && $stats['percent_complete_precise'] > 5) {
                $campaign['estimated_finish_datetime'] = round(time() + (100 - $stats['percent_complete_precise'])*($end_ts - $start_ts)/$stats['percent_complete_precise']);
            }
        }
        $campaign['duration'] = self::getAge($end_ts - $start_ts);
    }

    /**
     * Make human-readable time difference string from number of seconds.
     * Used in request list.
     */
    public static function getAge($fullseconds)
    {
        if($fullseconds < 60) {
            return _w('%d second', '%d seconds', $fullseconds);
        } elseif($fullseconds < 60 * 60) {
            return _w('%d minute', '%d minutes', round(($fullseconds) / 60));
        } else {
            $minutes = round($fullseconds / 60) % 60;
            $hours = floor($fullseconds / (60*60));

            $result = _w('%d hour', '%d hours', $hours);
            if ($minutes) {
                $result .= ' '._w('%d minute', '%d minutes', $minutes);
            }

            return $result;
        }
    }


    public static function formatInt($f)
    {
        if ($f == 0) {
            return array(0, 0);
        } else if ($f <= 0.1) {
            return array(0, '≈0');
        } else if ($f < 1) {
            return array(round($f), '<1');
        } else {
            return array(round($f), round($f));
        }
    }
}

