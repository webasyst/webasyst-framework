<?php

/** Group access rights editor. */
class contactsGroupsRightsAction extends waViewAction
{
    public function execute()
    {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied');
        }

        if (! ( $group_id = (int)waRequest::get('id'))) {
            throw new waException('Group id not specified.');
        }

        $gm = new waGroupModel();
        $group = $gm->getById($group_id);

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

        $this->view->assign('apps', $apps);
        $this->view->assign('group', $group);
        $this->view->assign('noAccess', $noAccess);
        $this->view->assign('fullAccess', $fullAccess);
    }
}

// EOF