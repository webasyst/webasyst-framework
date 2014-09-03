<?php

class contactsHistoryModel extends waModel
{
    protected $table = 'contacts_history';
    const NUM_HISTORY_KEEP = 150;
    const NUM_HISTORY_SHOW = 5;
    const NUM_SEARCH_HISTORY_SHOW = 100;


    /** Get all history for current user, or a single history record
      * @param int $id (defaults to null) id of a record to fetch
      * @return array if $id is specified, then null (if not found) or a single array with keys: id, type, name, hash, contact_id, position, accessed, cnt; if no $id, then a list of such arrays is returned. */
    public function get($id = null) {
        if ($id) {
            $sql = "SELECT * FROM {$this->table} WHERE id=i:id";
            return $this->query($sql, array('id' => $id))->fetchRow();
        }

        $currentUserId = wa()->getUser()->getId();
        $sql = "SELECT *
                FROM {$this->table}
                WHERE contact_id=:uid
                ORDER BY position, accessed DESC";
        $history = $this->query($sql, array('uid' => $currentUserId))->fetchAll();
        $contact_ids = array();
        foreach ($history as $h) {
            if ($h['type'] === 'add') {
                $contact_id = (int) str_replace('/contact/', '', $h['hash']);
                if ($contact_id) {
                    $contact_ids[] = $contact_id;
                }
            }
        }
        
        $contacts = array();
        if ($contact_ids) {
            $col = new contactsCollection('id/' . implode(',', $contact_ids));
            $contacts = $col->getContacts('id,is_company,photo_url_20');
        }
        
        foreach ($history as &$h) {
            if ($h['type'] === 'add') {
                $contact_id = (int) str_replace('/contact/', '', $h['hash']);
                $contact = ifset($contacts[$contact_id], array('is_company' => 0, 'photo_url_20' => waContact::getPhotoUrl(null, null, 20, 20, 'person')));
                $h['icon'] = $contact['photo_url_20'];
            }
        }
        unset($h);
        
        
        // leave only NUM_HISTORY_SHOW temporary items (i.e. position == 0 and type != import)
        $ra_limit = self::NUM_HISTORY_SHOW; // recently added
        $s_limit = self::NUM_HISTORY_SHOW; // search history
        
        $not_shown = array();
        foreach($history as $k => $v) {
            if ($v['position'] > 0) {
                break;
            }

            if ($v['type'] != 'search') {
                if ($ra_limit <= 0) {
                    $not_shown[] = $v['id'];
                    unset($history[$k]);
                    continue;
                }
                $ra_limit--;
            } else {
                if ($s_limit <= 0) {
                    $not_shown[] = $v['id'];
                    unset($history[$k]);
                    continue;
                }
                $s_limit--;
            }
        }

        if ($not_shown) {
            $sql = "DELETE FROM {$this->table} WHERE id IN (i:id)";
            $this->exec($sql, array('id' => $not_shown));

            // reset holes in key sequence
            $history = array_merge($history);
        }

        
        return $history;
    }
    
    public function getByType($type, $limit = null)
    {
        return $this->select('*')
                ->where(
                        'contact_id = i:contact_id AND type = s:type', 
                        array(
                            'contact_id' => wa()->getUser()->getId(), 
                            'type' => $type
                        ))
                ->order('position, accessed DESC')
                ->limit(!$limit ? 
                    ($type === 'search' ? self::NUM_SEARCH_HISTORY_SHOW : self::NUM_HISTORY_SHOW) : 
                    (int) $limit
                )->fetchAll();
    }
    
    public function countByType($type)
    {
        return $this->select('COUNT(id)')->where('contact_id = i:contact_id AND type = s:type', 
                array('contact_id' => wa()->getUser()->getId(), 'type' => $type)
        )->order('position, accessed DESC')->fetchField();        
    }

    /** Create a new record in history or update an existing one.
      * New record is created as temporary (not fixed). Existing record status does not change.
      * @param string $hash URL part after #, without #
      * @param string $name Human-readable title (null to do not update name)
      * @param string $type add|search|import; pass null (default) to update existing record not creating one if does not exist.
      * @param mixed $count number to show as a count; -1 (default) to show no number at all. If '--' is passed as $count then existing number is decreased by 1.
      * @return boolean true if new record created, false if old record updated */
    public function save($hash, $name, $type=null, $count=null) {
        $currentUserId = wa()->getUser()->getId();

        $sql = "SELECT id, accessed FROM {$this->table} WHERE contact_id=i:uid AND hash=:hash";
        $id = $this->query($sql, array('uid' => $currentUserId, 'hash' => $hash))->fetchAssoc();
        if (!$id) {
            $newRecord = true;
            $accessed = $id = 0;
        } else {
            $newRecord = false;
            $accessed = $id['accessed'];
            $id = $id['id'];
        }

        if ($id) {
            $set = array();
            if ($name !== null) {
                $set[] = 'name=:name';
            }
            if ($count === '--') {
                $set[] = 'cnt=cnt-1';
            } else if ($count !== null) {
                $set[] = 'cnt=i:count';
            }

            if ($set) {
                $set[] = 'accessed=:accessed';
                $set = implode(',', $set);

                $sql = "UPDATE {$this->table} SET {$set} WHERE id=i:id";
                $this->exec($sql, array(
                    'id' => $id,
                    'name' => $name,
                    'count' => $count,
                    'accessed' => date('Y-m-d H:i:s')
                ));
            }
        } else if ($type) {
            // Create history record
            $id = $this->insert(array(
                'type' => $type,
                'name' => $name,
                'hash' => $hash,
                'cnt' => $count !== null ? (int) $count : -1,
                'contact_id' => $currentUserId,
                'accessed' => date('Y-m-d H:i:s'),
            ));
        }

        $this->prune();
        
        return $newRecord;
    }

    /** Mark history record as fixed and move it to given position
      * @param int $id id of record to update
      * @param int $position Position parameter of a record to place $id after. Defaults to the end of the list. Set to 0 to make unfix a record and make it temporary. */
    public function fix($id, $position=null) {
        $currentUserId = wa()->getUser()->getId();

        if ($position === null) {
            // determine the max position
            $sql = "SELECT MAX(position) FROM {$this->table} WHERE contact_id=i:uid AND id<>i:id";
            $position = 1 + $this->query($sql, array('uid' => $currentUserId, 'id' => $id))->fetchField();
        } else if ($position > 0) {
            // free space at $position
            $sql = "UPDATE {$this->table} SET position=position+1 WHERE contact_id=:uid AND position>i:position AND id<>i:id";
            $this->exec($sql, array('uid' => $currentUserId, 'position' => $position, 'id' => $id));
        }

        $sql = "UPDATE {$this->table} SET position=:position WHERE id=:id";
        $this->exec($sql, array('position' => $position, 'id' => $id));
    }

    /** Remove temporary (not fixed) history, except last $limit records
      * @param int $limit (default contactsHistoryModel::NUM_HISTORY_KEEP) how many items to keep
      * @param string $type if specified, then only records of given type are affected. */
    public function prune($limit=null, $type=null) {
        $currentUserId = wa()->getUser()->getId();
        $typeSql = $type ? " AND type IN (:type) " : '';
        if ($limit === null) {
            $limit = self::NUM_HISTORY_KEEP;
        }
        
        // How many records are there?
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE contact_id=i:uid AND position=0".$typeSql;
        $total = $this->query($sql, array('uid' => $currentUserId, 'type' => $type))->fetchField();

        $limit = $total - $limit;
        if ($limit > 0) {
            $sql = "DELETE FROM {$this->table} WHERE contact_id=:uid AND position=0$typeSql ORDER BY accessed LIMIT i:limit";
            $this->exec($sql, array('uid' => $currentUserId, 'limit' => $limit, 'type' => $type));
        }
    }
}

// EOF