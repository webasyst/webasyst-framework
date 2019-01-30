<?php

/** Save contact data that came from profile form. */
class webasystProfileSaveController extends waJsonController
{
    /** @var waContact */
    protected $id = null;
    protected $contact;

    public function execute()
    {
        $this->contact = $this->getContact();
        $this->id = $this->contact->getId();

        $data = $this->getData();
        if (!$data) {
            $this->response = array(
               'errors' => array(),
               'data' => array(),
            );
            return;
        }

        // Validate and save contact if no errors found
        $this->save($data, $response, $errors);

        $this->response = array(
           'errors' => $errors,
           'data' => $response,
        );
    }

    protected function getData()
    {
        $data = json_decode(waRequest::post('data', '[]', 'string'), true);
        if (!$data || !is_array($data)) {
            return null;
        }

        // Make sure only allowed fields are saved
        $allowed = array();
        foreach(waContactFields::getAll('person') as $f) {
            if ($f->getParameter('allow_self_edit')) {
                $allowed[$f->getId()] = true;
            }
        }

        return array_intersect_key($data, $allowed);
    }

    protected function save($data, &$response, &$errors)
    {
        // get old data for logging
        if ($this->id) {
            $old_data = array();
            $oldLocale = $this->contact->getLocale();
            foreach ($data as $field_id => $field_value) {
                $old_data[$field_id] = $this->contact->get($field_id);
            }
        }

        $response = array();
        $errors = $this->contact->save($data, true);
        if ($errors) {
            return;
        }

        // New data formatted for JS
        $new_data = array();
        foreach ($data as $field_id => $field_value) {
            $response[$field_id] = $this->contact->get($field_id, 'js');
            $new_data[$field_id] = $this->contact->get($field_id);
        }

        $response['id'] = $this->contact->getId();
        $response['top'] = $this->contact->getTopFields();
        if (!isset($response['name'])) {
            $response['name'] = $this->contact->get('name', 'js');
        }

        // Reload page with new language if user just changed it in own profile
        if ($this->contact->getId() == wa()->getUser()->getId() && $oldLocale != $this->contact->getLocale()) {
            $response['reload'] = TRUE;
        }

        // Log the action
        if ($this->id) {
            $diff = array();
            wa_array_diff_r($old_data, $new_data, $diff);
            if (!empty($diff)) {
                $this->logAction('contact_edit', $diff, $this->contact->getId());
            }
        }
    }

    protected function getContact()
    {
        return wa()->getUser();
    }
}
