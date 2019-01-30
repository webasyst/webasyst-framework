<?php

class waContactEventsModel extends waModel
{
    protected $table = 'wa_contact_events';

    public function insertEvent(array $data)
    {
        // check UID
        if (empty($data['uid'])) {
            // try to get any domain
            if (!($domain = wa()->getRouting()->getDomain())) {
                if ($domains = wa()->getRouting()->getDomains()) {
                    $domain = $domains[0];
                }
            }
            if (!$domain) {
                $domain = mt_rand(100000, 999999);
            }
            // generate UID
            $data['uid'] = time() . '-' . mt_rand(100000, 999999) . '@' . $domain;
        }
        if (empty($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        if (empty($data['contact_id'])) {
            $data['contact_id'] = wa()->getUser()->getId();
        }
        return $this->insert($data);
    }

    public function getEventsByPeriod($start, $end, $calendar_id = null, $contact_id = null)
    {
        $condition = '';
        if ($calendar_id) {
            $condition .= 'AND calendar_id='.intval($calendar_id);
        }
        if ($contact_id) {
            $condition .= ' AND contact_id='.intval($contact_id);
        }
        // $ccm = new waContactCalendarsModel();
        $sql = "SELECT * FROM {$this->getTableName()}
                WHERE end >= s:start AND start <= s:end {$condition}
                ORDER BY is_allday DESC, start ASC";
        return $this->query($sql, array('start' => $start, 'end' => $end))->fetchAll('id');
    }

    public function getEventByContact($contact_id, $limit = null)
    {
        if (is_array($contact_id)) {
            $contact_condition = "e.contact_id IN('".join("','", $this->escape($contact_id))."')";
        } else {
            $contact_condition = "e.contact_id = ".intval($contact_id);
        }
        $now = waDateTime::format('Y-m-d H:i:s');
        $ccm = new waContactCalendarsModel();
        $_limit = $limit ? ('LIMIT '.intval($limit)) : '';
        $sql = "SELECT e.*, c.name calendar_name, c.bg_color, c.font_color FROM {$this->getTableName()} e
            INNER JOIN {$ccm->getTableName()} c ON c.id=e.calendar_id
            WHERE $contact_condition AND  is_status=1 AND
            ((is_allday = 0 AND e.start <= '$now' AND e.end >= '$now')
            OR (is_allday = 1 AND DATE(e.start) <= DATE('$now') AND DATE(e.end) >= DATE('$now')))
            ORDER BY is_allday DESC, start ASC $_limit";
        if ($limit == 1) {
            return $this->query($sql)->fetchAssoc();
        } else {
            return $this->query($sql)->fetchAll('id');
        }
    }

    public function moveEventDates($id, $date_diff_days)
    {
        $date_diff_days = (int) $date_diff_days;
        if (!$date_diff_days) {
            return;
        }

        $sql = "UPDATE {$this->table}
                SET start=DATE_ADD(start, INTERVAL ? DAY),
                    end=DATE_ADD(end, INTERVAL ? DAY),
                    sequence=sequence + 1,
                    update_datetime=?
                WHERE id IN (?)";
        $this->exec($sql, $date_diff_days, $date_diff_days, date('Y-m-d H:i:s'), $id);
    }
}
