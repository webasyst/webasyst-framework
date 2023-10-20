<?php

class teamSidebarAction extends waViewAction
{
    public function execute()
    {
        $groups = teamHelper::getVisibleGroups();
        $locations = teamHelper::getVisibleLocations();
        $last = date('Y-m-d H:i:s', time() - waUser::getOption('online_timeout'));

        $all_users = teamUser::getList('users/active_and_banned', array(
            'fields' => 'id,is_user,_online_status',
        ));
        $active_users = array_filter($all_users, wa_lambda('$u', 'return $u["is_user"] > 0;'));
        $online_users = array_filter($all_users, wa_lambda('$u', 'return $u["_online_status"] === "online";'));

        $all_count = count($active_users);
        $online_count = count($online_users);
        $inactive_count = count($all_users) - $all_count;

        // Has to come before other assigns because it messes up with smarty vars
        $this->view->assign('backend_sidebar', $this->pluginHook($groups, $locations));

        $this->view->assign(array(
            'groups' => $groups,
            'locations' => $locations,
            'all_count' => $all_count,
            'online_count' => $online_count,
            'invited_count' => count(teamUsersInvitedAction::getInvited()),
            'inactive_count' => $inactive_count,
            'noaccess_count' => $this->countNoaccessUsers(),
        ));
        if(wa('team')->whichUI('team') === '1.3'){
            $this->setTemplate('templates/actions-legacy/Sidebar.html');
        }else{
            $is_reload = waRequest::get('is_reload', 0, waRequest::TYPE_INT);
            $this->view->assign(['is_reload' => $is_reload]);
            $this->setTemplate('templates/actions/Sidebar.html');
        }
    }

    protected function pluginHook($groups, $locations)
    {
        $event_params = array();
        return wa()->event('backend_sidebar', $event_params, array(
            'top_li', 'section', 'bottom_li',
        ));
    }

    protected function countNoaccessUsers()
    {
        $cm = new waContactModel();
        return (int) $cm->select('COUNT(id)')->where('is_staff > 0 AND is_user = 0')->fetchField();
    }
}
