<?php
$model = new waModel();
$sqls = array();
$sqls[] = "ALTER TABLE stickies_sheet DROP INDEX sort";
$sqls[] = "SET @sort := 0";
$sqls[] = "UPDATE `stickies_sheet` SET `sort`=(@sort := @sort + 1) ORDER BY `sort`";
foreach ($sqls as $sql) {
    try {
        $model->exec($sql);
    } catch (waDbException $e) {
    }
}