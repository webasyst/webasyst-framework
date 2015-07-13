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

    public function getLogs($where = array())
    {
        $where_string = "l.action != 'login' AND l.action != 'logout'";
        if (!empty($where['max_id'])) {
            $where_string .= ' AND l.id < '.(int)$where['max_id'];
            unset($where['max_id']);
        }
        if (!empty($where['min_id'])) {
            $where_string .= ' AND l.id > '.(int)$where['min_id'];
            unset($where['min_id']);
        }
        $where = array_intersect_key($where, $this->getMetadata());
        if ($where) {
            $where_string .= ' AND ('.$this->getWhereByField($where).')';
        }
        $sql = "SELECT l.*, c.name contact_name, c.photo contact_photo, c.firstname, c.lastname, c.middlename,
c.company, c.is_company, c.is_user
                FROM ".$this->table." l
                LEFT JOIN wa_contact c ON l.contact_id = c.id
                WHERE ".$where_string."
                ORDER BY l.id DESC
                LIMIT 50";
        return $this->query($sql)->fetchAll();
    }
}