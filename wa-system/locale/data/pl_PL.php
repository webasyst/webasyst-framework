<?php

return array(
    'iso3' => 'pol',
    'name' => 'Polski',
    'region' => 'Polska',
    'english_name' => 'Polish',
    'english_region' => 'Poland',
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
    // Unlike the other Slavic languages here, the first form is used for 1 only:
    // "1 plik", "21 plików" (not "21 plik")
    'plural_forms' => array(
        'nplurals' => 3,
        'plural' => '((n==1)?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2))'
    ),
    'currency' => 'PLN',
);
