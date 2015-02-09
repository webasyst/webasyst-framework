<?php

$mod = new waModel();

try {
    // Classify existing bounces
    $bounce_types = $this->getBounceTypes(); // $this === wa('mailer')->getConfig()
    $r = $mod->query("SELECT id, error, error_class FROM `mailer_message_log` WHERE error_class IS NULL AND error IS NOT NULL ORDER BY id DESC");
    foreach ($r as $row) {
        $error_type = null;
        foreach ($bounce_types as $type => $bt) {
            if (preg_match($bt['regex'], $row['error'])) {
                $error_type = $type;
                break;
            }
        }
        if ($error_type) {
            $mod->exec("UPDATE mailer_message_log SET error_class=:c WHERE id=:id", array(
                'c' => $error_type,
                'id' => $row['id'],
            ));
        }
    }
} catch (waDbException $e) {
}

