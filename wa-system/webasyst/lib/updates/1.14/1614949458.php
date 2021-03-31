<?php

(new webasystInstaller)->addUniqueIndex('wa_contact_calendars', 'sort', ['sort'], function($table) {
    // check for uniqueness
    $m = new waContactCalendarsModel();

    $sql = "SELECT COUNT(*)
            FROM (
                     SELECT sort
                     FROM `{$table}`
                     GROUP BY sort
                     having COUNT(*) > 1
            ) r";

    $count = intval($m->query($sql)->fetchField());
    if ($count <= 0) {
        return;
    }

    // if there are doubles - ensure there will not be doubles
    $sort = 0;
    $sql = "SELECT `id` FROM `{$table}` ORDER BY `sort`";
    $query = $m->query($sql);
    foreach ($query as $row) {
        $m->updateById($row['id'], ['sort' => $sort]);
        $sort++;
    }
});
