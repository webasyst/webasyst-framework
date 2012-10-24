<?php

/** Add or delete a set of contacts to/from a set of categories. */
class contactsCategoriesController extends waJsonController
{
    public function execute()
    {
        $this->checkAccess();
        switch (waRequest::get('type')) {
            case 'add':
                $this->addToCategory();
                break;
            case 'del':
                $this->removeFromCategory();
                break;
        }
    }

    protected function checkAccess() {
        if ($this->getRights('category.all')) {
            return;
        }

        // Only allow actions with categories available for current user
        $crm = new contactsRightsModel();
        $allowed = $crm->getAllowedCategories();
        foreach(waRequest::post('categories', array(), 'array_int') as $id) {
            if (!isset($allowed[$id])) {
                throw new waRightsException('Access denied');
            }
        }
        
        // Only allow actions with contacts available for current user
        $allowed = array_keys($allowed);
        $ccm = new waContactCategoriesModel();
        foreach($ccm->getContactsCategories(waRequest::post('contacts', array(), 'array_int')) as $id => $cats) {
            if (!array_intersect($allowed, $cats)) {
                throw new waRightsException('Access denied');
            }
        }
    }
    
    protected function removeFromCategory() {
        $contacts = waRequest::post('contacts', array(), 'array_int');
        $categories = waRequest::post('categories', array(), 'array_int');
        
        if (!$contacts || !$categories) {
            return;
        }
        
        $ccm = new waContactCategoriesModel();
        $ccm->remove($contacts, $categories);

        $contacts = count($contacts);
        $categories = count($categories);
        $this->response['message'] = sprintf(_w("%d contact has been removed", "%d contacts have been removed", $contacts), $contacts);
        $this->response['message'] .= ' ';
        $this->response['message'] .= sprintf(_w("from %d category", "from %d categories", $categories), $categories);
    }
    
    protected function addToCategory()
    {
        $contacts = waRequest::post('contacts', array(), 'array_int');
        $categories = waRequest::post('categories', array(), 'array_int');

        $ccm = new waContactCategoriesModel();
        $ccm->add($contacts, $categories);

        foreach ($categories as $category_id) {
            $c = new waContactsCollection("/category/".$category_id);
            $this->response['count'][$category_id] = $c->count();
        }

        $contacts = count($contacts);
        $categories = count($categories);
        $this->response['message'] = sprintf(_w("%d contact has been added", "%d contacts have been added", $contacts), $contacts);
        $this->response['message'] .= ' ';
        $this->response['message'] .= sprintf(_w("to %d category", "to %d categories", $categories), $categories);
    }
}

// EOF