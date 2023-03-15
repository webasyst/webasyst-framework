<?php
$m = new waModel();

$add_index_is_user = function() use ($m) {
    $m->exec("ALTER TABLE `wa_contact` ADD INDEX `is_user` (`is_user`)");
};

$fix_incorrect_datetime = function() use ($m) {
    // Incorrect datetime value: '0000-00-00 00:00:00' for column 'create_datetime'
    // a common problem for old installations due to framework bug
    // fix that first, then retry

    try {
        $m->exec("UPDATE wa_contact SET create_datetime='1970-01-01 11:11:11' WHERE CAST(create_datetime AS CHAR(20)) = '0000-00-00 00:00:00'");
    } catch (waDbException $e) {
        // oh, well, we tried
    }
};

try {
    $add_index_is_user();
} catch (waDbException $e) {
    if ($e->getCode() == 1061) {
        // ignore if index already exists
    } else if ($e->getCode() == 1292) {
        $fix_incorrect_datetime();
        try {
            $add_index_is_user();
        } catch (waDbException $e) {
            if ($e->getCode() == 1061) {
                // ignore if index already exists
            } else {
                throw $e;
            }
        }
    } else {
        throw $e;
    }
}

try {
    $m->exec("SELECT is_staff FROM wa_contact WHERE 0");
} catch (waDbException $e) {
    $add_field_is_staff = function() use ($m) {
        $m->exec("ALTER TABLE `wa_contact`
                    ADD `is_staff` INT(11) NOT NULL DEFAULT 0 AFTER `is_user`,
                    ADD INDEX `is_staff` (`is_staff`)");
    };

    try {
        $add_field_is_staff();
    } catch (waDbException $e) {
        if ($e->getCode() == 1292) {
            $fix_incorrect_datetime();
            $add_field_is_staff();
        } else {
            throw $e;
        }
    }

    $m->exec("UPDATE `wa_contact` SET `is_staff` = 1 WHERE `is_user` > 0");
}
