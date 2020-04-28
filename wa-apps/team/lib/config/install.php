<?php

//
// This script is executed during installation
// after database tables are created. Also see db.php
//

// Create calendars
$cm = new waContactCalendarsModel();
if ($cm->countAll() <= 0) {
    $calendar_name = array('Vacation', 'Business', 'Illness', 'Meeting', 'Other');
    $calendar_bg = array('#5bb75b', '#99ccff', '#da4f49', '#fad54a', '#d4d4d4');
    $calendar_fc = array('#ffffff', '#000000', '#ffffff', '#000000', '#000000');
    $calendar_default_status = array('on vacation', 'in a business trip', 'sick', 'at the meeting', null);
    $calendars = array();
    for ($i = 0; $i < count($calendar_bg); $i++) {
        $calendars[] = array(
            'name'           => _w($calendar_name[$i]),
            'bg_color'       => $calendar_bg[$i],
            'font_color'     => $calendar_fc[$i],
            'sort'           => $i,
            'default_status' => _w($calendar_default_status[$i]),
        );
    }
    $cm->multipleInsert($calendars);
}

// Copy basic access rights (no access/limited/full access) from Contacts app to Team
$crm = new waContactRightsModel();
if (!$crm->countByField('app_id', 'team')) {
    $rows = $crm->getByField(array(
        'app_id' => 'contacts',
        'name' => 'backend',
    ), true);
    if ($rows) {
        foreach($rows as &$r) {
            $r['app_id'] = 'team';
        }
        unset($r);
        $crm->multipleInsert($rows);
    }
}

// Create groups if this is a new installation
$gm = new waGroupModel();
$groups = $gm->getAll();
if (!$groups) {
    $admin_group_id = $gm->insert(array(
        'name' => _w('Administrators'),
        'sort' => 0,
    ));
    $crm->save(-$admin_group_id, 'webasyst', 'backend', 2);

    $hq_group_id = $gm->insert(array(
        'name' => _w('My office'),
        'type' => 'location',
        'sort' => 1,
    ));
    $crm->save(-$hq_group_id, 'team', 'backend', 1);

    $remote_group_id = $gm->insert(array(
        'name' => _w('Remote'),
        'type' => 'location',
        'sort' => 2,
    ));
    $crm->save(-$remote_group_id, 'team', 'backend', 1);

    $ugm = new waUserGroupsModel();
    $contact_id = wa('webasyst')->getUser()->getId();
    $ugm->add($contact_id, $hq_group_id);
    if (wa()->getUser()->isAdmin()) {
        $ugm->add($contact_id, $admin_group_id);
    }
}
