<?php

class waAnnouncementRightsModel extends waModel
{
    protected $table = 'wa_announcement_rights';

    public function set($announcement_id, $group_ids)
    {
        $this->deleteByField('announcement_id', $announcement_id);
        if ($group_ids) {
            $this->multipleInsert([
                'announcement_id' => $announcement_id,
                'group_id' => $group_ids,
            ], waModel::INSERT_IGNORE);
        }
    }

    public function getIds($announcement_id) {
        $group_ids = [];
        $contact_ids = [];
        foreach($this->getByField('announcement_id', $announcement_id, true) as $row) {
            if ($row['group_id'] > 0) {
                $group_ids[] = (int) $row['group_id'];
            } else {
                $contact_ids[] = -$row['group_id'];
            }
        }
        return [
            $group_ids,
            $contact_ids,
        ];
    }

}
