<?php

$_old_files = [
    "img/_screen_access.png",
    "img/_screen_calendar.png",
    "img/_screen_calendar_personal.png",
    "img/_screen_settings.png",
    "img/_screen_timeline.png",
    "lib/actions/profile/teamProfileWaidUnbind.controller.php",
    "lib/actions/profile/teamProfileWaidUnbindConfirm.action.php",
    "lib/actions/profile/teamProfileWaidAccountInfo.action.php",
    "templates/actions/InviteFrontend.html",
    "templates/actions/profile/ProfileWaidUnbindConfirm.html",
    "templates/actions/profile/ProfileWaidAccountInfo.html",
];

foreach ($_old_files as &$_old_file) {
    $_old_file = wa()->getAppPath($_old_file, 'team');
}
unset($_old_file);

foreach ($_old_files as $_file) {
    if (file_exists($_file)) {
        try {
            waFiles::delete($_file);
        } catch (waException $exception) {

        }
    }
}
