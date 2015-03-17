<?php

$mod = new waModel();
try {
    $mod->exec("DELETE FROM wa_country WHERE locale<>'en_US'");
    $old_db = true;
} catch (waDbException $e) {
    // locale field does not exist: table is already updated
    $old_db = false;
}

if ($old_db) {
    // Drop old indices
    try {
        $mod->exec("ALTER TABLE wa_country DROP INDEX `PRIMARY`");
    } catch (waDbException $e) { }
    try {
        $mod->exec("ALTER TABLE wa_country DROP INDEX `iso3letter`");
    } catch (waDbException $e) { }
    try {
        $mod->exec("ALTER TABLE wa_country DROP INDEX `isonumeric`");
    } catch (waDbException $e) { }

    // Drop locale
    try {
        $mod->exec("ALTER TABLE wa_country DROP locale");
    } catch (waDbException $e) { }

    // Add new indices
    try {
        $mod->exec("ALTER TABLE wa_country
                        ADD PRIMARY KEY (iso3letter),
                        ADD UNIQUE isonumeric (isonumeric),
                        ADD UNIQUE iso2letter (iso2letter)");
    } catch (waDbException $e) { }
}

// Populate wa_country if it is empty
if (!$mod->query("SELECT iso3letter FROM wa_country LIMIT 1")->fetchField()) {
    if ( ( $sql = @file_get_contents($this->getAppPath('lib/config/wa_country.sql')))) {
        $mod->exec($sql);
    }
}

