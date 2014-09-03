<?php 

$model = new waModel();

try {
    $model->exec("SELECT `cnt` FROM `wa_contact_category` WHERE 0");    
} catch (waDbException $e) {
    $model->exec("ALTER TABLE  `wa_contact_category` ADD  `cnt` INT NOT NULL DEFAULT '0'");
}

try {
    $model->exec("SELECT `cnt` FROM `wa_group` WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE  `wa_group` ADD  `cnt` INT NOT NULL DEFAULT  '0'");
}

try {
    $model->exec("SELECT `cnt` FROM `contacts_history` WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE `contacts_history` ADD  `cnt` INT NOT NULL DEFAULT  '-1'");
}