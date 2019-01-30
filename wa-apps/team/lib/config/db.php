<?php

return array(
    'team_calendar_external' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'type' => array('varchar', 50),
        'integration_level' => array('enum', "'subscription','sync','full'", 'null' => 0, 'default' => 'subscription'),
        'name' => array('varchar', 255, 'null' => 0),
        'contact_id' => array('int', 11),
        'create_datetime' => array('datetime'),
        'calendar_id' => array('int', 11),
        'native_calendar_id' => array('text'),
        'synchronize_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_id' => 'contact_id',
            'calendar_id' => 'calendar_id'
        )
    ),
    'team_calendar_external_params' => array(
        'calendar_external_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('calendar_external_id', 'name')
        )
    ),
    'team_event_external' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'event_id' => array('int', 11, 'null' => 0),
        'calendar_external_id' => array('int', 11, 'null' => 0),
        'native_event_id' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'event_id_calendar_external_id' => array('event_id', 'calendar_external_id', 'unique' => 1),
            'calendar_external_id_native_event_id' => array('calendar_external_id', 'native_event_id')
        )
    ),
    'team_event_external_params' => array(
        'event_external_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('event_external_id', 'name')
        )
    ),
    'team_location' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'address' => array('varchar', 255),
        'longitude' => array('varchar', 50),
        'latitude' => array('varchar', 50),
        ':keys' => array(
            'PRIMARY' => array('group_id')
        )
    )
);
