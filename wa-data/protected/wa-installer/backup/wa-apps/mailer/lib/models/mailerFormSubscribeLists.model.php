<?php
/**
 * Model for m:n relation between forms and subscribe lists
 */

class mailerFormSubscribeListsModel extends waModel
{
    protected $table = 'mailer_form_subscribe_lists';
    protected $id = array('form_id', 'list_id');

    public function getListsIds($form_id) {
        $sql = "SELECT list_id FROM {$this->table}
                WHERE form_id = i:id";
        return array_keys($this->query($sql, array('id' => $form_id))->fetchAll('list_id'));
    }

    public function getForms($list_id)
    {
        $sql = "SELECT mf.id, mf.name FROM {$this->table} mfsl
                JOIN mailer_form mf ON mfsl.form_id = mf.id
                WHERE mfsl.list_id = i:id
                ORDER BY mf.create_datetime ASC";
        return $this->query($sql, array('id' => $list_id))->fetchAll('id');
    }

    public function getLists($form_id)
    {
        $sql = "SELECT msl.id, msl.name, msl.description, msl.create_datetime FROM {$this->table} mfsl
                JOIN mailer_subscribe_list msl ON mfsl.list_id = msl.id
                WHERE mfsl.form_id = i:id
                ORDER BY msl.create_datetime ASC";
        return $this->query($sql, array('id' => $form_id))->fetchAll('id');
    }

    public function getFormsIds($list_id)
    {
        return $this->getByField('list_id', $list_id, true);
    }

    /**
     * @param int $form_id Form ID
     * @param int|array $lists array of lists to insert/update for a given Form ID
     * @return bool|resource
     */
    public function updateByFormId($form_id, $lists)
    {
        if (!is_array($lists)) {
            $lists = (array)$lists;
        }
        $this->deleteByField('form_id', $form_id);
        if (is_null($lists)) {
            return true;
        }
        foreach($lists as &$list_id) {
            $list_id = array(
                'form_id' => $form_id,
                'list_id' => $list_id
            );
        }
        return $this->multipleInsert($lists);
    }

    /**
     * @param int $list_id List ID
     * @param int|array $forms array of forms to insert/update for a given List ID or deletes if null
     * @return bool|resource
     */
    public function updateByListId($list_id, $forms)
    {
        if (!is_array($forms)) {
            $forms = (array)$forms;
        }
        $this->deleteByField('list_id', $list_id);
        if (is_null($forms)) {
            return true;
        }

        foreach($forms as &$form_id) {
            $form_id = array(
                'form_id' => $form_id,
                'list_id' => $list_id
            );
        }
        return $this->multipleInsert($forms);
    }

    public function delete($contact_id, $group_id=null)
    {
        $where = array();
        if (is_array($contact_id)) {
            $where[] = "contact_id IN ('".implode("','", $this->escape($contact_id))."')";
        } else {
            $where[] = "contact_id = ".(int)$contact_id;
        }

        if ($group_id) {
            if (is_array($group_id)) {
                $where[] = "group_id IN ('".implode("','", $this->escape($group_id))."')";
            } else {
                $where[] = "group_id = ".(int)$group_id;
            }
        }

        if ($where) {
            $sql = "DELETE FROM ".$this->table." WHERE ".implode(" AND ", $where);
            return $this->exec($sql);
        }
        return true;
    }
} 