<?php

/** Group editor: edit name and a set of group members. */
class contactsGroupsEditorAction extends waViewAction
{
    public function execute() {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }

        $group = null;
        $group_id = waRequest::get('id');
        if ($group_id) {
            $group_model = new waGroupModel();
            $group = $group_model->getById($group_id);
        }
        
        // only allowed to global admin
        $is_global_admin = wa()->getUser()->getRights('webasyst', 'backend');

        $right_model = new waContactRightsModel();
        $fullAccess = $right_model->get(-$group_id, 'webasyst', 'backend');
        $apps = wa()->getApps();
        if(!$fullAccess) {
            $appAccess = $right_model->getApps($group_id, 'backend');
        }
        $noAccess = true;
        foreach($apps as $app_id => &$app) {
            $app['id'] = $app_id;
            $app['customizable'] = isset($app['rights']) ? (boolean) $app['rights'] : false;
            $app['access'] = $fullAccess ? 2 : 0;
            if (!$app['access'] && isset($appAccess[$app_id])) {
                $app['access'] = $appAccess[$app_id];
            }
            $noAccess = $noAccess && !$app['access'];
        }
        unset($app);

        $user_groups = new waUserGroupsModel();
        $users_count = $user_groups->countByField(array(
            'group_id' => $group_id
        ));
        $this->view->assign('users_count', $users_count);
        
        $this->view->assign('apps', $apps);
        $this->view->assign('noAccess', $noAccess);
        $this->view->assign('fullAccess', $fullAccess);
        $this->view->assign('is_global_admin', $is_global_admin);
        
        $this->view->assign('group', $group);
        $this->view->assign('icons', waGroupModel::getIcons());
    }

    function _cmp($a, $b)
    {
        return strcmp($a['name'], $b['name']);
    }
}

// EOF