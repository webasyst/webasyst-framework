<?php

$mod = new mailerReturnPathModel();

try {
    $mod->exec("SELECT last_error FROM mailer_return_path LIMIT 0");
} catch (waDbException $e) {
    $mod->exec("ALTER TABLE mailer_return_path ADD last_error TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");
}

try {
    $mod->exec("SELECT last_campaign_date FROM mailer_return_path LIMIT 0");
} catch (waDbException $e) {
    $mod->exec("ALTER TABLE `mailer_return_path`
                    ADD `last_campaign_date` DATE NULL DEFAULT NULL,
                    ADD INDEX (`last_campaign_date`)");

    $dts = $mod->query("SELECT rp.id, MAX(DATE(IFNULL(m.finished_datetime, m.send_datetime))) AS dt
                        FROM mailer_return_path AS rp
                            JOIN mailer_message AS m
                                ON rp.email=m.return_path
                        WHERE m.status > 0
                        GROUP BY rp.id");
    foreach ($dts as $row) {
        $mod->updateById($row['id'], array('last_campaign_date' => $row['dt']));
    }
}

