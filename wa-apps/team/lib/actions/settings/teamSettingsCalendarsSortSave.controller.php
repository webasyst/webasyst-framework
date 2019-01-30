<?php
class teamSettingsCalendarsSortSaveController extends waJsonController
{
    public function execute()
    {
        if (!teamHelper::hasRights()) {
            throw new waRightsException();
        }
        $calendars = waRequest::request('calendars', array(), waRequest::TYPE_ARRAY_TRIM);
        $ccm = new waContactCalendarsModel();
        $sort = 0;
        foreach ($calendars as $id) {
            $ccm->updateById($id, array('sort' => $sort++));
        }
    }
}
