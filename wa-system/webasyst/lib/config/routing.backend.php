<?php
$__id = '(\w[\w\d_]*)';
return array(
    'settings/sms/template/<id>/?'              => 'settingsTemplateSMS',
    'settings/sms/template/?'                   => 'settingsTemplateSMS',
    'settings/email/template/<id>/<template>/?' => 'settingsTemplateEmail',
    'settings/email/template/?'                 => 'settingsTemplateEmail',
    'settings/sms/?'                            => 'settingsSMS',
    'settings/email/?'                          => 'settingsEmail',
    'settings/maps/?'                           => 'settingsMaps',
    'settings/captcha/?'                        => 'settingsCaptcha',
    'settings/push/?'                           => 'settingsPush',
    'settings/auth/?'                           => 'settingsAuth',
    'settings/regions/?'                        => 'settingsRegions',
    'settings/field/?'                          => 'settingsField',
    'settings/db/?'                             => 'settingsDatabase',
    'settings/waid/?'                           => 'settingsWaID',
    'settings/?'                                => 'settings/',
    'repair'                                    => array(
        'url'    => 'repair/<action:(\w+)?>/?',
        'module' => 'repair',
    ),
    'pluginActions'                             => array(
        'url'    => "<module:(payments|shipping)>/<plugin_id:{$__id}>/<plugin_module:{$__id}>/<plugin_action:{$__id}?>/?",
        'action' => 'actions',
    ),
);
