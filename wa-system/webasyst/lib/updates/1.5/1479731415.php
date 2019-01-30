<?php
$model = new waModel();
try {
    $model->exec("ALTER TABLE `wa_log` ADD INDEX `contact` (`contact_id`, `id`)");
} catch (Exception $e) {
}
