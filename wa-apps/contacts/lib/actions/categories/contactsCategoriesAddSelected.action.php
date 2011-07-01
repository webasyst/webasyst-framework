<?php

/** Dialog to add a set of selected contacts to one or more categories. */
class contactsCategoriesAddSelectedAction extends waViewAction
{
    public function execute() {
        // Only show categories available to current user
        $crm = new contactsRightsModel();
        $cm = new waContactCategoryModel();
        $categories = $cm->getNames();
        if (TRUE !== ( $allowed = $crm->getAllowedCategories())) {
            foreach($categories as $id => $cat) {
                if (!isset($allowed[$id])) {
                    unset($categories[$id]);
                }
            }
        }
        $this->view->assign('categories', $categories);

        // Set of catorories that are always checked and disabled in list
        $d = waRequest::get('disabled');
        if (!is_array($d)) {
            $d = array($d);
        }
        $this->view->assign('disabled', array_fill_keys($d, true));
    }
}

// EOF