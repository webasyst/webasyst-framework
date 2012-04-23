<?php

$model = new waModel();

try {
    $model->query('SELECT status FROM wa_contact_emails WHERE 0');
} catch (waDbException $e) {
    $model->exec("ALTER TABLE  `wa_contact_emails` 
                  ADD  `status`
                  ENUM(  'unknown',  'confirmed',  'unconfirmed',  'unavailable' ) NOT NULL DEFAULT  'unknown'");
}