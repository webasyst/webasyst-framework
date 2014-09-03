<?php

/** Delete a contacts category */
class contactsCategoriesDeleteFromController extends waJsonController
{
    public function execute()
    {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }

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
}

// EOF