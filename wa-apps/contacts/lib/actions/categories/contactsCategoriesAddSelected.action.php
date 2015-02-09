<?php

/** Dialog to add a set of selected contacts to one or more categories. */
class contactsCategoriesAddSelectedAction extends waViewAction
{
    public function execute() {
        
        $cm = new waContactCategoryModel();

        // List of categories user is allowed to add contacts to
        $categories = $cm->select('*')->where('system_id IS NULL')->fetchAll('id');
        
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