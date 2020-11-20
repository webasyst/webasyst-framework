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

        $groups = $locations = array();
        foreach ($all_groups as $id => $g) {
            if (teamHelper::hasRights('manage_group.'.$g['id'])) {
                $groups[$id] = $g;
            }
        }
        foreach ($all_locations as $id => $g) {
            if (teamHelper::hasRights('manage_group.'.$g['id'])) {
                $locations[$id] = $g;
            }
        }

        $this->view->assign(array(
            'groups'    => $groups,
            'locations' => $locations,
            'is_waid_forced' => $this->isBackendAuthForced()
        ));
    }

    protected function isBackendAuthForced()
    {
        $cm = new waWebasystIDClientManager();
        return $cm->isBackendAuthForced();
    }
}
