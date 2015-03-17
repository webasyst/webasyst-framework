<?php

try {
    $model = new waModel();
    $sql = "SELECT contact_id, MAX(id) id FROM wa_login_log WHERE datetime_out IS NULL GROUP BY contact_id HAVING count(*) > 1";
    $rows = $model->query($sql)->fetchAll();
    foreach ($rows as $row) {
        $sql = "UPDATE wa_login_log SET datetime_out = datetime_in
                WHERE  datetime_out IS NULL AND contact_id = ".(int)$row['contact_id']." AND id != ".(int)$row['id'];
        $model->exec($sql);
    }
} catch (waDbException $e) {
}