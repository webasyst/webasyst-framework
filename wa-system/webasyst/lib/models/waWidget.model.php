<?php

class waWidgetModel extends waModel
{
    protected $table = 'wa_widget';
    protected $id = 'id';

    public function add($name, $code)
    {
        $data = array(
            'name' => $name,
            'app_id' => waSystem::getInstance()->getApp(),
            'locale' => waSystem::getInstance()->getLocale(),
            'code' => $code,
            'create_contact_id' => waSystem::getInstance()->getUser()->getId(),
            'create_datetime' => date("Y-m-d H:i:s")
        );
        return $this->insert($data);
    }

    public function getByCode($code)
    {
        return $this->getByField('code', $code);
    }

    public function getByApp($app_id)
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE app_id = s:app_id
                ORDER BY name";
        return $this->query($sql, array('app_id' => $app_id))->fetchAll();
    }


    public function getParams($id)
    {
        $sql = "SELECT name, value FROM wa_widget_params WHERE widget_id = i:id";
        return $this->query($sql, array('id' => $id))->fetchAll('name', true);
    }

    public function setParams($id, $params)
    {
        $values = array();
        foreach ($params as $n => $v) {
            $values [] = "(".$id.", '".$this->escape($n)."', '".$this->escape($v)."')";
        }
        if ($values) {
            $sql = "REPLACE INTO wa_widget_params
                    (widget_id, name, value) VALUES ";
            $sql .= implode(", ", $values);
            return $this->exec($sql);
        }
        return true;
    }

    public function delete($id)
    {
        $sql = "DELETE FROM wa_widget_params WHERE widget_id = i:id";
        $this->exec($sql, array('id' => $id));
        return $this->deleteById($id);
    }
}