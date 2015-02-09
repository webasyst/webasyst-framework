<?php

class waLogModel extends waModel
{
    protected $table = "wa_log";

    public function add($action, $params = null, $subject_contact_id = null, $contact_id = null)
    {
        /**
         * @var waSystem
         */
        $system = waSystem::getInstance();
        /**
         * @var waAppConfig
         */
        $config = $system->getConfig();
        if ($config instanceof waAppConfig) {
            // Get actions of current application available to log
            $actions = $config->getLogActions();
            // Check action
            if (!isset($actions[$action])) {
                if (waSystemConfig::isDebug()) {
                    throw new waException('Unknown action for log '.$action);
                } else {
                    return false;
                }
            }
            if ($actions[$action] === false) {
                return false;
            }
            $app_id = $system->getApp();
        } else {
            $app_id = 'wa-system';
        }
        // Save to database
        $data = array(
            'app_id' => $app_id,
            'contact_id' => $contact_id === null ? $system->getUser()->getId() : $contact_id,
            'datetime' => date("Y-m-d H:i:s"),
            'action' => $action,
            'subject_contact_id' => $subject_contact_id
        );

        if ($params !== null) {
            if (is_array($params)) {
                $params = json_encode($params);
            }
            $data['params'] = $params;
        }
        return $this->insert($data);
    }
}