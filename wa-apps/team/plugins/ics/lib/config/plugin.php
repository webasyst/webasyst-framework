<?php

return array(
    'name'        => 'iCalendar',
    'description' => /*_wp*/('RFC 5545 iCalendar specification support for events import via WebCal subscriptions (iCal URL) and uploading local .ics files'),
    'icon'        => 'img/ics16.png',
    'img'         => 'img/ics16.png',
    'version'     => '1.1.0',
    'vendor'      => 'webasyst',
    'frontend'    => false,
    'external_calendar' => true,
    'integration_level' => 'subscription',
    'handlers' => array(
        'backend_schedule_settings' => 'backendScheduleSettings',
        'backend_assets' => 'backendAssets',
    )
);