<?php

class teamUsersInactiveAction extends teamContentViewAction
{
    public function execute()
    {
        $contacts = array();
        $cm = new waContactModel();
        $ids = $cm->select('id')->where('is_user=-1 AND login IS NOT NULL')->fetchAll('id', true);
        $ids = array_keys($ids);
        if ($ids) {
            $contacts = teamUser::getList('/id/'.join(',', $ids), array(
                'access_rights' => false,
                'order' => 'last_datetime DESC',
                'additional_fields' => array(
                    'update_datetime' => 'last_datetime',
                ),
            ));
        }
        $ids = array_keys($contacts);
        if ($ids) {
            // Get ban datetime from wa_log
            $log_model = new waLogModel();
            $rows = $log_model->select('subject_contact_id AS id, datetime')->where(
                "action = 'access_disable' AND subject_contact_id IN (i:id)",
                array('id' => $ids)
            )->order('datetime')->query();
            foreach ($rows as $row) {
                if (isset($contacts[$row['id']])) {
                    $contacts[$row['id']]['update_datetime'] = $row['datetime'];
                }
            }
            teamUser::convertFieldToUtc($contacts);
        }

        $this->view->assign(array(
            'contacts' => $contacts,
        ));
    }
}
