<?php

/**
 * HTML for dialog in backend to invite a new user.
 */
class teamUsersInviteformAction extends waViewAction
{
    public function execute()
    {
        if (!teamHelper::hasRights('add_users')) {
            throw new waRightsException();
        }
        $all_groups = teamHelper::getVisibleGroups();
        $all_locations = teamHelper::getVisibleLocations();

        $contact_rights_model = new waContactRightsModel();

        $groups = $locations = array();
        foreach ($all_groups as $id => $g) {
            if (teamHelper::hasRights('manage_group.'.$g['id'])) {
                $groups[$id] = $g;
                $access = $contact_rights_model->getApps($id, 'backend', false, false) + array('webasyst' => 0);
                $groups[$id]['apps'] = teamHelper::appsWithAccessRights($access);
            }
        }
        foreach ($all_locations as $id => $g) {
            if (teamHelper::hasRights('manage_group.'.$g['id'])) {
                $locations[$id] = $g;
                $access = $contact_rights_model->getApps($id, 'backend', false, false) + array('webasyst' => 0);
                $locations[$id]['apps'] = teamHelper::appsWithAccessRights($access);
            }
        }

        list($is_waid_forced, $is_waid_enabled) = $this->getWaidStatus();

        $event_data = compact('groups', 'locations');
        $this->view->assign(array(
            'frontend_invite_user' => wa()->event('frontend_invite_user', $event_data),
            'groups'    => $groups,
            'locations' => $locations,
            'access_types' => teamHelper::getAccessTypes(),
            'is_waid_forced' => $is_waid_forced,
            'is_waid_enabled' => $is_waid_enabled,
        ));
    }

    protected function getWaidStatus()
    {
        $cm = new waWebasystIDClientManager();
        return [$cm->isBackendAuthForced(), $cm->isConnected()];
    }
}
