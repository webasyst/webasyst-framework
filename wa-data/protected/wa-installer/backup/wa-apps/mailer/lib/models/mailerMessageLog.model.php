<?php

/**
 * Storage for individual campaign recipients and delivery status.
 */
class mailerMessageLogModel extends waModel
{
    const STATUS_PREVIOUSLY_UNAVAILABLE     = -4;
    const STATUS_PREVIOUSLY_UNSUBSCRIBED    = -3;
    const STATUS_NOT_DELIVERED              = -2;
    const STATUS_SENDING_ERROR              = -1;
    const STATUS_AWAITING                   = 0;
    const STATUS_SENT                       = 1;
    const STATUS_DELIVERED                  = 2;
    const STATUS_VIEWED                     = 3;
    const STATUS_CLICKED                    = 4;
    const STATUS_UNSUBSCRIBED               = 5;

    protected $table = "mailer_message_log";

    public function setStatus($id, $status, $error = '', $error_class='', $error_fatal=true)
    {
        if ($error_fatal && ($status == -1 || $status == -2)) {
            // Mark email as unavailable in wa_contact_emails
            $sql = "UPDATE wa_contact_emails AS e
                        JOIN mailer_message_log AS l
                            ON e.email=l.email
                    SET e.status='unavailable'
                    WHERE l.id=i:log_id";
            $this->exec($sql, array('log_id' => $id));
        }

        // Update status in log
        $data = array('status' => $status, 'datetime' => date("Y-m-d H:i:s"));
        if ($error) {
            $data['error'] = trim($error);
        }
        if ($error_class) {
            $data['error_class'] = $error_class;
        }
        return $this->updateById($id, $data);
    }

    public function getStatsByMessage($message_ids) {
        if (!is_array($message_ids)) {
            $message_ids = array($message_ids);
        }
        if (empty($message_ids)) {
            return array();
        }
        $sql = "SELECT message_id, status, count(*) AS num
                FROM {$this->table}
                WHERE message_id IN (i:messages)
                GROUP BY message_id, status";
        $stats = array();
        foreach($this->query($sql, array('messages' => $message_ids)) as $row) {
            $stats[$row['message_id']][$row['status']] = $row['num'];
        }
        return $stats;
    }

    public function getByMessage($message_id, $start=null, $limit=null, $status=null, $search=null, $error_class=null, &$total_rows=null, $startinterval = 0, $endinterval = 0, $quantum = 0, $ordername = false)
    {
        if ($limit) {
            $start = (int) $start;
            $limit = (int) $limit;
            $limit = "LIMIT {$start}, {$limit}";
        } else {
            $limit = "";
        }

        $status_sql = '';
        if ($status) {
            if (is_array($status)) {
                $status_sql = ' AND l.status IN (:status) ';
            } else {
                $status_sql = ' AND l.status=:status ';
            }
        }

        $search_sql = '';
        if ($search) {
            $search_sql = " AND CONCAT(c.name, ' ', l.email) LIKE :search ";
        }

        $error_class_sql = '';
        if ($error_class === 'null') {
            $error_class_sql = ' AND l.error_class IS NULL';
        } else if ($error_class) {
            $error_class_sql = ' AND l.error_class=:error_class';
        }


        $datetime_sql = '';
        if ($startinterval && $endinterval && $quantum) {
            $startinterval = floor($startinterval / (60 * $quantum)) * (60 * $quantum);
            $startinterval = waDateTime::date('Y-m-d H:i:s', $startinterval);
            $endinterval = waDateTime::date('Y-m-d H:i:s', $endinterval);

            $datetime_sql = " AND l.datetime BETWEEN s:startinterval AND s:endinterval ";
//            $datetime_sql = " AND CEIL ( UNIX_TIMESTAMP(l.datetime) / (60*i:quantum) ) * (60*i:quantum) BETWEEN i:startinterval AND i:endinterval ";
//            $datetime_sql = " AND datetime BETWEEN i:startinterval AND i:endinterval ";
        }

        $ordername_sql = ' ORDER BY l.datetime, c.name, l.id';
        if ($ordername) {
            $ordername_sql = " ORDER BY c.name, l.id";
        }

        $sql = "SELECT ".($total_rows !== null ? 'SQL_CALC_FOUND_ROWS' : '')." l.*, c.name as cname
                FROM {$this->table} l
                LEFT JOIN wa_contact c ON c.id = l.contact_id
                WHERE l.message_id=:m_id
                    {$status_sql}
                    {$search_sql}
                    {$error_class_sql}
                    {$datetime_sql}
                {$ordername_sql}
                {$limit}";
        $result = $this->query($sql, array(
            'm_id' => $message_id,
            'status' => $status,
            'search' => '%'.$search.'%',
            'error_class' => $error_class,
            'startinterval' => min($startinterval, $endinterval),
            'endinterval' => max($startinterval, $endinterval),
            'quantum' => $quantum
        ));
        if ($total_rows !== null) {
            $total_rows = $this->query('SELECT FOUND_ROWS()')->fetchField();
        }
        return $result;
    }

    public function countByMessage($id)
    {
        $sql = "SELECT COUNT(*) FROM ".$this->table." WHERE message_id = i:id";
        return $this->query($sql, array('id' => $id))->fetchField();
    }

    public function countSentToday()
    {
        $result = 0;

        // Sent today
        $sql = "SELECT COUNT(*)
                FROM {$this->table} AS ml
                    JOIN mailer_message AS m
                        ON ml.message_id=m.id
                WHERE ml.status<>0
                    AND (m.finished_datetime IS NOT NULL OR m.send_datetime IS NOT NULL)
                    AND IFNULL(m.finished_datetime, m.send_datetime) > :today";
        $result += $this->query($sql, array('today' => date('Y-m-d').' 00:00:00'))->fetchField();

        // Not sent yet
        $result += $this->countByField('status', self::STATUS_AWAITING);

        return $result;
    }

    private function getCampaignTimeStuff($timestamp_start, $timestamp_end, $manual = false, $quantum = false)
    {
        $campaign_time_stuff = array();

        // time data
        $campaign_time_stuff['min'] = $timestamp_start;
        $campaign_time_stuff['max'] = $timestamp_end;
        $campaign_time_stuff['length'] = $campaign_time_stuff['max'] - $campaign_time_stuff['min'];
        $campaign_time_stuff['days'] = floor($campaign_time_stuff['length'] / (60*60*24));

//        $time_now = $manual ? $campaign_time_stuff['max'] : time();
        $from_start = time() - $campaign_time_stuff['min'];
        $campaign_length = $campaign_time_stuff['max'] - $campaign_time_stuff['min'];
        // if start was < 24h ago - we'll plot till now()
        if ($from_start < 24*60*60) {
            $campaign_time_stuff['quantum'] = $quantum ? $quantum : 1;
            $campaign_time_stuff['tickinterval'] = 1;
            if ($from_start < 10*60) { // unless 10 minutes have passed - plot graph till now() + 10 min
                $campaign_time_stuff['max'] = time() + 10 * 60;
            } else {
                $campaign_time_stuff['max'] = time();
            }
        }
        // else we should plot till last event, but not less then 24h
        else {
            if ($campaign_length < 24*60*60 && !$manual) {
                $campaign_time_stuff['max'] = $campaign_time_stuff['min'] + 24*60*60;
            }
            $campaign_time_stuff['quantum'] = $quantum ? $quantum : 60;
            $campaign_time_stuff['tickinterval'] = 24 * 60;
        }

        // round to quantum
        $campaign_time_stuff['min'] = mailerHelper::quant($campaign_time_stuff['min'], $campaign_time_stuff['quantum']);
        $campaign_time_stuff['max'] = mailerHelper::quant($campaign_time_stuff['max'], $campaign_time_stuff['quantum']);;

        return $campaign_time_stuff;
    }

    public function getMessageStart($message_id) {
        $campaign_start = $this->query(
            "SELECT
              min(datetime) start
            FROM {$this->table}
            WHERE
              message_id = :m_id AND
              datetime IS NOT NULL",
            array(
                'm_id' => $message_id
            )
        )->fetchAssoc();
        return strtotime($campaign_start['start']);
        /*
                $campaign_start = $this->query(
                    "SELECT
                      UNIX_TIMESTAMP(min(datetime)) start
                    FROM {$this->table}
                    WHERE
                      message_id = :m_id AND
                      datetime IS NOT NULL",
                    array(
                        'm_id' => $message_id
                    )
                )->fetchAssoc();
                return $campaign_start['start'];
        */
    }


    public function getGraphData($message_id, $status, $timestamp_start = 0, $timestamp_end = 0, $quantum = false)
    {
        if (!is_array($status)) {
            $status = array($status);
        }
        $result_with_zero = array();
        $statuses_to_select = array(
            self::STATUS_NOT_DELIVERED,
            self::STATUS_SENDING_ERROR,
            self::STATUS_SENDING_ERROR,
            self::STATUS_VIEWED,
            self::STATUS_UNSUBSCRIBED,
        );
        $date_interval = '';

        $sql_get_campaign_get_time_bounds = "SELECT
              min(datetime) min, max(datetime) max
            FROM {$this->table}
            WHERE
              message_id = :m_id AND
              datetime IS NOT NULL";
        /*
                $sql_get_campaign_get_time_bounds = "SELECT
                      UNIX_TIMESTAMP(min(datetime)) min,
                      UNIX_TIMESTAMP(max(datetime)) max
                    FROM {$this->table}
                    WHERE
                      message_id = :m_id AND
                      datetime IS NOT NULL";
        */
        $sql_include_statuses = " AND status IN ( :statuses )";

        $campaign_all_time_stuff = $this->query(
            $sql_get_campaign_get_time_bounds.
            $sql_include_statuses,
            array(
                'm_id' => $message_id,
                'statuses' => $statuses_to_select
            )
        )->fetchAssoc();

        if ($campaign_all_time_stuff['min'] == null && $campaign_all_time_stuff['max'] == null) {
            $campaign_all_time_stuff = $this->query(
                $sql_get_campaign_get_time_bounds,
                array(
                    'm_id' => $message_id,
                )
            )->fetchAssoc();
        }

        // if we haven't date interval - get all campaign data
        $manual = false;
        if (!$timestamp_start && !$timestamp_end) {
            $datetime_start = $campaign_all_time_stuff['min'];
            $datetime_end = $campaign_all_time_stuff['max'];
        } else {
            $manual = true;
            $datetime_start = waDateTime::date('Y-m-d H:i:s', max(strtotime($campaign_all_time_stuff['min']), $timestamp_start));
            $datetime_end = waDateTime::date('Y-m-d H:i:s', min(strtotime($campaign_all_time_stuff['max']), $timestamp_end));
            $date_interval = " AND datetime BETWEEN '".$datetime_start."' AND '".$datetime_end."'";
//            $date_interval = " AND UNIX_TIMESTAMP(datetime) BETWEEN ".$timestamp_start." AND ".$timestamp_end;
        }
        $timestamp_start = strtotime($datetime_start);
        $timestamp_end = strtotime($datetime_end);

        $campaign_dates = $this->getCampaignTimeStuff($timestamp_start, $timestamp_end, $manual, $quantum);

        $sent = $this->query(
            "SELECT datetime FROM {$this->table} WHERE
                message_id = :m_id AND
                status IN ( :status ) AND
                datetime IS NOT NULL
                $date_interval
            ORDER BY datetime ASC",
            array(
                'm_id'    => $message_id,
                'status'  => $status
            ))->fetchAll();
        /*
                $graphs = $this->query(
                    "SELECT
                      datetime,
                      CEIL ( UNIX_TIMESTAMP(datetime) / (60*i:quantum) ) * (60*i:quantum) AS rounded_time,
                      COUNT(status) AS recipients,
                      status
                    FROM {$this->table}
                    WHERE
                      message_id = :m_id AND
                      status IN ( :status ) AND
                      datetime IS NOT NULL
                      $date_interval
                    GROUP BY rounded_time
                    ORDER BY rounded_time ASC",
                    array(
                        'm_id' => $message_id,
                        'quantum' => $campaign_dates['quantum'],
                        'status' => $status
                    ))->fetchAll();
        */
        if ($sent) {
            for ($cur_unix_date = $campaign_dates['min']; $cur_unix_date <= $campaign_dates['max']; $cur_unix_date += (60*$campaign_dates['quantum'])) {
                $new_date = waDateTime::date('Y-m-d H:i:s', $cur_unix_date, wa()->getUser()->getTimezone());
                $zero = array($new_date, 0);
                $result_with_zero[$new_date] = $zero;
            }
            // filling zero array with values from db
            foreach($sent as $s) {
                $rounded_time = ceil(strtotime($s['datetime']) / (60 * $campaign_dates['quantum'])) * (60 * $campaign_dates['quantum']);
                $rounded_date = waDateTime::date('Y-m-d H:i:s', $rounded_time, wa()->getUser()->getTimezone());
//                $rounded_time = waDateTime::date('Y-m-d H:i:s', $value['rounded_time'], wa()->getUser()->getTimezone());
                $result_with_zero[$rounded_date] = array(
                    $rounded_date,
                    isset($result_with_zero[$rounded_date][1]) ? $result_with_zero[$rounded_date][1] + 1 : 1
                );
            }
            // reset date keys
            $result_with_zero = array_values($result_with_zero);
        }
        else {
            $result_with_zero = array();
        }

        // adjust min and max for better graph view
        $mindate =mailerHelper::quant($campaign_dates['min'] - ($campaign_dates['quantum'] * 60), $campaign_dates['quantum']);
//        $maxdate = $mindate + $campaign_dates['length'] + $campaign_dates['quantum'] * 60;
        $maxdate = $campaign_dates['max'];

        unset($sent);

        return array(
            'data' => $result_with_zero,
            'mindate' => $mindate,
            'maxdate' => $maxdate,
            'ticks' => array(),//$ticks,
            'tickinterval' => $campaign_dates['tickinterval'],
            'days' => $campaign_dates['days'],
            'quantum' => $campaign_dates['quantum'],
            'zeroline' => $this->getMessageStart($message_id)
        );
    }

    public function moveToDraftRecipients($message_id)
    {
        $sql = "DELETE FROM mailer_draft_recipients WHERE message_id=?";
        $this->exec($sql, $message_id);

        $sql = "INSERT IGNORE INTO mailer_draft_recipients (message_id, contact_id, name, email)
                SELECT message_id, contact_id, name, email
                FROM ".$this->table."
                WHERE message_id=?";
        //GROUP BY email"; // 'GROUP BY' makes it faster when there are many duplicates (like 40%+), but slower otherwise
        $this->exec($sql, $message_id);

        $this->deleteByField('message_id', $message_id);
    }

}
