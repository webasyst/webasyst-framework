<?php

class waContactCategoryModel extends waModel
{
    protected $table = 'wa_contact_category';

    /** Create category
     * @param string $name New category name
     * @return int new id */
    public function add($name) {
        return $this->insert(array('name' => $name));
    }

    /** Delete category
     * @param int $id */
    public function delete($id) {
        $ccm = new waContactCategoriesModel();
        $ccm->deleteByField('category_id', $id);
        return $this->deleteById($id);
    }

    /** @return array id => name */
    public function getNames() {
        $sql = "SELECT id, name FROM {$this->table} ORDER BY name";
        return $this->query($sql)->fetchAll('id', true);
    }

    /** Update members count */
    public function updateCount($id, $count) {
        $this->updateById($id, array('cnt' => $count));
    }

    /** @return array id => array(id=>..,name=>..,cnt=>..) */
    public function getAll($key = null, $normalize = false) {
        $sql = "SELECT * FROM `{$this->table}` ORDER BY name";
        return $this->query($sql)->fetchAll($key = null, $normalize = false);
    }
}

// EOF
