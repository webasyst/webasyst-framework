<?php

$_table = 'wa_contact_calendars';
$_columns = [
    'status_bg_color' => 'VARCHAR(7) NULL DEFAULT NULL',
    'status_font_color' => 'VARCHAR(7) NULL DEFAULT NULL',
    'icon' => 'VARCHAR(255) NULL DEFAULT NULL'
];

$_migrate = (new webasystInstaller());
foreach (array_reverse($_columns, true) as $_column_name => $_column_def) {
    $_migrate->addColumn($_table, $_column_name, $_column_def, 'font_color');
}
