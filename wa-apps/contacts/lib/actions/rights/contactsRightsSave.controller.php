<?php

/** All access rights save operations (for contacts and groups) go here. */
class contactsRightsSaveController extends waJsonController
{
    public function execute()
    {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }

        $app_id = waRequest::post('app_id');
        $name = waRequest::post('name');
        $value = (int)waRequest::post('value');
        $contact_id = waRequest::get('id');

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
        $this->response = true;
    }
}

// EOF