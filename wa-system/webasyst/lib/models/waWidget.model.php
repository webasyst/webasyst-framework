<?php

class waWidgetModel extends waModel
{
    protected $table = 'wa_widget';
    protected $id = 'id';

    public function getByContact($contact_id)
    {
        $sql = 'SELECT * FROM '.$this->table.' WHERE contact_id = i:0 ORDER BY block, sort';
        return $this->query($sql, $contact_id)->fetchAll('id');
    }

    public function add($data, $new_block = false)
    {
        $contact_id = wa()->getUser()->getId();

        if ($new_block) {
            $sql = 'UPDATE ' . $this->table . ' SET block = block + 1 WHERE contact_id = i:0 AND block >= i:1';
            $this->exec($sql, $contact_id, $data['block']);
        }

        $sql = 'UPDATE ' . $this->table . ' SET sort = sort + 1 WHERE contact_id = i:0 AND block = i:1 AND sort >= i:2';
        $this->exec($sql, $contact_id, $data['block'], $data['sort']);
        return $this->insert(array(
            'contact_id' => $contact_id,
            'app_id' => $data['app_id'],
            'widget' => $data['widget'],
            'name' => $data['name'],
            'block' => $data['block'],
            'sort' => $data['sort'],
            'size' => $data['size'],
            'create_datetime' => date('Y-m-d H:i:s')
        ));
    }


    public function move($w, $block, $sort, $new_block = false)
    {
        if (is_numeric($w)) {
            $w = $this->getById($w);
        }

        $count_widgets = $this->countByField(array('contact_id' => $w['contact_id'], 'block' => $w['block']));
        $delete_block = false;
        // remove block
        if ($count_widgets == 1) {
            $sql = 'UPDATE ' . $this->table . ' SET block = block - 1 WHERE contact_id = i:0 AND block > i:1';
            $this->exec($sql, $w['contact_id'], $w['block']);
            $delete_block = true;
        }

        // move within block
        if (($w['block'] == $block) && !$new_block && !$delete_block) {
            if ($w['sort'] < $sort) {
                $sql = 'UPDATE ' . $this->table . ' SET sort = sort - 1 WHERE contact_id = i:0 AND block = i:1 AND sort > i:2 AND sort <= i:3';
                $this->exec($sql, $w['contact_id'], $w['block'], $w['sort'], $sort);
            } else {
                $sql = 'UPDATE ' . $this->table . ' SET sort = sort + 1 WHERE contact_id = i:0 AND block = i:1 AND sort >= i:2 AND sort < i:3';
                $this->exec($sql, $w['contact_id'], $w['block'], $sort, $w['sort']);
            }
        } else {
            // move all next blocks
            if ($new_block) {
                $sql = 'UPDATE ' . $this->table . ' SET block = block + 1 WHERE contact_id = i:0 AND block >= i:1';
                $this->exec($sql, $w['contact_id'], $block);
            }
            // change sort in new block
            if (!$new_block) {
                $sql = 'UPDATE ' . $this->table . ' SET sort = sort + 1 WHERE contact_id = i:0 AND block = i:1 AND sort >= i:2';
                $this->exec($sql, $w['contact_id'], $block, $sort);
            }
            // change sort in old block
            if (!$delete_block) {
                $sql = 'UPDATE ' . $this->table . ' SET sort = sort - 1 WHERE contact_id = i:0 AND block = i:1 AND sort > i:2';
                $this->exec($sql, $w['contact_id'], $w['block'], $w['sort']);
            }
        }
        // update widget position
        $this->updateById($w['id'], array('block' => $block, 'sort' => $sort));
        return 1;
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

        $sql = 'UPDATE wa_widget SET sort = sort - 1 WHERE contact_id = i:0 AND block = i:1 AND sort > i:2';
        $this->exec($sql, $w['contact_id'], $w['block'], $w['sort']);

        $count_widgets = $this->countByField(array('contact_id' => $w['contact_id'], 'block' => $w['block']));
        if ($count_widgets == 1) {
            $sql = 'UPDATE ' . $this->table . ' SET block = block - 1 WHERE contact_id = i:0 AND block > i:1';
            $this->exec($sql, $w['contact_id'], $w['block']);
        }

        return $this->deleteById($id);
    }
}