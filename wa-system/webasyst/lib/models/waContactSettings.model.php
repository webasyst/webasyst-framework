<?php

class waContactSettingsModel extends waModel
{
    protected $table = 'wa_contact_settings';


    public function get($contact_id, $app_id)
    {
        $sql = "SELECT name, value
                FROM ".$this->table."
                WHERE contact_id = i:contact_id AND app_id = s:app_id";
        return $this->query($sql, array(
            'contact_id' => $contact_id, 'app_id' => $app_id
        ))->fetchAll('name', true);
    }

    public function getOne($contact_id, $app_id, $name)
    {
        $data = $this->getByField(array(
            'contact_id' => $contact_id,
            'app_id' => $app_id,
            'name' => $name
        ));
        if ($data) {
            return $data['value'];
        }
        return '';
    }

    public function set($contact_id, $app_id, $name, $value = null)
    {
        if (is_array($name) && $value === null) {
            $sql = "REPLACE INTO ".$this->table."
                    (contact_id, app_id, `name`, `value`) VALUES ";
            $contact_id = (int)$contact_id;
            $app_id = $this->escape($app_id);
            $f = false;
            foreach ($name as $k => $v) {
                if ($f) {
                    $sql .= ", ";
                } else {
                    $f = true;
                }
                $sql .= "(".$contact_id.", '".$app_id."', '".$this->escape($k)."', '".$this->escape($v)."')";
            }
            return $this->exec($sql);
        } else {
            return $this->replace(array(
                'contact_id' => $contact_id,
                'app_id' => $app_id,
                'name' => $name,
                'value' => $value
            ));
        }
    }

    public function delete($contact_id, $app_id, $name)
    {
        if (!$name) {
            return;
        }
        $sql = "DELETE FROM ".$this->table."
                WHERE contact_id = i:contact_id AND
                      app_id = s:app_id AND name IN (:name)";
        return $this->exec($sql, array(
            'contact_id' => $contact_id, 'app_id' => $app_id, 'name' => $name
        ));
    }
}
