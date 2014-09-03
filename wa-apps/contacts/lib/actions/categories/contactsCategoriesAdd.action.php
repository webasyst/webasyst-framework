<?php

/** Dialog to add a set of selected contacts to one or more categories. */
class contactsCategoriesAddSelectedAction extends waViewAction
{
    public function execute() {
        // Only show categories available to current user
        $crm = new contactsRightsModel();
        $cm = new waContactCategoryModel();

        // List of categories user is allowed to add contacts to
        $categories = $cm->getAll('id');
        
//        $allowed = $crm->getAllowedCategories();
//        if ($allowed === true) {
//            $allowed = $categories;
//        }
//        foreach($categories as $id => &$cat) {
//            if (!isset($allowed[$id]) || $cat['system_id']) {
//                unset($categories[$id]);
//            }
//            $cat = $cat['name'];
//        }
//        unset($cat);

        // Set of catorories that are always checked and disabled in list
        $d = waRequest::get('disabled');
        if (!is_array($d)) {
            $d = array($d);
        }

        $this->view->assign('categories', $categories);
        $this->view->assign('disabled', array_fill_keys($d, true));
    }
}

// EOF