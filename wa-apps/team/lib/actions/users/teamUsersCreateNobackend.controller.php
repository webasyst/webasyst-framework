<?php
/**
 * Create a contact with no access to Webasyst backend
 * but a special marker to make it show in Team app in "No access" section
 */
class teamUsersCreateNobackendController extends teamUsersNewUserController
{
    public function execute()
    {
        $data = $this->getAdditionalContactData();
        $contact_id = $this->createContact($data);
        $this->response = array(
            'contact_url' => wa()->getUrl() . 'id/' . $contact_id . '/'
        );
    }

    protected function createContact($data)
    {
        $data['is_user'] = 0;
        $data['is_staff'] = 1;
        $data['locale'] = wa()->getUser()->getLocale();

        $contact = new waContact();
        $contact->save($data);
        return $contact->getId();
    }
}
