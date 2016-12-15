<?php

$m = new waModel();
try {
    $m->exec("CREATE INDEX `calendar_id` ON `team_calendar_external` (`calendar_id`)");
} catch (waDbException $e) {
    // if index already exists - ignore
    if ($e->getCode() != 1061) {
        throw $e;
    }
}
