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

        $contacts = array();
        if ($res) {
            $ids = array();
            foreach ($res as $r) {
                $ids[] = $r['id'];
            }
            $contacts = teamUser::getList('/id/'.join(',', $ids), array(
                'order' => 'last_datetime DESC',
                'access_rights' => false,
            ));
        }
        $this->view->assign(array(
            'search' => $search,
            'contacts' => $contacts,
        ));
    }
}
