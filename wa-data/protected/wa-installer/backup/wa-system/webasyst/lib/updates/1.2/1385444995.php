<?php

$model = new waModel();
// remove characters +-()
$model->exec("UPDATE wa_contact_data SET value = REPLACE(value, '+', '') WHERE field = 'phone' AND value LIKE '%+%'");
$model->exec("UPDATE wa_contact_data SET value = REPLACE(value, '-', '') WHERE field = 'phone' AND value LIKE '%-%'");
$model->exec("UPDATE wa_contact_data SET value = REPLACE(value, '(', '') WHERE field = 'phone' AND value LIKE '%(%'");
$model->exec("UPDATE wa_contact_data SET value = REPLACE(value, ')', '') WHERE field = 'phone' AND value LIKE '%)%'");

// remove spaces between digits
$rows = $model->query("SELECT id, value FROM wa_contact_data WHERE field='phone' AND value LIKE '% %'");
foreach ($rows as $row) {
    $sql = "UPDATE wa_contact_data SET value = '".$model->escape(preg_replace('/(\d)\s+(\d)/i', '$1$2', trim($row['value'])))."' WHERE id = ".(int)$row['id'];
    $model->exec($sql);
}