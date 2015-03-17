<?php
$mod = new waModel();

// try to alter mailer_form
try {
    $mod->exec("ALTER TABLE mailer_form DROP list_id");
} catch (waDbException $ex) {}
try {
    $mod->exec("ALTER TABLE mailer_form_params DROP PRIMARY KEY");
} catch (waDbException $ex) {}

try {
    $mod->exec("ALTER TABLE mailer_form_params ADD PRIMARY KEY (form_id, name)");
} catch (waDbException $ex) {}