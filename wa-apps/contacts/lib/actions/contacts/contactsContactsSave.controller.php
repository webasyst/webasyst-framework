<?php

/** save contact data that came from contact add or contact edit form. */
class contactsContactsSaveController extends waJsonController
{
    /**
     * @var int
     */
    protected $id;
    protected $type;
    /**
     * @var waContact
     */
    protected $contact;

    public function execute()
    {

        $this->id = (int)waRequest::post('id');

        // Check access
        if (!$this->id) {
            if (!$this->getRights('create')) {
                throw new waRightsException('Access denied.');
            }
        } else {
            $cr = new contactsRightsModel();
            if ($cr->getRight(null, $this->id) != 'write') {
                throw new waRightsException('Access denied.');
            }
        }

        $this->type = waRequest::post('type');
        $this->contact = new waContact($this->id);
        if($this->type == 'company') {
            $this->contact['is_company'] = 1;
        }

        $data = json_decode(waRequest::post('data'), true);
        if (!$this->id && !isset($data['create_method'])) {
            $data['create_method'] = 'add';
        }


        $oldLocale = $this->getUser()->getLocale();

        // get old data for logging
        if ($this->id) {
            $old_data = array();
            foreach ($data as $field_id => $field_value) {
                $old_data[$field_id] = $this->contact->get($field_id);
            }
        }
        
        $response = array();
        if (!$errors = $this->contact->save($data, true)) {
            if ($this->id) {
                $new_data = array();
                foreach ($data as $field_id => $field_value) {
                    if (!isset($errors[$field_id])) {
                        $response[$field_id] = $this->contact->get($field_id, 'js');
                        $new_data[$field_id] = $this->contact->get($field_id);
                    }
                }
                if (empty($errors)) {
                    $this->logContactEdit($old_data, $new_data);
                }
                
                $response['name'] = $this->contact->get('name', 'js');
                $response['top'] = contactsHelper::getTop($this->contact);
                $response['id'] = $this->contact->getId();
            } else {
                $response = array('id' => $this->contact->getId());
                $response['address'] = $this->contact->get('address', 'js');
                $this->logAction('contact_add', null, $this->contact->getId());
            }

            // Update recently added menu item
            $name = waContactNameField::formatName($this->contact);
            if($name || $name === '0') {
                $history = new contactsHistoryModel();
                $history->save('/contact/'.$this->contact->getId(), $name, $this->id ? null : 'add');
                $history = $history->get(); // to update history in user's browser
            }
        }

        // Reload page with new language if user just changed it in own profile
        if ($this->contact->getId() == $this->getUser()->getId() && $oldLocale != $this->contact->getLocale()) {
            $response['reload'] = true;
        }

        $this->response = array(
           'errors' => $errors,
           'data' => $response,
        );
        if (isset($history)) {
            $this->response['history'] = $history;
        }
    }
    
    public function logContactEdit($old_data, $new_data)
    {
        $diff = array();
        wa_array_diff_r($old_data, $new_data, $diff);
        if (!empty($diff)) {
            $this->logAction('contact_edit', $diff, $this->contact->getId());
        }
    }
}

// EOF