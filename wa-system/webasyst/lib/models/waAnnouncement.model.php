<?php 

class waAnnouncementModel extends waModel
{
    protected $table = 'wa_announcement';

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
            $where[$row['app_id']] = "(app_id = '".$this->escape($row['app_id'])."' AND ".
                                     "datetime > '".$this->escape($row['value'])."')";
        }
        foreach ($apps as $app_id) {
            if (!isset($where[$app_id])) {
                $where[$app_id] = "app_id = '".$this->escape($app_id)."'";
            }
        }
        if ($where) {
            $where  = "(".implode(" OR ", $where).")";
        } else {
            $where = "";
        }
        $sql = "SELECT * FROM ".$this->table;
        if ($after_time) {
            if ($where) {
                $where .= " AND ";
            }
            $where .= "datetime >= '".$this->escape($after_time)."'";
        }
        if ($where) {
            $sql .= " WHERE ".$where;
        }
        $sql .= " ORDER BY datetime DESC";
        return $this->query($sql)->fetchAll();
    }
}