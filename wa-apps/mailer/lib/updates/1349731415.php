<?php

$mod = new waModel();

try {
    $mod->exec('SELECT message_id FROM mailer_unsubscriber LIMIT 0');
} catch (waDbException $e) {
    $mod->exec('ALTER TABLE mailer_unsubscriber ADD message_id INT UNSIGNED NULL DEFAULT NULL AFTER list_id');
}

try {
    $sql = "UPDATE wa_contact_emails AS e
                JOIN mailer_message_log AS l
                    ON e.email=l.email
            SET e.status='unavailable'
            WHERE l.status IN (-1, -2) 
                AND e.status<>'unavailable'";
    $mod->exec($sql);
} catch (waDbException $e) {
}

