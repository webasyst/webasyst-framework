<?php

/**
 * Contact profile view and editor form.
 *
 * This action is also used in own profile editor, even when user has no access to Contacts app.
 * See 'profile' module in 'webasyst' system app.
 */
class contactsContactsInfoController extends waController
{
    public function execute()
    {
        if (!$this->getRequest()->request('json', 0)) {
            $action = new contactsContactsInfoAction();
            echo $action->display();
            return;
        }
        
        $m = new waContactModel();
        $contact_id = $this->getRequest()->request('id', 0, 'int');
        $contact = new waContact($contact_id);
        $values = $contact->load('js', true);
        if (isset($values['company_contact_id'])) {
            if (!$m->getById($values['company_contact_id'])) {
                $values['company_contact_id'] = 0;
                $contact->save(array('company_contact_id' => 0));
            }
        }
        $values['photo_url_96'] = $contact->getPhoto(96);
        $values['photo_url_20'] = $contact->getPhoto(20);
        $fields = waContactFields::getInfo($contact['is_company'] ? 'company' : 'person', true);
        echo json_encode(array(
            'fields' => $fields,
            'values' => $values,
            'top' => contactsHelper::getTop($contact)
        ));
        
    }    
}

// EOF
