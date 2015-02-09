<?php

/** Category editor: edit name. */
class contactsCategoriesEditorAction extends waViewAction
{
    public function execute() {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException(_w('Access denied'));
        }

        $category = null;
        if ( ( $id = waRequest::get('id'))) {
            $cm = new waContactCategoryModel();
            $category = $cm->getById($id);
        }

        $this->view->assign('category', $category);
    }
}

// EOF