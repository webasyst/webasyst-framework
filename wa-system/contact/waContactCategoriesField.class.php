<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage contact
 */
class waContactCategoriesField extends waContactChecklistField
{
    /**
     * @var waContactCategoryModel
     */
    protected  $model;
    protected function init() {
        $this->options['storage'] = 'waContactCategoryStorage';
        $this->options['required'] = !wa()->getUser()->getRights('contacts', 'category.all');
    }

    function getOptions($id = null) {
        if (!$this->model) {
            $this->model = new waContactCategoryModel();
        }
        
        $options = $this->model->getNames();
        
        // Admins are allowed to see everything, and person outside of contacts app can see a list of categories too
        if (wa()->getApp() != 'contacts' || wa()->getUser()->getRights('contacts', 'category.all')) {
            return $options;
        }

        // Only load categories available for current user
        $crm = new contactsRightsModel();
        $allowed = $crm->getAllowedCategories();
        foreach($options as $id => $row) {
            if (!isset($allowed[$id])) {
                unset($options[$id]);
            }
        }
        return $options;
    }
    
    public function validate($data, $contact_id=null) {
        if ($this->options['required'] && empty($data[0])) {
            return array('This field is required');
        }
        return null;
    }
    
    public function setParameter($p, $value) {
        // do not allow to reset required status initially set in init()
        if ($p == 'required') {
            return;
        }
        parent::setParameter($p, $value);
    }
}

// EOF