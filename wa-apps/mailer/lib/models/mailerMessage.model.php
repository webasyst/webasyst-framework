<?php

/**
 * Storage for basic campaign data.
 * Entity is called 'message' for legacy reasons.
 */
class mailerMessageModel extends waModel
{
    const STATUS_DRAFT = 0;
    const STATUS_CONTACTS = 1; // preparing recipients before sending
    const STATUS_SENDING = 2;
    const STATUS_SENDING_PAUSED = 3;
    const STATUS_SENDING_ERROR = 4;
    const STATUS_PENDING = 5;
    const STATUS_SENT = 9;

    protected $table = "mailer_message";

    public function delete($id)
    {
        $model = new mailerMessageLogModel();
        $model->deleteByField('message_id', $id);

        $model = new mailerMessageRecipientsModel();
        $model->deleteByField('message_id', $id);

        $model = new mailerMessageParamsModel();
        $model->deleteByField('message_id', $id);

        $model = new mailerDraftRecipientsModel();
        $model->deleteByField('message_id', $id);

        $this->deleteById($id);
    }

    public function countSent()
    {
        // Filter messages by access rights
        $access_sql = '';
        if (!mailerHelper::isInspector()) {
            $access_sql = 'AND m.create_contact_id='.wa()->getUser()->getId();
        }

        $sql = "SELECT SUM(IF(status=i:sent,0,1)) AS sending_count, count(*) AS total_sent
                FROM mailer_message AS m
                WHERE status IN (i:sending,i:sent)
                    AND is_template=0
                    {$access_sql}";
        $m = new waModel();
        return $m->query($sql, array(
            'sending' => mailerCampaignsArchiveAction::getArchiveStates(),
            'sent' => self::STATUS_SENT,
        ))->fetchAssoc();
    }

    public function countDraft()
    {
        // Filter messages by access rights
        $access_sql = '';
        if (!mailerHelper::isInspector()) {
            $access_sql = 'AND m.create_contact_id='.wa()->getUser()->getId();
        }

        $sql = "SELECT SUM(IF(status=i:scheduled,1,0)) AS scheduled_count, count(*) AS draft_count
                FROM mailer_message AS m
                WHERE status IN (i:draft,i:scheduled)
                    AND is_template=0
                    {$access_sql}";
        $m = new waModel();
        return $m->query($sql, array(
            'scheduled' => self::STATUS_PENDING,
            'draft' => self::STATUS_DRAFT,
        ))->fetchAssoc();
    }

    public function getListView($escaped_search, $start, $limit, $order)
    {
        // SQL search condition
        $search_sql = '';
        if ($escaped_search) {
            $search_sql = " AND CONCAT(m.subject, ' ', m.body) LIKE '%".implode('%', $escaped_search)."%' ";
        }

        // Limit
        $limit_sql = '';
        if ($limit) {
            $limit = (int) $limit;
            if ($start) {
                $limit_sql = "LIMIT {$start}, {$limit}";
            } else {
                $limit_sql = "LIMIT {$limit}";
            }
        }

        // Order
        $order_sql = '';
        if ($order) {
            $possible_order = array(
                '!id' => 'm.id DESC',
                '!sent' => 'IF(m.status = '.mailerMessageModel::STATUS_SENT.', 0, 1) DESC, m.send_datetime DESC',
                'id' => 'm.id',
                'sent' => 'IF(m.status = '.mailerMessageModel::STATUS_SENT.', 0, 1), m.send_datetime',
            );
            if (!$order || empty($possible_order[$order])) {
                $order = key($possible_order);
            }
            $order_sql = "ORDER BY ".$possible_order[$order];
        }

        // Filter messages by access rights
        $access_sql = '';
        if (!mailerHelper::isInspector()) {
            $access_sql = 'AND m.create_contact_id='.wa()->getUser()->getId();
        }

        // List of messages
        $sql = "SELECT SQL_CALC_FOUND_ROWS m.*
                FROM mailer_message AS m
                WHERE is_template=0
                    AND status IN (".implode(',', mailerCampaignsArchiveAction::getArchiveStates()).")
                    {$search_sql}
                    {$access_sql}
                {$order_sql}
                {$limit_sql}";

        $messages = $this->query($sql)->fetchAll('id');
        $total_rows = $this->query('SELECT FOUND_ROWS()')->fetchField();
        return array($messages, $total_rows);
    }

    public function getMessageForSend($shedule = false)
    {
        $condition = !$shedule ? " AND send_datetime < '".date('Y-m-d H:i:s')."'" : '';
        return $this->query("SELECT * FROM ".$this->table."
                            WHERE status = ".mailerMessageModel::STATUS_SENDING." OR
                            (status = ".mailerMessageModel::STATUS_PENDING.$condition.")")->fetchAll('id');
    }
}
