<?php

/**
 * List of recipients for campaign that is sent or being sent.
 */
class mailerCampaignsRecipientsReportAction extends mailerCampaignsReportAction
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

        // Access control
        if (mailerHelper::campaignAccess($campaign) < 1) {
            throw new waException('Access denied.', 403);
        }

        $campaign['opened_count_available'] = mailerCampaignsArchiveAction::isOpenedCountAvailable($campaign);
        $campaign['has_unsubscribe_link'] = mailerCampaignsArchiveAction::hasUnsubscribeLink($campaign);

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        // Campaign recipients
        if ($campaign['status'] <= 0) {
            throw new waException('Recipients report is unavailable for drafts.');
        }

        // List of recipients
        $recipients = $this->getRecipientsSent($campaign_id);

        // Recipients stats for pie graph
        $stats = $this->getStats($campaign_id);

        $this->view->assign('recipients', $recipients);
        $this->view->assign('campaign', $campaign);
        $this->view->assign('params', $params);
        $this->view->assign('stats', $stats);
    }

    protected function getRecipientsSent($campaign_id)
    {
        $start = waRequest::request('start', 0, 'int');
        $startinterval = waRequest::request('startinterval', 0, 'int');
        $endinterval = waRequest::request('endinterval', 0, 'int');
        $quantum = waRequest::request('quantum', 60, 'int');
        $limit = 50;
        $search = waRequest::request('search');
        $error_class = waRequest::request('error_class');
        $status = array();
        foreach(explode(',', waRequest::request('status', '')) as $s) {
            if (wa_is_int($s)) {
                $status[] = $s;
            }
        }

        $lm = new mailerMessageLogModel();

        // Hacky way to add type classification to error tab
        $error_classes = null;
        if (!$search && $start == 0 && $status && $status[0] < 0 && count($status) != 7) {
            $type = $status[0] > mailerMessageLogModel::STATUS_PREVIOUSLY_UNSUBSCRIBED ? 'bounces' : 'exceptions';
            $stata = $type == 'bounces' ? '-1,-2' : '-3,-4';

            $total_count = 0;
            $error_classes = array();
            $datetime_sql = '';
            if ($startinterval && $endinterval && $quantum) {
                $startinterval = floor($startinterval / (60 * $quantum)) * (60 * $quantum);
                $startinterval = waDateTime::date('Y-m-d H:i:s', $startinterval);
                $endinterval = waDateTime::date('Y-m-d H:i:s', $endinterval);

                $datetime_sql = " AND datetime BETWEEN s:startinterval AND s:endinterval ";
//            $datetime_sql = " AND CEIL ( UNIX_TIMESTAMP(datetime) / (60*i:quantum) ) * (60*i:quantum) BETWEEN i:startinterval AND i:endinterval ";
//            $datetime_sql = " AND datetime BETWEEN i:startinterval AND i:endinterval ";
            }
            $sql = "SELECT
                      status,
                      error_class,
                      COUNT(*) AS `count`
                  FROM mailer_message_log
                  WHERE
                      message_id=:mid AND
                      status IN ({$stata})
                      {$datetime_sql}
                  GROUP BY status, error_class
                  ORDER BY `count` DESC";
            foreach($lm->query(
                $sql,
                array(
                    'mid' => $campaign_id,
                    'stata' => $stata,
                    'quantum' => $quantum,
                    'startinterval' => min($startinterval, $endinterval),
                    'endinterval' => max($startinterval, $endinterval)
                )
            ) as $row) {
                $row['name'] = self::getErrorClass($row['status'], $row['error_class']);
                $row['param'] = 'status='.$row['status'].'&error_class='.urlencode(ifempty($row['error_class'], 'null'));
                $total_count += $row['count'];
                $error_classes[] = $row;
            }
            foreach($error_classes as &$row) {
                list($tmp, $row['percent']) = mailerCampaignsReportAction::formatInt($row['count'] * 100 / $total_count);
            }
            unset($row, $tmp);

            array_unshift($error_classes, array(
                'status' => $stata,
                'error_class' => null,
                'count' => $total_count,
                'name' => $type == 'bounces' ? _w('All bounces') : _w('All exceptions'),
                'param' => 'status='.$stata,
                'percent' => null,
            ));
        }

        // List of recipients
        $log = array();
        $total_rows = true;
        $ordername = !count($status);
        foreach($lm->getByMessage($campaign_id, $start, $limit, $status, $search, $error_class, $total_rows, $startinterval, $endinterval, $quantum, $ordername) as $l) {
            // try to get name from contacts or mailer_message_log table
            $l['name'] = empty($l['cname']) ? $l['name'] : $l['cname'];
            $l['email'] = !empty($l['name']) ? '<'.$l['email'].'>' : $l['email'];
            $l['datetime'] = waDateTime::format('fulldatetime', $l['datetime']);
            $l['status_text'] = '';
            switch($l['status']) {
                case -4:
                case -3:
                case -2:
                case -1:
                    $error = self::getErrorClass($l['status'], $l['error_class']);
                    $css_class = $l['status'] == mailerMessageLogModel::STATUS_PREVIOUSLY_UNSUBSCRIBED ? 'earlier-unsubscribed' : 'error';
                    if ($l['error']) {
                        $css_class .= ' show-full-error-text';
                    }
                    $l['status_text'] = '<span class="'.$css_class.'">'.htmlspecialchars($error).'</span>';
                    break;
                case 0:
                    $l['status_text'] = '<span class="awaits-sending">'._w('Not sent yet').'</span>';
                    break;
                case 1:
                case 2:
                    $l['status_text'] = '<span class="unknown">'._w('Unknown').'</span>';
                    break;
                case 3:
                case 4:
                    $l['status_text'] = '<span class="opened">'._w('Opened').'</span>';
                    break;
                case 5:
                    $l['status_text'] = '<span class="unsubscribed">'._w('Unsubscribed ').'</span>';
                    break;
            }
            $log[$l['id']] = $l;
        }
        unset($l);

        $parameters = array(
            'start='.($start+$limit),
        );
        if ($status) {
            $parameters[] = 'status='.implode(',', $status);
        }
        if ($search) {
            $parameters[] = 'search='.urlencode($search);
        }
        if ($error_class) {
            $parameters[] = 'error_class='.urlencode($error_class);
        }
        $period = false;
        if ($startinterval && $endinterval) {
            $parameters[] = 'startinterval='.$startinterval;
            $parameters[] = 'endinterval='.$endinterval;
            $period = array(
                waDateTime::format('humandatetime', min($startinterval, $endinterval)),
                waDateTime::format('humandatetime', max($startinterval, $endinterval))
            );
        }
        $parameters = implode('&', $parameters);

        $this->view->assign('start', $start);
        $this->view->assign('has_more', $total_rows > $start + $limit);
        $this->view->assign('parameters', $parameters);
        $this->view->assign('total_rows', $total_rows);
        $this->view->assign('error_classes', $error_classes);
        $this->view->assign('search', $search);
        $this->view->assign('statuses', $status);
        $this->view->assign('period', $period);
        $this->view->assign('startinterval', $startinterval);
        $this->view->assign('endinterval', $endinterval);
        return $log;
    }

    public static function getErrorClass($status, $error_class)
    {
        if ($error_class) {
            return $error_class;
        }

        switch ($status) {
            case -4:
                return _w('Delivering error in past campaigns');
            case -3:
                return _w('Earlier unsubscribed');
            case -2:
                return _w('Unknown error');
            case -1:
                return _w('Bounced by sender mail server');
        }

        return '';
    }
}

