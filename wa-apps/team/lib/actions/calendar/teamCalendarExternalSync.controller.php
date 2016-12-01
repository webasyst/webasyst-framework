<?php

class teamCalendarExternalSyncController extends waJsonController
{
    public function execute()
    {
        self::runSync();
    }

    public static function runSync()
    {
        $sync = new teamCalendarExternalSync();
        $sync->execute();
    }
}
