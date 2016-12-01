<?php

return array(
    'user_name_formats' => array(
        array(
            'format' => 'lastname,firstname,middlename',
            'name' => _w('Lastname Firstname Middlename')
        ),
        array(
            'format' => 'lastname,firstname',
            'name' => _w('Lastname Firstname')
        ),
        array(
            'format' => 'firstname,lastname',
            'name' => _w('Firstname Lastname')
        ),
        array(
            'format' => 'firstname,middlename,lastname',
            'name' => _w('Firstname Middlename Lastname')
        ),
        array(
            'format' => 'login',
            'name' => _w('Login')
        )
    ),
    'external_calendar_sync_max_date_offset' => '6'     // in months
);