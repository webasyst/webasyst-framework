<?php

return array(
    'user_name_formats' => array(
        array(
            'format' => 'lastname,firstname,middlename',
            'name' => 'Lastname Firstname Middlename'
        ),
        array(
            'format' => 'lastname,firstname',
            'name' => 'Lastname Firstname'
        ),
        array(
            'format' => 'firstname,lastname',
            'name' => 'Firstname Lastname'
        ),
        array(
            'format' => 'firstname,middlename,lastname',
            'name' => 'Firstname Middlename Lastname'
        ),
        array(
            'format' => 'login',
            'name' => 'Login'
        )
    ),
    'external_calendar_sync_max_date_offset' => '6'     // in months
);