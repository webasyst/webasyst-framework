<?php

/**
 * Custom campaign parameters, name => value storage.
 */
class mailerMessageParamsModel extends waModel
{
    protected $table = 'mailer_message_params';

    public function getByMessage($id)
    {
        return $this->select('name,value')->where('message_id = ?', $id)->fetchAll('name', true);
    }

    public function save($id, $params, $delete=null)
    {
        // check sender id
        $id = (int)$id;
        if (!$id) {
            return false;
        }

        // delete old params
        if ($delete === null) {
            $this->deleteByMessage($id);
        } else {
            if (!is_array($delete)) {
                $delete = array($delete);
            }
            if (is_array($params)) {
                $delete = array_merge($delete, array_keys($params));
            }
            $this->deleteByField(array(
                'message_id' => $id,
                'name' => $delete,
            ));
        }

        if (!$params) {
            return true;
        }
        // save
        $data = array();
        foreach ($params as $k => $v) {
            if ($v) {
                $data[] = "(".$id.", '".$this->escape($k)."', '".$this->escape($v)."')";
            }
        }
        if ($data) {
            $sql = "INSERT INTO ".$this->table." (message_id, name, value) VALUES ".implode(', ', $data);
            return $this->exec($sql);
        }
        return true;
    }

    public function deleteByMessage($id)
    {
        $this->exec('DELETE FROM '.$this->table." WHERE message_id = i:id", array('id' => $id));
    }
}