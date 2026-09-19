<?php

return array(
    'iso3' => 'kat',
    'name' => 'ქართული',
    'region' => 'საქართველო',
    'english_name' => 'Georgian',
    'english_region' => 'Georgia',
    'date_formats' => array(
        'humandate' => 'j f Y',
        'date' => 'd.m.Y',
        'dtime' => 'd.m H:i',
        'datetime' => 'd.m.Y H:i',
        'fulldatetime' => 'd.m.Y H:i:s'
    ),
    'decimal_point' => ',',
    'frac_digits' => '2',
    'thousands_sep' => ' ',
    'first_day' => 1,
    // Older GNU gettext docs put Georgian among the languages with a single form;
    // CLDR defines "one" and "other", which is what is used here
    'plural_forms' => array(
        'nplurals' => 2,
        'plural' => '(n != 1)'
    ),
    'currency' => 'GEL',
);
