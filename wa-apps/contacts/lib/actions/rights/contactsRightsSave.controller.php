<?php

/** All access rights save operations (for contacts and groups) go here. */
class contactsRightsSaveController extends waJsonController
{
    public function execute()
    {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException(_w('Access denied'));
        }

        if (waRequest::request('only_is_user')) {
            $contact_id = waRequest::request('id', null, 'int');
            if ($contact_id) {
                $is_user = waRequest::request('is_user', null, 'int');
                if ($is_user === 0 || $is_user === 1 || $is_user === -1) {
                    $contact = new waContact($contact_id);
                    $contact->save(array(
                        'is_user' => $is_user
                    ));
                    $this->response['access_disable_msg'] = contactsHelper::getAccessDisableMsg($contact);
                }
            }
            return;
        }
        
        $app_id = waRequest::post('app_id');
        $name = waRequest::post('name');
        $value = (int)waRequest::post('value');
        $contact_id = waRequest::get('id');

        $has_backend_access_old = $this->hasBackendAccess($contact_id);
        
        if (!$name && !$value) {
            $values = waRequest::post('app');
            if (!is_array($values)) {
                throw new waException('Bad values for access rights.');
            }
        } else {
            $values = array($name => $value);
        }

        $right_model = new waContactRightsModel();
        $is_admin = $right_model->get($contact_id, 'webasyst', 'backend', false);
        
        if ($is_admin && $app_id != 'webasyst') {
            throw new waException('Cannot change application rights for global admin.');
        }
        
        // If $contact_id used to have limited access and we're changing global admin privileges,
        // then need to notify all applications to remove their custom access records.
        if (!$is_admin && $app_id == 'webasyst' && $name == 'backend') {
            foreach(wa()->getApps() as $aid => $app) {
                try {
                    if (isset($app['rights']) && $app['rights']) {
                        $app_config = SystemConfig::getAppConfig($aid);
                        $class_name = $app_config->getPrefix()."RightConfig";
                        $file_path = $app_config->getAppPath('lib/config/'.$class_name.".class.php");
                        $right_config = null;
                        if (!file_exists($file_path)) {
                            continue;
                        }
                        waSystem::getInstance($aid, $app_config);
                        include_once($file_path);
                        /**
                         * @var waRightConfig
                         */
                        $right_config = new $class_name();
                        $right_config->clearRights($contact_id);
                    }
                } catch (Exception $e) {
                    // silently ignore other applications errors
                }
            }
        }

        // Update $app_id access records
        $app_config = SystemConfig::getAppConfig($app_id);
        $class_name = $app_config->getPrefix()."RightConfig";
        $file_path = $app_config->getAppPath('lib/config/'.$class_name.".class.php");
        $right_config = null;
        if (file_exists($file_path)) {
            // Init app
            waSystem::getInstance($app_id, $app_config);
            include_once($file_path);
            /**
             * @var waRightConfig
             */
            $right_config = new $class_name();
        }
        
        foreach($values as $name => $value) {
            if ($right_config && $right_config->setRights($contact_id, $name, $value)) {
                // If we've got response from custom rights config, then no need to update main rights table
                continue;
            }

            // Set default limited rights
            if ($right_config && $name == 'backend' && $value == 1) {
                /**
                 * @var $right_config waRightConfig
                 */
                foreach($right_config->setDefaultRights($contact_id) as $n => $v) {
                    $right_model->save($contact_id, $app_id, $n, $v);
                }
            }
            $right_model->save($contact_id, $app_id, $name, $value);
        }

        waSystem::setActive('contacts');

        if ($contact_id) {
            // TODO: use waContact method for disabling
            $is_user = waRequest::post('is_user', null, 'int');
            if ($is_user === -1 || $is_user === 0 || $is_user === 1) {
                $contact = new waContact($contact_id);
                $contact->save(array(
                    'is_user' => $is_user
                ));
                $this->response['access_disable_msg'] = contactsHelper::getAccessDisableMsg($contact);
            }
        }
        
        $has_backend_access_new = $this->hasBackendAccess($contact_id);
        if ($has_backend_access_new !== $has_backend_access_old) {
            if ($has_backend_access_new) {
                $this->logAction("grant_backend_access", null, $contact_id);
            } else {
                $this->logAction("revoke_backend_access", null, $contact_id);
            }
        }
        
    }
    
    public function hasBackendAccess($contact_id)
    {
        $ugm = new waUserGroupsModel();
        $rm = new waContactRightsModel();
        $ownAccess = $rm->getApps(-$contact_id, 'backend', FALSE, FALSE);
        if(!isset($ownAccess['webasyst'])) {
            $ownAccess['webasyst'] = 0;
        }
        $groups = $ugm->getGroups($contact_id);
        $groupAccess = $rm->getApps(array_keys($groups), 'backend', FALSE, FALSE);
        if(!isset($groupAccess['webasyst'])) {
            $groupAccess['webasyst'] = 0;
        }
        
        $system = waSystem::getInstance();
        $apps = $system->getApps();
        $noAccess = true;
        $gNoAccess = true;
        foreach($apps as $app_id => &$app) {
            $app['id'] = $app_id;
            $app['customizable'] = isset($app['rights']) ? (boolean) $app['rights'] : false;
            $app['access'] = $ownAccess['webasyst'] ? 2 : 0;
            if (!$app['access'] && isset($ownAccess[$app_id])) {
                $app['access'] = $ownAccess[$app_id];
            }
            $app['gaccess'] = $groupAccess['webasyst'] ? 2 : 0;
            if (!$app['gaccess'] && isset($groupAccess[$app_id])) {
                $app['gaccess'] = $groupAccess[$app_id];
            }
            $noAccess = $noAccess && !$app['gaccess'] && !$app['access'];
            $gNoAccess = $gNoAccess && !$app['gaccess'];
        }
        unset($app);
        
        return $ownAccess['webasyst'] || !$noAccess;
        
    }
    
}

// EOF