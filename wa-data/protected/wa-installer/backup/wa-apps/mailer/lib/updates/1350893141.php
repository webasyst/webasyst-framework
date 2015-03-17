<?php

$mod = new waModel();

$sql = <<<EOF
UPDATE mailer_message_log AS l
  JOIN wa_contact_emails AS e
    ON l.email=e.email
SET e.status='unknown'
WHERE l.error='Expected response code 250 but got code "", with message ""'
EOF;

try {
    $mod->exec($sql);
} catch (waDbException $e) {
}

