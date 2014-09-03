<?php

class contactsCategoriesContactSaveController extends waJsonController
{
    public function execute()
    {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }
        
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