<?php

/**
 * Create user and create invitation WITHOUT sending it by email
 */
class teamUsersCreateInvitationController extends teamUsersNewUserController
{
    public function execute()
    {
        $contact_id = $this->getRequest()->post('contact_id', '', waRequest::TYPE_INT);

        if ($contact_id) {
            $errors = [];
            $additional_contact_data = [];
            $data = [
                'id' => $contact_id,
            ];
        } else {
            list($data, $additional_contact_data) = $this->getData();
            list($data, $additional_contact_data) = $this->sanitizeData($data, $additional_contact_data);
            if ($errors = $this->validateData($data)) {
                $this->errors = $errors;
                return;
            }
        }

        $event_data = compact('data');
        $this->runCreateInvitationHook($event_data);
        if ($this->errors) {
            return;
        }

        $result = (new teamUserInviting($data, [
            'generate_waid_code' => (bool) $this->getRequest()->request('by_code'),
            'groups' => $this->getGroups(),
        ]))->createInvitation();

        if (!$result['status']) {
            $err = $result['details'];
            $this->errors = [
                'general' => $err['description'],
            ];
            if (ifset($err, 'error', null) === 'token_not_created') {
                $this->errors['general'] .= ' '.sprintf_wp('API error: %s', $err['api_description']);
            }
            return;
        }

        if (!empty($additional_contact_data)) {
            $c = new waContact($result['details']['contact_id']);
            $c->save($additional_contact_data);
        }

        $result['details']['contact_url'] = wa()->getUrl() . 'id/' . $result['details']['contact_id'] . '/';
        $this->response = waUtils::extractValuesByKeys($result['details'], [
            'contact_id',
            'invitation_link',
            'contact_url',
            'invitation_code',
            'invitation_expire',
        ]);
    }

    protected function getData()
    {
        $additional_contact_data = $this->getAdditionalContactData();

        $data = [];
        foreach (['firstname', 'middlename', 'lastname'] as $field) {
            $data[$field] = $this->getRequest()->post($field, '', waRequest::TYPE_STRING_TRIM);
            if (!strlen($data[$field]) && isset($additional_contact_data[$field])) {
                $data[$field] = $additional_contact_data[$field];
            }
            unset($additional_contact_data[$field]);
        }

        return [$data, $additional_contact_data];
    }

    protected function sanitizeData(array $data = [], array $additional_contact_data = [])
    {
        $at_least_one_required = ['firstname', 'middlename', 'lastname'];

        $ok = false;
        foreach ($at_least_one_required as $field) {
            if (isset($data[$field]) && strlen($data[$field]) > 0) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            $data['firstname'] = date('Y-m-d');
        }

        return [$data, $additional_contact_data];
    }

    protected function validateData(array $data = [])
    {
        return []; // no validation for now
    }

    protected function runCreateInvitationHook($event_data)
    {
        $event_results = wa('team')->event('create_invitation', $event_data);
        foreach ($event_results as $message) {
            if ($message) {
                $this->errors['general'] = $message;
            }
        }
    }

}
