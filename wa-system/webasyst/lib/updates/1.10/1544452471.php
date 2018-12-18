<?php
/**
 * This is a heavy update. Made for manual launch.
 */


/*
$m = new waModel();

try {
    $m->exec("LOCK TABLES `wa_transaction` WRITE");
} catch (Exception $e) {
    waLog::log('Not enough permissions to lock the table');
}

$model = new waTransactionModel();
$metadata = $model->getMetadata();
$amount_type = ifset($metadata, 'amount', 'type', null);

if ($amount_type === 'float') {
    $model->exec("UPDATE `wa_transaction` SET `create_datetime` = '1970-01-01 00:00:00' WHERE SUBSTR(CAST(create_datetime AS CHAR), 1, 10) = '0000-00-00'");
    $model->exec("UPDATE `wa_transaction` SET `update_datetime` = `create_datetime` WHERE SUBSTR(CAST(update_datetime AS CHAR), 1, 10) = '0000-00-00'");
    $model->exec("UPDATE `wa_transaction` SET `amount`=0 WHERE `amount` IS NULL");
    $model->exec("alter table `wa_transaction` modify `amount` varchar(255) default 0 not null");
    $model->exec("alter table `wa_transaction` modify `amount` decimal(20,8) default 0 not null");
}

try {
    $m->exec("UNLOCK TABLES");
} catch (Exception $e) {
    waLog::log('Not enough permissions to unlock the table');
}
*/
