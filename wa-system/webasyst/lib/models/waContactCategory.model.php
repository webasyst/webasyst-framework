<?php

class waContactCategoryModel extends waModel
{
    protected $table = 'wa_contact_category';

    /** Create category
     * @param string $name New category name
     * @return int new id */
    public function add($name)
    {
        return $this->insert(array('name' => $name));
    }

    public function getById($value)
    {
        $data = parent::getById($value);
        if ($data && $data['system_id'] && wa()->appExists($data['system_id'])) {
            $app = wa()->getAppInfo($data['system_id']);
            $data['name'] = $app['name'];
            $data['icon'] = wa()->getRootUrl(true).$app['icon'][16];
        }
        return $data;
    }

    /**
     * Delete category by ID
     * @param int $id
     * @return boolean
     */
    public function delete($id)
    {
        $ccm = new waContactCategoriesModel();
        $ccm->deleteByField('category_id', $id);
        return $this->deleteById($id);
    }

    /** @return array id => name */
    public function getNames()
    {
        $sql = "SELECT id, name FROM {$this->table} ORDER BY name";
        return $this->query($sql)->fetchAll('id', true);
    }

    /**
     * Update members count
     * @param int $id
     * @param int $count
     */
    public function updateCount($id, $count)
    {
        $this->updateById($id, array('cnt' => $count));
    }

    /**
     * Recalculate counters of categories
     * @param int|array|null $id
     */
    public function recalcCounters($id = null)
    {
        $where = '';
        if ($id) {
            $ids = array_map('intval', (array) $id);
            if (!$ids) {
                return;
            }
            $where = "WHERE cc.id IN (".implode(',', $ids).")";
        }
        $sql = "UPDATE `wa_contact_category` cc JOIN (
                SELECT cc.id, COUNT(*) AS count FROM `wa_contact_category` cc
                JOIN `wa_contact_categories` ccs ON cc.id = ccs.category_id
                {$where}
                GROUP BY cc.id) t ON t.id = cc.id
            SET cc.cnt = t.count";
        $this->exec($sql);
    }

    /**
     * @param null|string $key
     * @param bool $normalize
     * @return array id => array(id=>..,name=>..,cnt=>..)
     */
    public function getAll($key = null, $normalize = false)
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY name";
        $data = $this->query($sql)->fetchAll($key, $normalize);
        foreach ($data as &$row) {
            if ($row['system_id']) {
                if (wa()->appExists($row['system_id'])) {
                    $app = wa()->getAppInfo($row['system_id']);
                    $row['name'] = $app['name'];
                    $row['icon'] = wa()->getRootUrl(true).$app['icon'][16];
                }
            }
        }
        unset($row);
        return $data;
    }

    /**
     * Category row with given system id.
     * Category is created with given name (matches $system_id if omitted) when it does not exist.
     * @param string $system_id
     * @param string $name
     * @return array
     */
    public function getBySystemId($system_id, $name=null)
    {
        $cat = $this->getByField('system_id', $system_id);
        if (!$cat) {
            return $this->getById($this->insert(array(
                'system_id' => $system_id,
                'name' => $name ? $name : $system_id,
            )));
        }
        return $cat;
    }
}

