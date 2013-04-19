<?php

$mod = new waModel();
try {
    $mod->exec("ALTER TABLE `wa_contact_emails` ADD INDEX `status` (`status`)");
} catch (waDbException $e) {
}

