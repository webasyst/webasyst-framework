<?php

class mailerContactsDeleteHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $contacts = $params;

        $mlm = new mailerMessageLogModel();
        $mlm->updateByField('contact_id', $contacts, array('contact_id' => 0));

        $sql = "UPDATE IGNORE mailer_subscriber SET contact_id=0 WHERE contact_id IN (:ids)";
        $mlm->query($sql, array('ids' => $contacts));

        // Some updates may have failed due to unique key constrain. Delete them.
        $sql = "DELETE FROM mailer_subscriber WHERE contact_id IN (:ids)";
        $mlm->query($sql, array('ids' => $contacts));
    }
}
