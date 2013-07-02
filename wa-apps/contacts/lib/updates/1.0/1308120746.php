<?php

$sql = "ALTER TABLE `contacts_history` CHANGE `accessed` `accessed` DATETIME DEFAULT NULL";
$model = new waModel();
$model->exec($sql);

$path = wa()->getDataPath('photo', true, 'contacts');

copy($this->getAppPath('lib/config/data/thumb.php'), $path.'/thumb.php');