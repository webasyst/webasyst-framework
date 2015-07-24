<?php

class waWidgetSettingsModel extends waModel
{
    protected $table = 'wa_widget_params';
    protected $id = array('widget_id', 'name');

    public function get($widget_id)
    {
        $sql = 'SELECT name, value FROM '. $this->table.' WHERE widget_id = i:0';
        return $this->query($sql, $widget_id)->fetchAll('name', true);
    }

    public function set($widget_id, $name, $value)
    {
        return $this->insert(array('widget_id' => $widget_id, 'name' => $name, 'value' => $value), 1);
    }

    public function del($widget_id, $name)
    {
        return $this->deleteByField(array('widget_id' => $widget_id, 'name' => $name));
    }
}