<?php

$m = new waCountryModel();

$_countries = array(
    array(
        'name' => 'Abkhazia',
        'iso3letter' => 'abh',
        'iso2letter' => 'ab',
        'isonumeric' => '895',
    ),
    array(
        'name' => 'Bonaire, Sint Eustatius and Saba',
        'iso3letter' => 'bes',
        'iso2letter' => 'bq',
        'isonumeric' => '535',
    ),
    array(
        'name' => 'Guernsey',
        'iso3letter' => 'ggy',
        'iso2letter' => 'gg',
        'isonumeric' => '831',
    ),
    array(
        'name' => 'Jersey',
        'iso3letter' => 'jey',
        'iso2letter' => 'je',
        'isonumeric' => '832',
    ),
    array(
        'name' => 'Curaçao',
        'iso3letter' => 'cuw',
        'iso2letter' => 'cw',
        'isonumeric' => '531',
    ),
    array(
        'name' => 'Isle of Man',
        'iso3letter' => 'imn',
        'iso2letter' => 'im',
        'isonumeric' => '833',
    ),
    array(
        'name' => 'Saint Barthélemy',
        'iso3letter' => 'blm',
        'iso2letter' => 'bl',
        'isonumeric' => '652',
    ),
    array(
        'name' => 'Saint Martin (French Part)',
        'iso3letter' => 'maf',
        'iso2letter' => 'mf',
        'isonumeric' => '663',
    ),
    array(
        'name' => 'Sint Maarten',
        'iso3letter' => 'sxm',
        'iso2letter' => 'sx',
        'isonumeric' => '534',
    ),
    array(
        'name' => 'South Ossetia',
        'iso3letter' => 'ost',
        'iso2letter' => 'os',
        'isonumeric' => '896',
    ),
    array(
        'name' => 'South Sudan',
        'iso3letter' => 'ssd',
        'iso2letter' => 'ss',
        'isonumeric' => '728',
    ),
);

foreach ($_countries as $_country) {
    try {
        $m->insert($_country);
    } catch (waDbException $e) {
        if ($e->getCode() != 1062) {
            throw $e;
        }
    }
}
