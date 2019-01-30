<?php

class waGroupModel extends waModel
{
    protected $table = 'wa_group';
    static protected $icons = null;

    public static function getIcons()
    {
        if (self::$icons === null) {
            $path = waConfig::get('wa_path_root') . '/wa-content/img/users/';

            if (!file_exists($path) || !is_dir($path)) {
                $list = array();
            }
            if (!($dh = opendir($path))) {
                $list = array();
            }
            $list = array();
            while (false !== ($file = readdir($dh))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if (is_dir($path.'/'.$file)) {
                    continue;
                } else {
                    $list[] = $file;
                }
            }
            closedir($dh);

            foreach ($list as &$l)
            {
                $p = strpos($l, '.png');
                if ($p !== false) {
                    $l = substr($l, 0, $p);
                }
            }
            unset($l);
            natsort($list);

            self::$icons = array_values($list);
        }
        return self::$icons;
    }

    /**
     * Creates a group with the speciafied name
     *
     * @param string $name
     * @return int - id of the new group
     */
    public function add($name)
    {
        return $this->insert(array('name' => $name));
    }

    /**
     * Returns associative array of group names with key group id sorted by name
     *
     * @return array
     */
    public function getNames()
    {
        $sql = "SELECT id, name FROM ".$this->table." ORDER BY type, sort, name";
        return $this->query($sql)->fetchAll('id', true);
    }

    /**
     * @param int|array $id group id or list of ids
     * @return string|array group name or array(id => name) when $id is an array
     */
    public function getName($id)
    {
        if ( ( $string = !is_array($id))) {
            $id = array($id);
        } else {
            $id = array_values($id);
        }

        if (!$id) {
            return array();
        }

        $sql = "SELECT id, name FROM ".$this->table." WHERE id IN (i:ids)";
        $result = $this->query($sql, array('ids' => $id))->fetchAll('id', true);
        if ($string) {
            return ifset($result[$id[0]], $id[0]);
        }
        return $result;
    }

    /**
     * @param null $key
     * @param bool $normalize
     * @return array array(id => array(id=>..,name=>..,cnt=>..) )
     */
    public function getAll($key = null, $normalize = false)
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY type, sort, name";
        $groups = $this->query($sql)->fetchAll('id');
        foreach ($groups as &$g) {
            if (!$g['icon']) {
                $g['icon'] = 'user';
            }
        }
        unset($g);
        return $groups;
    }

    /**
     * Delete group
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        // Delete all records from table of the links
        $user_groups_model = new waUserGroupsModel();
        $user_groups_model->deleteByField('group_id', $id);
        // Delete group
        return $this->deleteById($id);
    }

    /**
     * Update members count
     * @param $id
     * @param $count
     */
    public function updateCount($id, $count)
    {
        $this->updateById($id, array('cnt' => $count));
    }

    public function updateCounts($group_ids = null)
    {
        $where_ug = $where_g = '';
        if ($group_ids) {
            $group_ids = array_values((array)$group_ids);
            $where_ug = "AND ug.group_id IN (i:groups)";
            $where_g = "WHERE g.id IN (i:groups)";
        }

        $subquery = "SELECT ug.group_id, count(*) AS cnt
                     FROM wa_user_groups AS ug
                        JOIN wa_contact AS c
                            ON c.id=ug.contact_id
                     WHERE c.is_user>0
                        {$where_ug}
                     GROUP BY ug.group_id";

        $sql = "UPDATE {$this->table} AS g
                    LEFT JOIN ($subquery) as t
                        ON t.group_id=g.id
                SET g.cnt=IFNULL(t.cnt, 0)
                ".$where_g;

        $this->exec($sql, array('groups' => $group_ids));
    }

    public function getByField($field, $value = null, $all = false, $limit = false)
    {
        if (is_array($field)) {
            $limit = $all;
            $all = $value;
            $value = false;
        }
        $sql = "SELECT * FROM ".$this->table;
        $where = $this->getWhereByField($field, $value);
        if ($where != '') {
            $sql .= " WHERE ".$where;
        }
        $sql .= " ORDER BY type, sort, name";
        if ($limit) {
            $sql .= " LIMIT ".(int) $limit;
        } elseif (!$all) {
            $sql .= " LIMIT 1";
        }

        $result = $this->query($sql);

        if ($all) {
            $result = $result->fetchAll(is_string($all) ? $all : null);
        } else {
            $result = $result->fetchAssoc();
        }

        if ($all) {
            foreach ($result as &$r) {
                if (!$r['icon']) {
                    $r['icon'] = 'user';
                }
            }
            unset($r);
        } else {
            if ($result && !$result['icon']) {
                $result['icon'] = 'user';
            }
        }
        return $result;
    }

}