<?php

class webasystDashboardActivityAction extends waViewAction
{
    public function execute()
    {
        wa()->getStorage()->close();
        $filters = waRequest::post();
        if (waRequest::post('save_filters')) {
            unset($filters['save_filters']);
            wa()->getUser()->setSettings('webasyst', 'dashboard_activity', waRequest::post('app_id'));
        }
        $logs  = $this->getLogs($filters, $count);
        $this->view->assign('activity', $logs);
        if ($logs && waRequest::isXMLHttpRequest()) {
            $row = reset($logs);
            $this->view->assign('datetime_group', $this->getDatetimeGroup($row['datetime']));
        }
        if ($count == 50) {
            $this->view->assign('activity_load_more', true);
        }
    }

    public function getLogs($filters = array(), &$count = null)
    {
        $log_model = new waLogModel();
        $apps = wa()->getUser()->getApps();
        if (!isset($filters['app_id']) || !is_array($filters['app_id'])) {
            $user_filter = wa()->getUser()->getSettings('webasyst', 'dashboard_activity');
            if ($user_filter) {
                $filters['app_id'] = explode(',', $user_filter);
            }
        }
        if (!$this->getUser()->isAdmin()) {
            if (!empty($filters['app_id'])) {
                $filters['app_id'] = array_keys(array_intersect_key(array_flip($filters['app_id']), $apps));
            } else {
                $filters['app_id'] = array_keys($apps);
            }
        }
        $rows = $log_model->getLogs($filters);
        $count = count($rows);
        $apps = wa()->getApps(true);
        $apps_rows = array();
        $prev = array();
        foreach ($rows as $row_id => &$row) {
            if ($prev) {
                $flag = true;
                foreach (array('app_id', 'action', 'contact_id', 'subject_contact_id', 'params') as $k) {
                    if ($prev[$k] != $row[$k]) {
                        $flag = false;
                        break;
                    }
                }
                if ($flag) {
                    unset($rows[$row_id]);
                    continue;
                }
            }
            $contact_name = waContactNameField::formatName($row);
            if ($contact_name) {
                $row['contact_name'] = $contact_name;
            }
            if ($row['is_user']) {
                $row['contact_photo_url'] = waContact::getPhotoUrl($row['contact_id'], $row['contact_photo'], 32, 32);
            }
            $row['datetime_group'] = $this->getDatetimeGroup($row['datetime']);
            if (!empty($apps[$row['app_id']])) {
                $row['app'] = $apps[$row['app_id']];
                $logs = wa($row['app_id'])->getConfig()->getLogActions(true);
                $row['action_name'] = ifset($logs[$row['action']]['name'], $row['action']);
                if (strpos($row['action'], 'del')) {
                    $row['type'] = 4;
                } elseif (strpos($row['action'], 'add')) {
                    $row['type'] = 3;
                } else {
                    $row['type'] = 1;
                }
                $apps_rows[$row['app_id']][$row_id] = $row;
            } else {
                $row['app'] = array(
                    'name' => $row['app_id'],
                );
                $row['action_name'] = $row['action'];
                $row['type'] = 1;
            }

            $prev = $row;
            unset($row);
        }
        foreach ($apps_rows as $app_id => $app_rows) {
            $app_rows = wa($app_id)->getConfig()->explainLogs($app_rows);
            foreach ($app_rows as $row_id => $row) {
                if ($row) {
                    $rows[$row_id] = $row;
                } else {
                    unset($rows[$row_id]);
                }
            }
        }
        return $rows;
    }

    protected function getDatetimeGroup($datetime)
    {
        $ts = strtotime($datetime);
        if (date('Y-m-d') == date('Y-m-d', $ts)) {
            return '';
        } elseif (date('Y-m-d', $ts) == date('Y-m-d', strtotime('-1 day'))) {
            return _ws('Yesterday');
        } elseif ($ts > time() - 7 * 86400) {
            return _ws('Earlier');
        } elseif ($ts > time() - 30 * 86400) {
            return _ws('7 Days');
        } elseif ($ts > time() - 90 * 86400) {
            return _ws('30 Days');
        } elseif ($ts > time() - 365 * 86400) {
            return _ws('90 Days');
        } else {
            return _ws('365 Days');
        }
    }
}
