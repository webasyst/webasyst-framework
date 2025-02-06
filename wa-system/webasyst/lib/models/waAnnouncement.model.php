<?php

class waAnnouncementModel extends waModel
{
    protected $table = 'wa_announcement';

    public function getByAppsBefore($contact_id, $apps, $limit = 15, $before_id = null)
    {
        $ignore_list = $this->getByApps($contact_id, $apps);
        $ignore_ids = array_column($ignore_list, 'id');

        $where = [];
        $params = [date('Y-m-d H:i:s')]; // is_unpublished in SELECT
        if ($ignore_ids) {
            $where[] = "id NOT IN (?)";
            $params[] = $ignore_ids;
        }
        if ($before_id) {
            $where[] = "id < ?";
            $params[] = $before_id;
        } else {
            $where[] = "(a.datetime <= ? OR (a.contact_id = ? AND a.app_id = 'webasyst' AND a.type = 'markdown'))";
            $params[] = date('Y-m-d H:i:s');
            $params[] = wa()->getUser()->getId();
        }

        $where[] = "(a.contact_id = ? OR a.access = 'all' OR ar.group_id IN (?))";
        $group_ids = (new waUserGroupsModel())->getGroupIds($contact_id);
        $group_ids[] = -$contact_id;
        $params[] = $contact_id;
        $params[] = $group_ids;

        $where = join("\n AND ", $where);

        $limit = (int) $limit;
        $limit = ifempty($limit, 15);

        $sql = "
            SELECT a.*, a.datetime > ? AS is_unpublished
            FROM {$this->table} AS a
                LEFT JOIN wa_announcement_rights AS ar
                    ON ar.announcement_id=a.id
            WHERE {$where}
            GROUP BY a.id
            ORDER BY a.id DESC
            LIMIT {$limit}
        ";
        return $this->query($sql, $params)->fetchAll();
    }

    public function getByApps($contact_id, $apps, $after_time = null)
    {
        $settings_model = new waContactSettingsModel();
        $data = $settings_model->getByField(array(
            'contact_id' => $contact_id,
            'name' => 'announcement_close',
            'app_id' => $apps
        ), true);
        $where = array();
        foreach ($data as $row) {
            if ($row['app_id'] === 'webasyst' && $row['value'] && $row['value'][0] == '{') {
                $value = @json_decode($row['value'], true);
                $general_datetime = null;
                $contact_ids = [];
                foreach (ifempty($value, []) as $cid => $datetime) {
                    if ($cid) {
                        $contact_ids[] = (int) $cid;
                        $conditions = [
                            "app_id = 'webasyst'",
                            "datetime > '".$this->escape($datetime)."'",
                            "contact_id = '".$this->escape($cid)."'",
                        ];
                        $where["webasyst_{$cid}"] = "(".join(' AND ', $conditions).")";
                    } else {
                        $general_datetime = $datetime;
                    }
                }

                $conditions = [
                    "app_id = 'webasyst'",
                ];
                if ($general_datetime) {
                    $conditions[] = "datetime > '".$this->escape($general_datetime)."'";
                }
                if ($contact_ids) {
                    $conditions[] = "(contact_id IS NULL OR contact_id NOT IN (".join(',', $contact_ids)."))";
                }
                $where["webasyst"] = "(".join(' AND ', $conditions).")";

            } else {
                $where[$row['app_id']] = "(app_id = '".$this->escape($row['app_id'])."' AND ".
                "datetime > '".$this->escape($row['value'])."')";
            }
        }
        foreach ($apps as $app_id) {
            if (!isset($where[$app_id])) {
                $where[$app_id] = "app_id = '".$this->escape($app_id)."'";
            }
        }

        $query_params = [
            date('Y-m-d H:i:s'), // is_unpublished in SELECT
        ];

        if ($where) {
            $where  = "(".implode("\nOR ", $where).")";
        } else {
            $where  = "1=1";
        }

        if ($after_time) {
            $where .= "\nAND datetime >= ?";
            $query_params[] = $after_time;
        }
        $where .= "\nAND (a.datetime <= ? OR (a.contact_id = ? AND a.app_id = 'webasyst' AND a.type = 'markdown'))";
        $query_params[] = date('Y-m-d H:i:s');
        $query_params[] = wa()->getUser()->getId();
        $where .= "\nAND (ttl_datetime IS NULL OR ttl_datetime > ?)";
        $query_params[] = date('Y-m-d H:i:s');

        // Global admin does not automatically see all announcements
        //if ((new waContactRightsModel())->get($contact_id, 'webasyst', 'backend') <= 0) {
            $where .= "\nAND (a.contact_id = ? OR a.access = 'all' OR ar.group_id IN (?))";
            $group_ids = (new waUserGroupsModel())->getGroupIds($contact_id);
            $group_ids[] = -$contact_id;
            $query_params[] = $contact_id;
            $query_params[] = $group_ids;
        //}

        $sql = "
            SELECT a.*, a.datetime > ? AS is_unpublished
            FROM {$this->table} AS a
                LEFT JOIN wa_announcement_rights AS ar
                    ON ar.announcement_id=a.id
            WHERE {$where}
            GROUP BY a.id
            ORDER BY is_pinned DESC, datetime DESC
        ";

        return $this->query($sql, $query_params)->fetchAll();
    }

    public function countByApps($app_ids)
    {
        if (!$app_ids) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE app_id IN (?) AND (`datetime` <= ? OR (`contact_id` = ? AND `app_id` = 'webasyst' AND `type` = 'markdown'))";
        return (int) $this->query($sql, [$app_ids, date('Y-m-d H:i:s'), wa()->getUser()->getId()])->fetchField();
    }
}