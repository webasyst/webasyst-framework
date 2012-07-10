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
     * @param null|string $key
     * @param bool $normalize
     * @return array id => array(id=>..,name=>..,cnt=>..)
     */
    public function getAll($key = null, $normalize = false)
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY name";
        return $this->query($sql)->fetchAll($key = null, $normalize = false);
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

