<?php

/**
 * This table stores recipient emails for draft campaigns.
 * INSERT and DELETE operations must be fast, so there are no indices in this table.
 * Should be fine as long as people do not create a lot of drafts at the same time
 * with a lot of recipients for each one.
 */
class mailerDraftRecipientsModel extends waModel
{
    protected $table = "mailer_draft_recipients";

    public function countUniqueByMessage($message_id)
    {
        $sql = "SELECT COUNT(DISTINCT email) FROM {$this->table} WHERE message_id=?";
        $result = $this->query($sql, $message_id)->fetchField();

        // !!! This is a workarond for unfortunate MySQL bug in COUNT(DISTINCT).
        // See http://bugs.mysql.com/bug.php?id=30402
        if (!$result) {
            $sql = "SELECT COUNT(*) FROM (SELECT DISTINCT email FROM {$this->table} WHERE message_id=?) AS t";
            $result = $this->query($sql, $message_id)->fetchField();
        }

        return $result;
    }

    public function moveToMessageLog($message_id)
    {
        $sql = "DELETE FROM mailer_message_log WHERE message_id=?";
        $this->exec($sql, $message_id);

        $sql = "INSERT IGNORE INTO mailer_message_log (message_id, contact_id, name, email)
                SELECT message_id, contact_id, name, email
                FROM mailer_draft_recipients
                WHERE message_id=?";
                //GROUP BY email"; // 'GROUP BY' makes it faster when there are many duplicates (like 40%+), but slower otherwise
        $this->exec($sql, $message_id);

        $this->deleteByField('message_id', $message_id);
    }

    public function getStatsByMessage($message_id)
    {
        $result = array(
            'unavailable' => 0,
            'unsubscribed' => 0,
        );

        $sql = "SELECT COUNT(DISTINCT dr.email)
                FROM {$this->table} AS dr
                    JOIN wa_contact_emails AS e
                        ON dr.email = e.email
                WHERE dr.message_id=?
                    AND e.status='unavailable'";
        $result['unavailable'] = $this->query($sql, $message_id)->fetchField();

        $sql = "SELECT COUNT(DISTINCT dr.email)
                FROM {$this->table} AS dr
                    JOIN mailer_unsubscriber AS u
                        ON dr.email = u.email
                WHERE dr.message_id=?";
        $result['unsubscribed'] = $this->query($sql, $message_id)->fetchField();

        return $result;
    }
}

