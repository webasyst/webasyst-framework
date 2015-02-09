<?php
$mod = new waModel();

// try to alter mailer_form
try {
    $mod->exec("ALTER TABLE mailer_form DROP confirmation");
} catch (waDbException $ex) {}
try {
    $mod->exec("ALTER TABLE mailer_form DROP confirmation_sender_id");
} catch (waDbException $ex) {}
try {
    $mod->exec("ALTER TABLE mailer_form DROP confirmation_subject");
} catch (waDbException $ex) {}
try {
    $mod->exec("ALTER TABLE mailer_form DROP confirmation_body");
} catch (waDbException $ex) {}
try {
    $mod->exec("ALTER TABLE mailer_form DROP captcha");
} catch (waDbException $ex) {}

$mod->exec("CREATE TABLE IF NOT EXISTS mailer_form_subscribe_lists (
                form_id INT(11) NOT NULL,
                list_id INT(11) NOT NULL,
                PRIMARY KEY (form_id, list_id),
                INDEX list_id_idx (list_id ASC))
            ENGINE = MyISAM
            DEFAULT CHARACTER SET = utf8");

$mod->exec("CREATE TABLE IF NOT EXISTS mailer_form_params (
                form_id INT(11) NOT NULL,
                name VARCHAR(255) NOT NULL,
                value TEXT NOT NULL,
                PRIMARY KEY (form_id))
            ENGINE = MyISAM
            DEFAULT CHARACTER SET = utf8");