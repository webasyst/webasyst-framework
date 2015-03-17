<?php

$mod = new waModel();

try {
    $mod->exec("UPDATE mailer_message SET status=9 WHERE status=3");
} catch (waDbException $e) {
}

