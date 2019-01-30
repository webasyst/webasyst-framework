<?php

class teamUsersAction extends teamContentViewAction
{
    protected static $offset = 0;
    protected static $limit = 50;

    public function execute()
    {
        $sort = $this->getSort();
        $contacts = teamUser::getList('users', array(
            'order' => $sort,
            'convert_to_utc' => 'update_datetime',
            'additional_fields' => array(
                'update_datetime' => 'c.create_datetime',
            ),
        ));

        // Redirect on first login
        if (wa()->getUser()->isAdmin('webasyst') && count($contacts) < 2) {
            $asm = new waAppSettingsModel();
            if (!$asm->getByField(array('app_id' => wa()->getApp(), 'name' => 'first_login'))) {
                $asm = new waAppSettingsModel();
                $asm->insert(array('app_id' => wa()->getApp(), 'name' => 'first_login', 'value' => date('Y-m-d H:i:s')));
                $this->redirect(wa()->getConfig()->getBackendUrl(true).wa()->getApp().'/welcome/');
            }
        }

        $this->view->assign(array(
            'contacts' => $contacts,
            'sort'     => $sort,
        ));
    }

    protected function getSort()
    {
        $sort = waRequest::request(
            'sort',
            wa()->getUser()->getSettings(wa()->getApp(), 'sort', 'last_seen'),
            waRequest::TYPE_STRING_TRIM
        );
        if (waRequest::request('sort')) {
            $csm = new waContactSettingsModel();
            $csm->set(wa()->getUser()->getId(), wa()->getApp(), 'sort', $sort);
        }
        return $sort;
    }
}
