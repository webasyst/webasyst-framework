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

        $response = array();
        if (!$errors = $this->contact->save($data, true)) {
            if ($this->id) {
                foreach ($data as $field_id => $field_value) {
                    if (!isset($errors[$field_id])) {
                        $response[$field_id] = $this->contact->get($field_id, 'js');
                    }
                }
                $response['name'] = $this->contact->get('name', 'js');
                $fields = array('email', 'phone', 'im');
                $top = array();
                foreach ($fields as $f) {
                    if ($v = $this->contact->get($f, 'top,html')) {
                        $top[] = array(
                            'id' => $f,
                            'name' => waContactFields::get($f)->getName(),
                            'value' => is_array($v) ? implode(', ', $v) : $v,
                        );
                    }
                }
                $response['top'] = $top;
            } else {
                $response = array('id' => $this->contact->getId());
                $this->log('contact_add', 1);
            }

            // Update recently added menu item
            if( ( $name = $this->contact->get('name')) || $name === '0') {
                $name = trim($this->contact->get('title').' '.$name);
                $history = new contactsHistoryModel();
                $history->save('/contact/'.$this->contact->getId(), $name, $this->id ? null : 'add');
                $history = $history->get(); // to update history in user's browser
            }
        }

        // Reload page with new language if user just changed it in own profile
        if ($this->contact->getId() == $this->getUser()->getId() && $oldLocale != $this->contact->getLocale()) {
            $response['reload'] = TRUE;
        }

        $this->response = array(
           'errors' => $errors,
           'data' => $response,
        );
        if (isset($history)) {
            $this->response['history'] = $history;
        }
    }
}

// EOF