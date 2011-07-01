<?php

class contactsRightConfig extends waRightConfig
{
    /** @var contactsRightsModel */
    protected static $model = null;
    protected $plugins_added = FALSE;

    public function init()
    {
        $model = new waContactCategoryModel();
        $items = $model->getNames();
        $this->addItem('create', _w('Can add contacts'), 'checkbox');
        $this->addItem('category', _w('Available categories'), 'list', array('items' => $items, 'hint1' => 'all_checkbox'));
        if (!self::$model) {
            self::$model = new contactsRightsModel();
        }
    }

    public function getHTML($rights = array(), $inherited=null)
    {
        if (!$this->plugins_added) {
            // allow plugins to insert their own items
            // we do it here rather than in constructor because HTML is not always needed
            foreach(wa()->event('rights.config', $this) as $items) {
                foreach ($items as $item) {
                    if (isset($item['id'], $item['name'], $item['type'], $item['options'])) {
                        $this->addItem($item['id'], $item['name'], $item['type'], $item['options']);
                    }
                }
            }
            $this->plugins_added = TRUE;
        }
        return parent::getHTML($rights, $inherited);
    }

    public function setDefaultRights($contact_id)
    {
        // Default access rights for contacts: creation of new contacts and all categories are allowed
        // (Nothing to do with waContactCategoryModel.)
        return array(
            'category.all' => 1,
            'create' => 1,
        );
    }

    public function getRights($contact_id)
    {
        $result = array();

        if (!is_array($contact_id)) {
            $contact_id = array(-$contact_id);
        }

        foreach (self::$model->getByField('group_id', $contact_id, true) as $row) {
            if ($row['category_id'] > 0) {
                $result['category.'.$row['category_id']] = 1;
            }
        }
        return $result;
    }

    public function setRights($contact_id, $name, $value = null)
    {
        if (substr($name, 0, 9) == 'category.' && ( $category_id = (int)substr($name, 9))) {
            if ($value) {
                self::$model->replace(array('group_id' => -$contact_id, 'category_id' => $category_id, 'writable' => 1));
                return true;
            } else {
                self::$model->deleteByField(array('group_id' => -$contact_id, 'category_id' => $category_id));
                return true;
            }
        } else if ($name == 'backend' && !$value) {
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
