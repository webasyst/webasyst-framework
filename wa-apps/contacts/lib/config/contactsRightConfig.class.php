<?php

class contactsRightConfig extends waRightConfig
{
    /** 
     * @var contactsRightsModel 
     */
    protected static $model = null;

    public function init()
    {
        $model = new waContactCategoryModel();
        $items = $model->getNames();
        $this->addItem('create', _w('Can add contacts'), 'checkbox');
        $this->addItem('edit', _w('Can edit or delete contacts added by other users'), 'checkbox');
        //$this->addItem('category', _w('Available categories'), 'list', array('items' => $items, 'hint1' => 'all_checkbox'));
        
        wa()->event('rights.config', $this);
        
        if (!self::$model) {
            self::$model = new contactsRightsModel();
        }
    }
    
    public function setDefaultRights($contact_id)
    {
        return array(
            'create' => 1,
            'edit' => 1
        );
    }

    public function setRights($contact_id, $name, $value = null)
    {
        if ($name == 'backend' && !$value) {
            self::$model->deleteByField(array('group_id' => -$contact_id));
            return false; // still need to update main rights table, so we return false
        }
        return false;
    }

    public function clearRights($contact_id)
    {
        self::$model->deleteByField(array('group_id' => -$contact_id));
    }
}
