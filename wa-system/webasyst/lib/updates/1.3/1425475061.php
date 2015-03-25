<?php

// wa_contact.password VARCHAR(128)
try {
    $model = new waModel();
    $model->exec("ALTER TABLE wa_contact CHANGE password password VARCHAR(128) NOT NULL DEFAULT ''");
} catch (waDbException $e) {
}

// change unconfirmed emails to unknown
$model->exec("UPDATE wa_contact_emails e JOIN wa_contact c ON e.contact_id = c.id AND c.password != ''
              SET e.status = 'unknown' WHERE e.status = 'unconfirmed'");
