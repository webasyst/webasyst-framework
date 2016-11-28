<?php
$model = new waModel();
try {
    $model->exec("ALTER TABLE `wa_login_log` ADD INDEX `contact_datetime` (`contact_id`,`datetime_out`)");
} catch (Exception $e) {
}
