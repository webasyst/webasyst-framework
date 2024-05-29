<?php

wa('webasyst');
$contact_rights_model = new waContactRightsModel();
$contact_ids = $contact_rights_model->getAllowedUsers('team', 'backend');
$contact_ids = array_filter($contact_ids, function($v) {
    return $v == '1';
});

$contact_rights_model->multipleInsert([
    'group_id' => array_map(function($contact_id) {
        return -$contact_id;
    }, array_keys($contact_ids)),
    'app_id' => 'team',
    'name' => 'edit_announcements',
    'value' => 1,
], waModel::INSERT_IGNORE);