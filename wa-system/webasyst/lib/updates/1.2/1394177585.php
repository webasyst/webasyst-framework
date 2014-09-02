<?php

$model = new waModel();

try {
    $model->query("SELECT birth_day FROM wa_contact WHERE 0");
} catch (waDbException $e) {
    $model->query("ALTER TABLE wa_contact ADD birth_day TINYINT(2) UNSIGNED NULL DEFAULT NULL");
}

try {
    $model->query("SELECT birth_month FROM wa_contact WHERE 0");
} catch (waDbException $e) {
    $model->query("ALTER TABLE wa_contact ADD birth_month TINYINT(2) UNSIGNED NULL DEFAULT NULL");
}

try {
    $model->query("SELECT birth_year FROM wa_contact WHERE 0");
} catch (waDbException $e) {
    $model->query("ALTER TABLE wa_contact ADD birth_year SMALLINT(4) NULL DEFAULT NULL");
}

try {
    $model->query("SELECT birthday FROM wa_contact WHERE 0");
    $model->exec("UPDATE wa_contact
        SET birth_day = DAY(birthday), birth_month = MONTH(birthday), birth_year = YEAR(birthday)
        WHERE birthday IS NOT NULL AND birthday != '0000-00-00'");
    $model->query("ALTER TABLE wa_contact DROP birthday");
} catch (waDbException $e) {
}
