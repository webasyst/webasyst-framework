<?php

class waGroupModel extends waModel
{
    protected $table = 'wa_group';
    
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
        $sql = "SELECT id, name FROM ".$this->table." ORDER BY name";
        return $this->query($sql)->fetchAll('id', true);
    }
    
    /** @return array id => array(id=>..,name=>..,cnt=>..) */
    public function getAll($key = null, $normalize = false)
    {
        $sql = "SELECT * FROM `{$this->table}` ORDER BY name";
        return $this->query($sql)->fetchAll('id');
    }
    
    /**
     * Delete group
     * 
     * @param int $id
     */
    public function delete($id)
    {
        // Delete all records from table of the links
        $user_groups_model = new waUserGroupsModel();
        $user_groups_model->deleteByField('group_id', $id);
        // Delete group
        return $this->deleteById($id);
    }
    
    /** Update members count */
    public function updateCount($id, $count) {
        $this->updateById($id, array('cnt' => $count));
    }
}