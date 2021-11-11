<?php

class teamSettingsCalendarsSortSaveController extends waJsonController
{
    public function execute()
    {
        if (!teamHelper::hasRights()) {
            throw new waRightsException();
        }

        $calendars = waRequest::request('calendars', array(), waRequest::TYPE_ARRAY_TRIM);
        $this->reSort($calendars);

    }

    private function reSort(array $ids = [])
    {
        $ids = waUtils::toIntArray($ids);
        $ids = waUtils::dropNotPositive($ids);
        if (!$ids) {
            return;
        }

        $ccm = new waContactCalendarsModel();

        // drop not existing
        $ccm->select('id')->where('id IN(:ids)', [
            'ids' => $ids
        ])->fetchAll(null, true);


        $shift = count($ids);

        $ccm->exec("UPDATE {$ccm->getTableName()} SET sort = sort + {$shift} ORDER BY sort DESC");

        $sort = 0;
        foreach ($ids as $id) {
            $ccm->updateById($id, array('sort' => $sort++));
        }

        $remaining_ids = $ccm->select('id')->where('sort >= :shift', [
            'shift' => $shift
        ])->order('sort ASC')->fetchAll(null, true);

        $sort = $shift;
        foreach ($remaining_ids as $id) {
            $ccm->updateById($id, array('sort' => $sort++));
        }

    }

}
