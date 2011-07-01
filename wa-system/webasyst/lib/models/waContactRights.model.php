<?php
/**
 * Class to work with user and group access rights.
 * 
 * Each application may store its access rights in a centralized storage kept by this model.
 * It keeps (int) access values associated with (string) app_id, (int) contact_id, (string) name.
 * 
 * To get values for groups, pass a negative group_id instead of contact_id.
 */
class waContactRightsModel extends waModel {

    protected static $cache = array();
    protected $table = "wa_contact_rights";

    /** Check key for current auth user and application */
    public function check($name) {
        return $this->get(null, null, $name);
    }
    
    /** Get access value for user or group.
      * Value for a group is determined from group acces preferences.
      * Eventual value for a user is MAX(preference for this user, preferences for groups user is member of). 
      * 
      * @param int|array $id (defaults to current auth user) user id if positive; group id if negative (0 is group id for guests); can be alist of such integers.
      * @param string $app_id application id; defaults to current application.
      * @param $name key to fetch value for; if not specified, then an array name => value is returned.
      * @param boolean $checkGroups (default is true) if set to false then only own access rights are considered, as if contact has no groups assigned
      * @return int|array depends on $name */
    public function get($id=null, $app_id=null, $name=null, $checkGroups = TRUE)
    {
    	if ($app_id !== null && $app_id !== 'webasyst' && !waSystem::getInstance()->appExists($app_id)) {
    		return $name === null ? array() : 0;
    	}
    	
        if ($id === null) {
            $id = waSystem::getInstance()->getUser()->getId();
        }
        if (!$app_id) {
            $app_id = waSystem::getInstance()->getApp();
        }

        if ($app_id != 'webasyst' && $this->get($id, 'webasyst', 'backend')) {
            return $name ? PHP_INT_MAX : array('backend' => PHP_INT_MAX);
        }
        
        if (is_array($id) || !isset(self::$cache[$id][$app_id])) {
            $group_ids = is_array($id) ? array_map(wa_lambda('$a', 'return -$a;'), $id) : array(-$id);
            if ($checkGroups) {
                $group_ids[] = 0; // guests 
                if ($id > 0) { // contact groups
                    $user_groups_model = new waUserGroupsModel();
                    $group_ids = array_merge($group_ids, $user_groups_model->getGroupIds($id));
                }
            }

            $sql = "SELECT name, MAX(value) 
                    FROM ".$this->table."
                    WHERE group_id IN (i:group_id)
                        AND app_id = s:app_id 
                        AND value > 0
                    GROUP BY name";
            $rights = $this->query($sql, array(
                'group_id' => $group_ids, 
                'app_id' => $app_id
            ))->fetchAll('name', true);
            
            if (!is_array($id)) {
                self::$cache[$id][$app_id] = $rights;
            }
        } else {
            $rights = self::$cache[$id][$app_id];
        }
        
        if ($name === null) {
            return $rights;
        } elseif (isset($rights['backend']) && $rights['backend'] >= 2) {
            return PHP_INT_MAX;
        } else {
            return isset($rights[$name]) ? $rights[$name] : 0;
        }
    }
    
    /**
     * @param array $ids list of contact (if positive) or group (if negative) ids.
     * @return array id => admin|custom; for users with no access at all there's no key=>value pair.
     */
    public function getAccessStatus($ids) {
        if (!$ids) {
            return array();
        }
        
        // Additional groups we need to get access info for.
        // $group_ids = list of (negative) group ids that users from $ids are members of.
        $user_groups_model = new waUserGroupsModel();
        $user_group = $user_groups_model->getGroupIdsForUsers($ids); // ignores negative ids, so it's ok to pass group ids there
        
        $group_ids = array();
        foreach ($user_group as $user_group_ids) {
        	$group_ids = array_merge($group_ids, $user_group_ids);
        }
        $group_ids = array_map(wa_lambda('$a', 'return -$a;'), $group_ids);
        
        $sql = "SELECT -group_id AS id, MAX(IF(app_id='webasyst' AND name='backend', 2, 1)) AS status
                FROM `{$this->table}`
                WHERE -group_id IN (i:ids)
                GROUP BY group_id";
        $result = $this->query($sql, array(
                // everything from $ids and all groups found for users in $ids; can contain duplicates, but it's ok
                'ids' => array_merge($ids, $group_ids)
            )
        )->fetchAll('id', true);
        
        // update result considering group rights for users
        foreach($ids as $id) {
            if (!isset($result[$id])) {
                $result[$id] = 0;
            }
            if (isset($user_group[$id]) && $result[$id] <= 1) {
                foreach($user_group[$id] as $gid) {
                    if (isset($result[-$gid]) && $result[-$gid] > $result[$id]) {
                        $result[$id] = $result[-$gid];
                    }
                    if ($result[$id] > 1) {
                        break;
                    }
                }
            }

            if ($result[$id]) {
                $result[$id] = $result[$id] > 1 ? 'admin' : 'custom';
            } else {
                unset($result[$id]);
            }
        }
        
        // Remove from results all groups that we added temporary
        foreach($group_ids as $gid) {
            if (isset($result[$gid])) {
                unset($result[$gid]);
            }
        }
        
        return $result;
    }
    
    /** Save access preference for user or group.
      * @param int $id treated as user id if positive; group id otherwise; 0 is group id for guests.
      * @param string $app_id application id
      * @param $name key to save value for 
      * @param $value int value to save; negative saved as 0. */
    public function save($id, $app_id, $name, $value)
    {
        $id = -$id;
        if ($app_id == 'webasyst' && $name == 'backend') {
            $this->deleteByField('group_id', $id);
        } else if ($name == 'backend' && $value != 1) {
            $this->deleteByField(array('group_id' => $id, 'app_id' => $app_id));
        }
        if ($value <= 0) {
            $sql = "DELETE FROM ".$this->table."
                    WHERE group_id = i:group_id AND
                          app_id = s:app_id AND
                          name = s:name";
        } else {
            $sql = "INSERT INTO ".$this->table."
                    SET `group_id` = i:group_id,
                        `app_id` = s:app_id,
                        `name` = s:name,
                        `value` = i:value
                    ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)";
        }
        return $this->exec($sql, array(
            'group_id' => $id,
            'app_id' => $app_id,
            'name' => $name,
            'value' => $value
        ));
    }

    /** @return array (contact_id => value) of users allowed for given app and access key */
    public function getAllowedUsers($app_id, $name)
    {
        $sql = "SELECT -group_id contact_id, value 
                FROM ".$this->table."
                WHERE app_id = s:app_id 
                    AND name = s:name
                    AND value > 0
                    AND group_id < 0";
        return $this->query($sql, array('app_id' => $app_id, 'name' => $name))->fetchAll('contact_id', true);
    }

    /** @return array (group_id => value) of groups allowed for given app and access key */
    public function getAllowedGroups($app_id, $name) {
        $sql = "SELECT group_id, value
                FROM ".$this->table."
                WHERE app_id = s:app_id
                    AND name = s:name
                    AND value > 0
                    AND group_id > 0";
        return $this->query($sql, array('app_id' => $app_id, 'name' => $name))->fetchAll('group_id', true);
    }

    /** @return array(group_id => total number of applications that storage keeps values for, not counting webasyst itself) */
    public function countApps()
    {
        $sql = "SELECT -group_id group_id, COUNT(*) 
                FROM ".$this->table."
                WHERE app_id != 'webasyst' 
                    AND name = 'backend'
                    AND value > 0
                    AND group_id < 0
                GROUP BY group_id";
        return $this->query($sql)->fetchAll('group_id', true);
    }

    /** @return array basic info from wa_contact for users who have access to given application. */
    public function getUsers($app_id, $name = 'backend', $value = null)
    {
    	$sql = "SELECT DISTINCT IF(r.group_id < 0, -r.group_id, g.contact_id) 
    			FROM  wa_contact_rights r 
    			LEFT JOIN wa_user_groups g ON r.group_id = g.group_id 
    			WHERE r.app_id = s:app_id AND r.name = s:name AND r.value ".
    			($value === null ? " > 0" : " = ".(int)$value);
        return $this->query($sql, array('app_id' => $app_id, 'name' => $name))->fetchAll(null, true);
    }

    /** Get access rights by group and key
      * @param int|array $id group ids (if positive) or contact ids (negative)
      * @param string $name key to check value for; default is 'backend'
      * @param boolean $checkGroups (default is true) if set to false then only own access rights are considered, as if contact has no groups assigned
      * @return array (app_id => value) */
    public function getApps($id, $name='backend', $checkGroups=true, $noWA=true)
    {
    	$cache = false;
        if ($checkGroups && is_numeric($id) && $id < 0) {
            $user_groups_model = new waUserGroupsModel();
            $cache = -$id;
            $id = array_merge(array($id, 0), $user_groups_model->getGroupIds(-$id));
        }
        
        if ((is_array($id) && !$id) || (!is_numeric($id) && !is_array($id))) {
            return array();
        }
        
        $sql = "SELECT app_id, MAX(value) v
                FROM ".$this->table."
                WHERE group_id IN (i:group_id)" 
                    .($noWA ? " AND app_id != 'webasyst' " : '').
                    "AND name = s:name
                    AND value > 0
                GROUP BY app_id";

        $data = $this->query($sql, array('group_id' => $id, 'name' => $name));
        $result = array();
        foreach ($data as $row) {
        	$result[$row['app_id']] = $row['v'];
        	if ($cache) {
        		self::$cache[$cache][$row['app_id']] = $row['v'];
        	}
        }
        return $result;     
    }
}

// EOF