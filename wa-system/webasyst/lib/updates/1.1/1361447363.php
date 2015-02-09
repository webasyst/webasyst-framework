<?php

$sqls = array();
$sqls['wa_transaction.app_id'] = 'ALTER TABLE  `wa_transaction` CHANGE  `application_id`  `app_id` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
$sqls['wa_transaction.plugin'] = array(
    'paymentsystem_id' => 'ALTER TABLE  `wa_transaction` CHANGE  `paymentsystem_id`  `plugin` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL',
    'payment_id'       => 'ALTER TABLE  `wa_transaction` CHANGE  `payment_id`  `plugin` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL',
);
$model = new waModel();
foreach ($sqls as $field => $sql) {
    try {
        list($table, $field) = explode('.', $field, 2);
        $sql_check = 'SELECT `%s` FROM `%s` WHERE 0';
        $model->query(sprintf($sql_check, $field, $table));
    } catch (waDbException $ex) {
        try {
            if (is_array($sql)) {
                $result = false;
                foreach ($sql as $field => $field_sql) {
                    try {
                        $model->query(sprintf($sql_check, $field, $table));
                        $model->query($field_sql);
                        $result = true;
                    } catch (waDbException $e) {
                        if (class_exists('waLog')) {
                            waLog::log(basename(__FILE__).': '.$e->getMessage(), 'update.log');
                        }
                    }
                    if ($result) {
                        break;
                    }
                }
                if (!$result && !empty($e)) {
                    throw $e;
                }
            } else {
                $model->query($sql);
            }
        } catch (waDbException $e) {
            if (class_exists('waLog')) {
                waLog::log(basename(__FILE__).': '.$e->getMessage(), 'update.log');
            }
            throw $e;
        }
    }
}

$sql = 'UPDATE `wa_transaction` SET `plugin`= LOWER(`plugin`)';
try {
    $model->query($sql);
} catch (waDbException $e) {
    if (class_exists('waLog')) {
        waLog::log(basename(__FILE__).': '.$e->getMessage(), 'update.log');
    }
    throw $e;
}
