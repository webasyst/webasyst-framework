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
            $token_data = teamUser::createContactToken($contact_id);
            if ($token_data && isset($token_data['token'])) {
                $this->response = [
                    'contact_id'      => $contact_id,
                    'invitation_link' => waAppTokensModel::getLink($token_data['token']),
                    'contact_url'     => wa()->getUrl() . 'id/' . $contact_id . '/',
                ];
            }
        } else {

            $data = $this->getData();
            if ($errors = $this->validateData($data)) {
                $this->errors = $errors;
                return;
            }

            $result = (new teamUserInviting($data, ['groups' => $this->getGroups()]))->createInvitation();
            if (!$result['status']) {
                $this->errors = [
                    'fail' => $result['details']
                ];
                return;
            }

            $result['details']['contact_url'] = wa()->getUrl() . 'id/' . $result['details']['contact_id'] . '/';
            $this->response = waUtils::extractValuesByKeys($result['details'], [
                'contact_id',
                'invitation_link',
                'contact_url',
            ]);
        }
    }

    protected function getData()
    {
        $data = [];
        foreach (['firstname', 'middlename', 'lastname'] as $field) {
            $data[$field] = $this->getRequest()->post($field, '', waRequest::TYPE_STRING_TRIM);
        }
        return $data;
    }

    protected function validateData(array $data = [])
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
            return [
                'firstname' => _w('Name is required')
            ];
        }

        return [];
    }
}
