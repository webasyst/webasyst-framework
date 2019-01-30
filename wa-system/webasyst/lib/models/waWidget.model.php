<?php

class waWidgetModel extends waModel
{
    protected $table = 'wa_widget';
    protected $id = 'id';

    public function getByContact($contact_id)
    {
        $sql = 'SELECT *
                FROM '.$this->table.'
                WHERE contact_id = i:0
                    AND dashboard_id IS NULL
                ORDER BY block, sort';
        return $this->query($sql, $contact_id)->fetchAll('id');
    }

    public function getByDashboard($dashboard_id)
    {
        $sql = 'SELECT *
                FROM '.$this->table.'
                WHERE dashboard_id = i:0
                ORDER BY block, sort';
        return $this->query($sql, (int) $dashboard_id)->fetchAll('id');
    }

    public function add($data, $new_block = false, $dashboard_id = null)
    {
        $contact_id = wa()->getUser()->getId();

        if ($new_block) {
            $sql = 'UPDATE ' . $this->table . '
                    SET block = block + 1
                    WHERE contact_id = i:0
                        AND dashboard_id <=> :2
                        AND block >= i:1';
            $this->exec($sql, $contact_id, $data['block'], $dashboard_id);
        }

        $sql = 'UPDATE ' . $this->table . '
                SET sort = sort + 1
                WHERE contact_id = i:0
                    AND dashboard_id <=> :3
                    AND block = i:1
                    AND sort >= i:2';
        $this->exec($sql, $contact_id, $data['block'], $data['sort'], $dashboard_id);
        return $this->insert(array(
            'dashboard_id' => $dashboard_id,
            'contact_id' => $contact_id,
            'app_id' => $data['app_id'],
            'widget' => $data['widget'],
            'name' => $data['name'],
            'block' => $data['block'],
            'sort' => $data['sort'],
            'size' => $data['size'],
            'create_datetime' => date('Y-m-d H:i:s'),
        ));
    }


    public function move($w, $block, $sort, $new_block = false)
    {
        if (is_numeric($w)) {
            $w = $this->getById($w);
        }

        $count_widgets = $this->countByField(array(
            'dashboard_id' => $w['dashboard_id'],
            'contact_id' => $w['contact_id'],
            'block' => $w['block'],
        ));
        $delete_block = false;
        // remove block
        if ($count_widgets == 1) {
            $sql = 'UPDATE ' . $this->table . '
                    SET block = block - 1
                    WHERE contact_id = i:0
                        AND dashboard_id <=> :2
                        AND block > i:1';
            $this->exec($sql, $w['contact_id'], $w['block'], $w['dashboard_id']);
            $delete_block = true;
        }

        // move within block
        if (($w['block'] == $block) && !$new_block && !$delete_block) {
            if ($w['sort'] < $sort) {
                $sql = 'UPDATE ' . $this->table . '
                        SET sort = sort - 1
                        WHERE contact_id = i:0
                            AND dashboard_id <=> :4
                            AND block = i:1
                            AND sort > i:2
                            AND sort <= i:3';
                $this->exec($sql, $w['contact_id'], $w['block'], $w['sort'], $sort, $w['dashboard_id']);
            } else {
                $sql = 'UPDATE ' . $this->table . '
                        SET sort = sort + 1
                        WHERE contact_id = i:0
                            AND dashboard_id <=> :4
                            AND block = i:1
                            AND sort >= i:2
                            AND sort < i:3';
                $this->exec($sql, $w['contact_id'], $w['block'], $sort, $w['sort'], $w['dashboard_id']);
            }
        } else {
            // move all next blocks
            if ($new_block) {
                $sql = 'UPDATE ' . $this->table . '
                        SET block = block + 1
                        WHERE contact_id = i:0
                            AND dashboard_id <=> :2
                            AND block >= i:1';
                $this->exec($sql, $w['contact_id'], $block, $w['dashboard_id']);
            }
            // change sort in new block
            if (!$new_block) {
                $sql = 'UPDATE ' . $this->table . '
                        SET sort = sort + 1
                        WHERE contact_id = i:0
                            AND dashboard_id <=> :3
                            AND block = i:1
                            AND sort >= i:2';
                $this->exec($sql, $w['contact_id'], $block, $sort, $w['dashboard_id']);
            }
            // change sort in old block
            if (!$delete_block) {
                $sql = 'UPDATE ' . $this->table . '
                        SET sort = sort - 1
                        WHERE contact_id = i:0
                            AND dashboard_id <=> :3
                            AND block = i:1
                            AND sort > i:2';
                $this->exec($sql, $w['contact_id'], $w['block'], $w['sort'], $w['dashboard_id']);
            }
        }
        // update widget position
        $this->updateById($w['id'], array(
            'block' => $block,
            'sort' => $sort,
        ));
        return array(
            'block' => $block,
            'sort' => $sort
        );
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
        $w = $this->getById($id);
        if (!$w) {
            return true;
        }

        $sql = "DELETE FROM wa_widget_params WHERE widget_id = i:id";
        $this->exec($sql, array('id' => $id));

        $sql = 'UPDATE wa_widget
                SET sort = sort - 1
                WHERE contact_id = i:0
                    AND dashboard_id <=> :3
                    AND block = i:1
                    AND sort > i:2';
        $this->exec($sql, $w['contact_id'], $w['block'], $w['sort'], $w['dashboard_id']);

        $count_widgets = $this->countByField(array(
            'dashboard_id' => $w['dashboard_id'],
            'contact_id' => $w['contact_id'],
            'block' => $w['block'],
        ));
        if ($count_widgets == 1) {
            $sql = 'UPDATE ' . $this->table . '
                    SET block = block - 1
                    WHERE contact_id = i:0
                        AND dashboard_id <=> :2
                        AND block > i:1';
            $this->exec($sql, $w['contact_id'], $w['block'], $w['dashboard_id']);
        }

        return $this->deleteById($id);
    }

    public function deleteByWidget($app_id, $widget)
    {
        // delete from params
        $sql = "DELETE p
                FROM wa_widget_params p
                    JOIN wa_widget w
                        ON p.widget_id = w.id
                WHERE w.app_id = s:0
                    AND w.widget = s:1";
        $this->exec($sql, $app_id, $widget);

        // fix sort
        $sql = "SELECT w.id
                FROM wa_widget w
                    JOIN wa_widget w2
                        ON w.contact_id = w2.contact_id
                            AND w.dashboard_id <=> w2.dashboard_id
                            AND w.block = w2.block
                            AND w.app_id != w2.app_id
                            AND w.widget != w2.widget
                WHERE w2.app_id = s:0
                    AND w2.widget = s:1
                    AND w.sort > w2.sort";
        $ids = $this->query($sql, $app_id, $widget)->fetchAll(null, true);
        if ($ids) {
            $sql = "UPDATE ".$this->table." SET sort = sort - 1 WHERE id IN (i:ids)";
            $this->exec($sql, array('ids' => $ids));
        }
        // fix block
        $sql = "SELECT w.*
                FROM `wa_widget` w
                    LEFT JOIN wa_widget w2
                        ON w.contact_id = w2.contact_id
                            AND w.dashboard_id <=> w2.dashboard_id
                            AND w.block = w2.block
                            AND w.app_id != w2.app_id
                            AND w.widget != w2.widget
                WHERE w.app_id = s:0
                    AND w.widget = s:1
                    AND w.sort = 0
                    AND w2.id IS NULL
                ORDER BY block DESC";
        $rows = $this->query($sql, $app_id, $widget);
        foreach ($rows as $row) {
            $sql = "UPDATE ".$this->table."
                    SET block = block - 1
                    WHERE contact_id = i:0
                        AND dashboard_id <=> :2
                        AND block > i:1";
            $this->exec($sql, $row['contact_id'], $row['block'], $row['dashboard_id']);
        }
        // delete widgets
        return $this->deleteByField(array('app_id' => $app_id, 'widget' => $widget));
    }
}