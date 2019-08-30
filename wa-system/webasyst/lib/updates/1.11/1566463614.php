<?php
$model = new waModel();

try {
    $model->exec("select `region_center` from `wa_region`");
} catch (Exception $e) {
    $model->exec("alter table `wa_region` add `region_center` varchar(255) null;");

    $regions = $model->query("SELECT *
                                    FROM `wa_region`
                                    WHERE (`country_iso3` = 'rus' AND (`code` = '77' OR `code` = '78' OR `code` = '92')) OR
                                        (`country_iso3` = 'ukr' AND `code` = '27')")
                     ->fetchAll();

    // Set default cities
    foreach ($regions as $region) {
        $region_center = null;
        if ($region['code'] == 77 && $region['country_iso3'] == 'rus') {
            $region_center = 'Москва';
        } elseif ($region['code'] == 78 && $region['country_iso3'] == 'rus') {
            $region_center = 'Санкт-Петербург';
        } elseif ($region['code'] == 92 && $region['country_iso3'] == 'rus') {
            $region_center = 'Севастополь';
        } elseif ($region['code'] == 27 && $region['country_iso3'] == 'ukr') {
            $region_center = 'Севастополь';
        }

        if ($region_center) {
            $model->exec("UPDATE `wa_region`
                                SET `region_center` = '{$region_center}'
                                WHERE `code` = ? AND `country_iso3` = ?", $region['code'], $region['country_iso3']);
        }
    }

}