<?php

/** HTML form to manage user's or group's access rights for a single application. */
class contactsRightsAction extends waViewAction
{
    public function execute()
    {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }

        $contact_id = waRequest::get('id');
        $group_ids = null;
        if ($contact_id > 0) {
            $user_groups_model = new waUserGroupsModel();
            $group_ids = $user_groups_model->getGroupIds($contact_id);
            $group_ids[] = 0;
        }

        $app_id = waRequest::get('app');

        $right_model = new waContactRightsModel();
        $rights = $right_model->get($contact_id, $app_id, null, false);
        $group_rights = null;
        if ($group_ids) {
            $group_rights = $right_model->get(array_map(wa_lambda('$a', 'return -$a;'), $group_ids), $app_id, null, false);
        }

        // Check custom rights items
        $app_config = SystemConfig::getAppConfig($app_id);
        $class_name = $app_config->getPrefix()."RightConfig";
        $file_path = $app_config->getAppPath('lib/config/'.$class_name.".class.php");
        if (file_exists($file_path)) {
            // Init app
            waSystem::getInstance($app_id, $app_config);
            include($file_path);
            $right_config = new $class_name();
            $rights += $right_config->getRights($contact_id);

            if ($group_ids) {
                $group_rights += $right_config->getRights($group_ids);
            }

            $this->view->assign('html', $right_config->getHTML($rights, $group_rights));
            waSystem::setActive('contacts');
        }

        if ($contact_id > 0) {
            $this->view->assign('user', new waContact($contact_id));
        } else {
            $gm = new waGroupModel();
            $this->view->assign('group', $gm->getById(-$contact_id));
        }

        $app = wa()->getAppInfo($app_id);
        $app['id'] = $app_id;
        $this->view->assign('app', $app);
        $this->view->assign('rights', $rights);
        $this->view->assign('group_rights', $group_rights);
    }
}