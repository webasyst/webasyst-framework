<?php

/** Add or delete a set of contacts to/from a set of categories. */
class contactsCategoriesController extends waJsonController
{
    public function execute()
    {
        switch (waRequest::get('type')) {
            case 'add':
                $this->addToCategory();
                break;
            case 'del':
                $this->removeFromCategory();
                break;
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