<?php

class teamUsersSearchAction extends teamContentViewAction
{
    public function execute()
    {
        $search = waRequest::param('search', null, waRequest::TYPE_STRING_TRIM);
        if (!$search) {
            throw new waException('Search string not found');
        }
        $res = teamAutocompleteController::usersAutocomplete($search) ;

        $contacts = $list = array();
        if ($res) {
            $ids = array();
            foreach ($res as $r) {
                $ids[] = $r['id'];
            }
            $list = teamUser::getList('/id/'.join(',', $ids), array(
                'order' => 'last_datetime DESC',
                'access_rights' => false,
            ));
        }
        foreach ($res as $r) {
            $contacts[$r['id']] = $list[$r['id']];
        }
        $this->view->assign(array(
            'search' => $search,
            'contacts' => $contacts,
        ));
    }
}
