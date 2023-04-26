<?php

class teamUsersNoaccessAction extends teamContentViewAction
{
    public function execute()
    {
        if (!teamHelper::hasRights()) {
            throw new waRightsException(_w('Access denied'));
        }

        $contacts = array();
        $cm = new waContactModel();
        $ids = $cm->select('id')->where('is_staff > 0 AND is_user = 0')->fetchAll('id', true);
        $ids = array_keys($ids);
        if ($ids) {
            $contacts = teamUser::getList('/id/'.join(',', $ids), array(
                'access_rights' => false,
                'order' => 'last_datetime DESC',
            ));
        }
        $ids = array_keys($contacts);

        $this->view->assign(array(
            'contacts' => $contacts,
        ));
    }
}
