<?php
return array(
    'wa_api_auth_codes' => array(
        'code' => array('varchar', 32, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'client_id' => array('varchar', 32, 'null' => 0),
        'scope' => array('text', 'null' => 0),
        'expires' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'code',
        ),
    ),
    'wa_api_tokens' => array(
        'contact_id' => array('int', 11, 'null' => 0),
        'client_id' => array('varchar', 32, 'null' => 0),
        'token' => array('varchar', 32, 'null' => 0),
        'scope' => array('text', 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'last_use_datetime' => array('datetime'),
        'expires' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'token',
            'contact_client' => array('contact_id', 'client_id', 'unique' => 1),
        ),
    ),
    'wa_push_subscribers' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'provider_id' => array('varchar', 64, 'null' => 0),
        'domain' => array('varchar', 255, 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'contact_id' => array('int', 11), // optional (eg. for frontend users)
        'subscriber_data' => array('text', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'provider_id' => 'provider_id',
            'domain' => 'domain',
            'contact_id' => 'contact_id',
            'create_datetime' => 'create_datetime',
        ),
    ),
    'wa_announcement' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'app_id' => array('varchar', 32, 'null' => 0),
        'text' => array('text', 'null' => 0),
        'datetime' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'app_datetime' => array('datetime', 'app_id'),
        ),
    ),
    'wa_app_settings' => array(
        'app_id' => array('varchar', 64, 'null' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('mediumtext', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('app_id', 'name'),
        ),
    ),
    'wa_app_tokens' => array(
        'contact_id' => array('int', 11),
        'app_id' => array('varchar', 32, 'null' => 0),
        'type' => array('varchar', 32, 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'expire_datetime' => array('datetime'),
        'token' => array('varchar', 32, 'null' => 0),
        'data' => array('text'),
        ':keys' => array(
            'token' => array('token', 'unique' => 1),
            'app' => 'app_id',
            'contact' => 'contact_id',
            'expire' => 'expire_datetime',
        ),
    ),
    'wa_contact' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 150, 'null' => 0),
        'firstname' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'middlename' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'lastname' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'title' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'company' => array('varchar', 150, 'null' => 0, 'default' => ''),
        'jobtitle' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'company_contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'is_company' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'is_user' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'is_staff' => array('int', 11, 'null' => 0, 'default' => '0'),
        'login' => array('varchar', 32),
        'password' => array('varchar', 128, 'null' => 0, 'default' => ''),
        'last_datetime' => array('datetime'),
        'sex' => array('enum', "'m','f'"),
        'birth_day' => array('tinyint', 2, 'unsigned' => 1),
        'birth_month' => array('tinyint', 2, 'unsigned' => 1),
        'birth_year' => array('smallint', 4),
        'about' => array('text'),
        'photo' => array('int', 10, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        'create_datetime' => array('datetime', 'null' => 0),
        'create_app_id' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'create_method' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'create_contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'locale' => array('varchar', 8, 'null' => 0, 'default' => ''),
        'timezone' => array('varchar', 64, 'null' => 0, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
            'login' => array('login', 'unique' => 1),
            'name' => 'name',
            'is_user' => 'is_user',
            'is_staff' => 'is_staff'
        ),
    ),
    'wa_contact_auths' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0),
        'session_id' => array('varchar', 255, 'null' => 0),
        'token' => array('varchar', 42, 'null' => 0),
        'login_datetime' => array('datetime'),
        'last_datetime' => array('datetime'),
        'user_agent' => array('varchar', 255),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_id' => 'contact_id',
            'token' => 'token',
            'session_id' => array('session_id', 'unique' => 1),
            'contact_session_id' => array('contact_id', 'session_id', 'unique' => 1)
        ),
    ),

    'wa_contact_calendars' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'bg_color' => array('varchar', 7),              // background color for events
        'font_color' => array('varchar', 7),            // font color for events
        'status_bg_color' => array('varchar', 7),       // background color for statuses
        'status_font_color' => array('varchar', 7),     // font color for statuses
        'icon' => array('varchar', 255),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'is_limited' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'default_status' => array('varchar', 255),
        ':keys' => array(
            'PRIMARY' => 'id',
            'sort' => array('id', 'unique' => 1)
        ),
    ),
    'wa_contact_categories' => array(
        'category_id' => array('int', 11, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('category_id', 'contact_id'),
            'contact_id' => 'contact_id',
        ),
    ),
    'wa_contact_category' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'system_id' => array('varchar', 64),
        'app_id' => array('varchar', 32),
        'icon' => array('varchar', 255),
        'cnt' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'system_id' => array('system_id', 'unique' => 1),
        ),
    ),
    'wa_contact_data' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0),
        'field' => array('varchar', 32, 'null' => 0),
        'ext' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'value' => array('varchar', 255, 'null' => 0),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),

        // Status need mostly for phones
        // Status for phone likewise status for email may be 'confirmed','unconfirmed','unavailable'
        // NULL available, cause there are fields do not need this
        // varchar (not ENUM), cause there are other fields that may have some other statuses
        'status' => array('varchar', 255, 'null' => 1),

        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_field_sort' => array('contact_id', 'field', 'sort', 'unique' => 1),
            'contact_id' => 'contact_id',
            'value' => 'value',
            'field' => 'field',
        ),
    ),
    'wa_contact_data_text' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0),
        'field' => array('varchar', 32, 'null' => 0),
        'ext' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'value' => array('text', 'null' => 0),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_field_sort' => array('contact_id', 'field', 'sort', 'unique' => 1),
            'contact_id' => 'contact_id',
            'field' => 'field',
        ),
    ),
    'wa_contact_emails' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0),
        'email' => array('varchar', 255, 'null' => 0),
        'ext' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'status' => array('enum', "'unknown','confirmed','unconfirmed','unavailable'", 'null' => 0, 'default' => 'unknown'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_sort' => array('contact_id', 'sort', 'unique' => 1),
            'email' => 'email',
            'status' => 'status',
        ),
    ),
    'wa_contact_events' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'uid' => array('varchar', 255),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime', 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'calendar_id' => array('int', 11, 'null' => 0),
        'summary' => array('varchar', 255, 'null' => 0),
        'description' => array('text'),
        'location' => array('varchar', 255),
        'start' => array('datetime', 'null' => 0),
        'end' => array('datetime', 'null' => 0),
        'is_allday' => array('tinyint', 4, 'null' => 0, 'default' => '0'),
        'is_status' => array('tinyint', 4, 'null' => 0, 'default' => '0'),
        'sequence' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'uid' => 'uid',
            'contact_id' => 'contact_id',
            'calendar_id' => 'calendar_id',
        ),
    ),
    'wa_contact_files' => array(
        'id' => array('int', 11, 'null' => 0,  'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0),
        'purpose' => array('enum', "'cover','general'", 'null' => 0, 'default' => 'general'),
        'name' => array('varchar', 255),
        'sort' => array('int', 11, 'null' => 0, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_id' => 'contact_id',
            'purpose' => 'purpose'
        ),
    ),
    'wa_contact_field_values' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'parent_field' => array('varchar', 64, 'null' => 0),
        'parent_value' => array('varchar', 255, 'null' => 0),
        'field' => array('varchar', 64, 'null' => 0),
        'value' => array('varchar', 255, 'null' => 0),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'parent_field' => array('parent_field', 'parent_value'),
        ),
    ),
    'wa_contact_rights' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'app_id' => array('varchar', 32, 'null' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'app_id', 'name'),
            'name_value' => array('name', 'value', 'group_id', 'app_id'),
        ),
    ),
    'wa_contact_settings' => array(
        'contact_id' => array('int', 11, 'null' => 0),
        'app_id' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('contact_id', 'app_id', 'name'),
        ),
    ),
    'wa_contact_waid' => array(
        'contact_id' => array('int', 11, 'null' => 0),
        'token' => array('text', 'null' => 0),
        'webasyst_contact_id' => array('int', 11, 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'login_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => array('contact_id'),
            'webasyst_contact_id' => array('webasyst_contact_id', 'unique' => 1)
        )
    ),
    'wa_country' => array(
        'name' => array('varchar', 255, 'null' => 0),
        'iso3letter' => array('varchar', 3, 'null' => 0),
        'iso2letter' => array('varchar', 2, 'null' => 0),
        'isonumeric' => array('varchar', 3, 'null' => 0),
        'fav_sort' => array('int', 11),
        ':keys' => array(
            'PRIMARY' => 'iso3letter',
            'isonumeric' => array('isonumeric', 'unique' => 1),
            'iso2letter' => array('iso2letter', 'unique' => 1),
            'name' => 'name',
        ),
    ),
    'wa_dashboard' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'hash' => array('varchar', 32, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'hash' => array('hash', 'unique' => 1),
        ),
    ),
    'wa_group' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'cnt' => array('int', 11, 'null' => 0, 'default' => '0'),
        'icon' => array('varchar', 255, 'null' => 1),
        'sort' => array('int', 11, 'null' => 1),
        'type' => array('enum', "'group','location'", 'null' => 0, 'default' => 'group'),
        'description' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'name' => 'name',
        ),
    ),
    'wa_log' => array(
        'id' => array('bigint', 20, 'null' => 0, 'autoincrement' => 1),
        'app_id' => array('varchar', 32, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'datetime' => array('datetime', 'null' => 0),
        'action' => array('varchar', 255, 'null' => 0),
        'subject_contact_id' => array('int', 11, 'null' => 1),
        'params' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact' => array('contact_id', 'id'),
            'datetime' => 'datetime',
        ),
    ),
    'wa_login_log' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0),
        'datetime_in' => array('datetime', 'null' => 0),
        'datetime_out' => array('datetime'),
        'ip' => array('varchar', 45),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_datetime' => array('contact_id', 'datetime_out')
        ),
    ),
    'wa_region' => array(
        'country_iso3' => array('varchar', 3, 'null' => 0),
        'code' => array('varchar', 8, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'fav_sort' => array('int', 11),
        'region_center' => array('varchar', 255),
        ':keys' => array(
            'PRIMARY' => array('country_iso3', 'code'),
        ),
    ),
    'wa_transaction' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'plugin' => array('varchar', 50, 'null' => 0),
        'app_id' => array('varchar', 50, 'null' => 0),
        'merchant_id' => array('varchar', 50),
        'native_id' => array('varchar', 255, 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime', 'null' => 0),
        'type' => array('varchar', 20, 'null' => 0),
        'parent_id' => array('int', 11),
        'order_id' => array('varchar', 50),
        'part_number' => array('int', 11, 'null' => 0, 'default' => '0'),
        'customer_id' => array('varchar', 50),
        'result' => array('varchar', 20, 'null' => 0),
        'error' => array('varchar', 255),
        'state' => array('varchar', 20),
        'view_data' => array('text'),
        'amount' => array('decimal', "20,8", 'null' => 0, 'default' => '0.00000000'),
        'currency_id' => array('varchar', 3),
        ':keys' => array(
            'PRIMARY' => 'id',
            'plugin' => 'plugin',
            'app_id' => 'app_id',
            'merchant_id' => 'merchant_id',
            'transaction_native_id' => 'native_id',
            'parent_id' => 'parent_id',
            'order_id' => 'order_id',
            'customer_id' => 'customer_id',
        ),
    ),
    'wa_transaction_data' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'transaction_id' => array('int', 11, 'null' => 0),
        'field_id' => array('varchar', 50, 'null' => 0),
        'value' => array('varchar', 255),
        ':keys' => array(
            'PRIMARY' => 'id',
            'transaction_id' => 'transaction_id',
            'field_id' => 'field_id',
            'value' => 'value',
        ),
    ),
    'wa_user_groups' => array(
        'contact_id' => array('int', 11, 'null' => 0),
        'group_id' => array('int', 11, 'null' => 0),
        'datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => array('contact_id', 'group_id'),
            'group_id' => 'group_id',
        ),
    ),
    'wa_verification_channel' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'address' => array('varchar', 64, 'null' => 0),
        'type' => array('varchar', 64, 'null' => 0),
        'create_datetime' => array('datetime'),
        'system' => array('int', 3, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'address' => 'address'
        )
        ),
    'wa_verification_channel_params' => array(
        'channel_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('channel_id', 'name')
        )
    ),
    'wa_verification_channel_assets' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'channel_id' => array('int', 11, 'null' => 0),
        'address' => array('varchar', 64, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0, 'default' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        'expires' => array('datetime'), // IF NULL asset never expires
        'tries' => array('int', 11, 'null' => 0, 'default' => 0),    // How may validation tries already done
        ':keys' => array(
            'PRIMARY' => 'id',
            'channel_address_name' => array('channel_id', 'address', 'contact_id', 'name', 'unique' => 1),
            'name' => 'name',
            'expires' => 'expires'
        )
    ),
    'wa_widget' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'widget' => array('varchar', 32, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'dashboard_id' => array('int', 11),
        'create_datetime' => array('datetime', 'null' => 0),
        'app_id' => array('varchar', 32, 'null' => 0),
        'block' => array('int', 11, 'null' => 0),
        'sort' => array('int', 11, 'null' => 0),
        'size' => array('char', 3, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'wa_widget_params' => array(
        'widget_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 32, 'null' => 0),
        'value' => array('text', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('widget_id', 'name'),
        ),
    ),
    'wa_cache' => array(
        'id'      => array('bigint', 20, 'null' => 0, 'autoincrement' => 1),
        'name'    => array('varchar', 255, 'null' => 0),
        'expires' => array('datetime', 'null' => 0),
        ':keys'   => array(
            'PRIMARY' => 'id',
            'name'    => array('name', 'unique' => 1),
            'expires' => 'expires',
        ),
    ),
);
