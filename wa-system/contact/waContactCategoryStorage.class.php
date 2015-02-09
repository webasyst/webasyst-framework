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
class waContactCategoryStorage extends waContactStorage
{
    /**
     * @var waContactCategoriesModel
     */
    protected $model = null;

    /**
     * Returns model
     *
     * @return waContactCategoriesModel
     */
    public function getModel()
    {
        if (!$this->model) {
            $this->model = new waContactCategoriesModel();
        }
        return $this->model;
    }

    /**
     * @param waContact $contact
     * @param string|array $fields
     * @return array
     */
    public function load(waContact $contact, $fields=null)
    {
        if ($fields && $fields != 'categories' && (!is_array($fields) || !in_array('categories', $fields))) {
            return array();
        }
        
        $categories = $this->getModel()->getContactCategories($contact->getId());
        
        return array(
            'categories' => array(
                'value' => array_keys($categories)
            )
        );
    }

    public function save(waContact $contact, $fields)
    {
        if (!isset($fields['categories'])) {
            return TRUE;
        }
        
        if (empty($fields['categories'][0])) {
            $fields['categories'] = array();
        }

        $this->getModel()->setContactCategories($contact->getId(), $fields['categories']);
        return TRUE;
    }

    public function deleteAll($fields, $type=null) {
        throw new waException('You cannot delete all contacts from all categories this way.');
    }

    public function duplNum($field) {
        throw new waException('Duplicates search does not make sense for categories field.');
    }

    public function findDuplicatesFor($field, $values, $excludeIds=array()) {
        throw new waException('Duplicates search does not make sense for categories field.');
    }
}

// EOF