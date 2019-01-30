<?php
/**
 * Dialog that allows to select no access/limited access/full access
 * for given app and given user or group.
 */
class teamAccessDialogAction extends waViewAction
{
    public function execute()
    {
        // Only allowed for global admin
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }

        $contact_id = waRequest::request('user_id', 0, 'int');
        $app_id = waRequest::post("app_id", null, 'string');

        // User or group
        $user = $group = null;
        if ($contact_id > 0) {
            $user = new waContact($contact_id);
            $user->getName(); // 404 if not exists
        } elseif ($contact_id < 0) {
            $gm = new waGroupModel();
            $group = $gm->getById(-$contact_id);
        }

        // Sanity check
        if ((!$user && !$group) || !$app_id || !wa()->appExists($app_id)) {
            throw new waException('', 404);
        }

        // App
        $app = wa()->getAppInfo($app_id);

        $this->view->assign(array(
            "app" => $app,
            "user" => $user,
            "group" => $group,
            "contact_id" => $contact_id,
            "access_levels" => self::getAccessLevels($app, $contact_id),
        ));
    }

    protected static function getAccessLevels($app, $contact_id)
    {
        $result = teamHelper::getAccessTypes();

        // Groups of a user
        $group_ids = array();
        if ($contact_id > 0) {
            $user_groups_model = new waUserGroupsModel();
            $group_ids = $user_groups_model->getGroupIds($contact_id);
            $group_ids = array_map(wa_lambda('$a', 'return -$a;'), $group_ids);
            $group_ids[] = 0;
        }

        // Basic rights stored inside wa_contact_rights
        $right_model = new waContactRightsModel();
        $rights = $right_model->get($contact_id, $app['id'], null, false);
        $group_rights = null;
        if ($group_ids) {
            $group_rights = $right_model->get($group_ids, $app['id'], null, false);
        }

        // Which access level is active?
        if (empty($rights['backend']) && empty($group_rights['backend'])) {
            $result['no']['is_active'] = true;
        } elseif ($rights['backend'] > 1 || $group_rights['backend'] > 1) {
            $result['full']['is_active'] = true;
        } else {
            $result['limited']['is_active'] = true;
        }

        // Some access levels can be disabled if inherited from groups
        if (!empty($group_rights['backend'])) {
            $result['no']['is_disabled'] = _w('This access level is inherited from groups. To change it, please adjust group settings or edit group membership for this user.');
            if ($group_rights['backend'] > 1) {
                $result['limited']['is_disabled'] = _w('This access level is inherited from groups. To change it, please adjust group settings or edit group membership for this user.');
            }
        }

        // Respect app's custom rights config
        $class_name = wa($app['id'])->getConfig()->getPrefix()."RightConfig";
        if (!empty($app['rights']) && class_exists($class_name)) {
            // Enable app's localization
            wa($app['id'], 1);
            $right_config = new $class_name();

            if (!empty($rights['backend']) && $rights['backend'] == 1) {
                // Custom rigths stored inside app
                $rights += $right_config->getRights($contact_id);
            } else {
                // Default rights for a new contact
                $rights += $right_config->getDefaultRights($contact_id);
            }

            if ($group_ids) {
                // Custom rigths stored inside app
                $group_rights += $right_config->getRights($group_ids);
            }

            // Prepare the result
            $result['limited']['custom_html_form'] = $right_config->getHTML($rights, $group_rights);

            // Return active app back
            wa('team', 1);
        } else {
            unset($result['limited']);
        }

        return $result;
    }
}
