<?php

class teamGroupSortSaveController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin($this->getAppId())) {
            throw new waRightsException();
        }

        $gm = new waGroupModel();

        $groups = $this->getRequest()->request('groups');
        if (!empty($groups)) {
            $sort = 0;
            foreach ($groups as $id) {
                $gm->updateById($id, array('sort' => $sort++));
            }
        }

        $locations = $this->getRequest()->request('locations');
        if (!empty($locations)) {
            $sort = 0;
            foreach ($locations as $id) {
                $gm->updateById($id, array('sort' => $sort++));
            }
        }
    }
}
