<?php

class waUserGroupsModel extends waModel
{
    protected $table = 'wa_user_groups';
    protected $id = array('contact_id', 'group_id');

    public function getContactIds($group_id) {
        $sql = "SELECT contact_id FROM ".$this->table."
                WHERE group_id = ".(int)$group_id;
        return array_keys($this->query($sql)->fetchAll('contact_id'));
    }

    public function getGroups($contact_id)
    {
        $sql = "SELECT g.id, g.name FROM ".$this->table." cg
                JOIN wa_group g ON cg.group_id = g.id
                WHERE cg.contact_id = i:id
                ORDER BY g.name";
        return $this->query($sql, array('id' => $contact_id))->fetchAll('id', true);
    }

    public function getGroupIds($contact_id)
    {
        // Caching here saves plenty of queries for access right checking
        static $last_contact_id = null;
        static $last_user_groups = null;
        if (!is_array($contact_id) && (int) $contact_id === $last_contact_id) {
            return $last_user_groups;
        }
        $sql = "SELECT group_id FROM ".$this->table."
                WHERE ".(is_array($contact_id) ? "contact_id IN (i:id)" : "contact_id=i:id");
        $result = array_keys($this->query($sql, array('id' => $contact_id))->fetchAll('group_id'));
        if (!is_array($contact_id)) {
            $last_contact_id = (int) $contact_id;
            $last_user_groups = $result;
        }
        return $result;
    }

    public function add($data, $group_id=null)
    {
        if (!is_array($data)) {
            $data = array(
                array((int) $data, (int) $group_id),
            );
        }

        $values = array();
        $group_ids = array();
        $datetime = date('Y-m-d H:i:s');
        foreach ($data as $row) {
            $contact_id = (int) $row[0];
            $group_id = (int) $row[1];
            if ($contact_id && $group_id) {
                $values[] = "({$contact_id},{$group_id},'{$datetime}')";
                $group_ids[$group_id] = $group_id;
            }
        }
        if ($values) {
            $sql = "INSERT IGNORE INTO ".$this->table." (contact_id, group_id, datetime) VALUES ";
            $sql .= implode(",", $values);
            $result = $this->exec($sql);
            $m = new waGroupModel();
            $m->updateCounts($group_ids);
            return $result;
        }

        return true;
    }

    public function emptyGroup($group_id) {
        $sql = "DELETE FROM `{$this->table}` WHERE group_id=".((int)$group_id);
        $this->exec($sql);

        $m = new waGroupModel();
        $m->updateCount($group_id, 0);
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
            $result = $this->exec($sql);
            $m = new waGroupModel();
            $m->updateCounts(ifempty($group_id));
            return $result;
        }
        return true;
    }

    /**
     * @param $contact_id
     * @param $group_id
     * @return boolean true if user belongs to group, false otherwise
     */
    public function isMember($contact_id, $group_id)
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE contact_id=i:cid AND group_id=i:gid LIMIT 1";
        return (bool) $this->query($sql, array('cid' => $contact_id, 'gid' => $group_id))->fetchField();
    }

    /**
      * @param array $contacts list of user ids
      * @return array user_id => array(group_id, group_id, ...)
      */
    public function getGroupIdsForUsers($contacts) {
        if (!$contacts) {
            return array();
        }
        $sql = "SELECT contact_id, group_id
                FROM ".$this->table."
                WHERE contact_id IN (i:ids)";
        $result = array();
        foreach($this->query($sql, array('ids' => $contacts)) as $row) {
            if (!isset($result[$row['contact_id']])) {
                $result[$row['contact_id']] = array();
            }
            $result[$row['contact_id']][] = $row['group_id'];
        }
        return $result;
    }
}

// EOF