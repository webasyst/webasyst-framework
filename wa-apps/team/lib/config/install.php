<?php

//
// This script is executed during installation
// after database tables are created. Also see db.php
//

$locale = $this->loaded_locale == 'ru_RU' ? 'ru_RU' : 'en_US';

// Create calendars
$cm = new waContactCalendarsModel();
if ($cm->countAll() <= 0) {
    $calendar_name = array(
        'en_US' => array('Vacation', 'Business', 'Illness', 'Meeting', 'Other'),
        'ru_RU' => array('Отпуск', 'Командировка', 'Больничный', 'Встреча', 'Другое'),
    );
    $calendar_bg = array('#5bb75b', '#99ccff', '#da4f49', '#fad54a', '#d4d4d4');
    $calendar_fc = array('#ffffff', '#000000', '#ffffff', '#000000', '#000000');
    $calendar_default_status = array(
        'en_US' => array('on vacation', 'in a business trip', 'sick', 'at the meeting', null),
        'ru_RU' => array('в отпуске', 'в командировке', 'болею', 'на встрече', null),
    );
    $calendars = array();
    for ($i = 0; $i < count($calendar_bg); $i++) {
        $calendars[] = array(
            'name'           => $calendar_name[$locale][$i],
            'bg_color'       => $calendar_bg[$i],
            'font_color'     => $calendar_fc[$i],
            'sort'           => $i,
            'default_status' => $calendar_default_status[$locale][$i],
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
        'name' => $locale != 'ru_RU' ? 'Administrators' : 'Администраторы',
        'sort' => 0,
    ));
    $crm->save(-$admin_group_id, 'webasyst', 'backend', 2);

    $hq_group_id = $gm->insert(array(
        'name' => $locale != 'ru_RU' ? wa('webasyst')->accountName().' HQ' : wa('webasyst')->accountName(),
        'type' => 'location',
        'sort' => 1,
    ));
    $crm->save(-$hq_group_id, 'team', 'backend', 1);

    $remote_group_id = $gm->insert(array(
        'name' => $locale != 'ru_RU' ? 'Remote' : 'Remote',
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
