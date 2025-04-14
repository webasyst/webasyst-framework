<?php

class waContactCategoryModel extends waModel
{
    protected $table = 'wa_contact_category';

    /**
     * Create category with given $name
     *
     * @param string $name New category name
     * @return int new id
     */
    public function add($name)
    {
        return $this->insert(array('name' => $name));
    }

    /**
     * @param array|int $value
     * @return array|null
     */
    public function getById($value)
    {
        if (!$value) {
            return null;
        }

        $data = parent::getById((array)$value);

        foreach ($data as &$row) {
            if ($row) {
                $row = $this->getAppInfo((string)ifset($row, 'system_id', '')) + $row;
            }
        }
        unset($row);

        return count($value) > 1 ? $data : array_shift($data);
    }

    /**
     * Delete category by ID
     *
     * @param int $id
     * @return boolean
     * @deprecated use deleteById() instead. This will give no change to orphans in linked tables
     */
    public function delete($id)
    {
        $ccm = new waContactCategoriesModel();
        $ccm->deleteByField('category_id', $id);
        return $this->deleteById($id);
    }

    /**
     * Delete category by ID
     *
     * @param int $id
     * @return boolean
     */
    public function deleteById($id)
    {
        $ccm = new waContactCategoriesModel();
        $ccm->deleteByField('category_id', $id);

        return parent::deleteById($id);
    }

    /**
     * Returns all names of categories indexed by its ID
     *
     * @return array id => name
     */
    public function getNames()
    {
        return $this->select('id, name')
            ->order('name')
            ->query()
            ->fetchAll('id', true);
    }

    /**
     * Update members count
     *
     * @param int $id
     * @param int $count
     */
    public function updateCount($id, $count)
    {
        $this->updateById($id, array('cnt' => $count));
    }

    /**
     * Recalculate counters of categories
     *
     * @param int|array|null $id
     */
    public function recalcCounters($id = null)
    {
        $where = '';
        if ($id) {
            $ids = array_filter(array_map('intval', (array)$id));
            if (!$ids) {
                return;
            }
            $where = "WHERE cc.id IN (" . implode(',', $ids) . ")";
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
        $data = $this->select('*')->order('name')->query()->fetchAll($key, $normalize);

        foreach ($data as &$row) {
            $row = $this->getAppInfo(ifset($row, 'system_id', '')) + $row;
        }
        unset($row);
        return $data;
    }

    /**
     * Category row with given system id.
     * Category is created with given name (matches $system_id if omitted) when it does not exist.
     *
     * @param string $system_id
     * @param string $name
     * @return array
     * @throws waException
     */
    public function getBySystemId($system_id, $name = null)
    {
        $cat = $this->getByField('system_id', $system_id);
        if (!$cat) {
            return $this->getById($this->insert(array(
                'system_id' => $system_id,
                'name'      => $name ? $name : $system_id,
            )));
        }
        return $cat;
    }

    /**
     * Get application properties by application ID
     *
     * @param string $system_id
     * @return array
     */
    protected function getAppInfo($system_id)
    {
        if (empty($system_id) || !wa()->appExists($system_id)) {
            return array();
        }
        $app = wa()->getAppInfo($system_id);

        return array('name' => $app['name'], 'icon' => wa()->getRootUrl(true) . $app['icon'][16]);
    }
}

