<?php

$mod = new waModel();

try {
    $mod->exec('SELECT email FROM mailer_subscriber LIMIT 0');
} catch (waDbException $e) {
    $mod->exec('ALTER TABLE mailer_subscriber ADD email VARCHAR(255) NULL DEFAULT NULL');
    $mod->exec('UPDATE mailer_subscriber AS s
                    JOIN wa_contact_emails AS ce
                        ON s.contact_id=ce.contact_id
                SET s.email=ce.email
                WHERE ce.sort=0');
    $mod->exec('ALTER TABLE mailer_subscriber CHANGE email email VARCHAR(255) NOT NULL');
};

