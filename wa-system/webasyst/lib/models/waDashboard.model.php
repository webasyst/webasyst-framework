<?php

class waDashboardModel extends waModel
{
    protected $table = 'wa_dashboard';
    protected $id = 'id';

    public function delete($id)
    {
        $sql = "DELETE w, wp
                FROM wa_widget AS w
                    LEFT JOIN wa_widget_params AS wp
                        ON w.id=wp.widget_id
                WHERE w.dashboard_id=?";
        $this->exec($sql, array($id));
        $this->deleteById($id);
    }
}
