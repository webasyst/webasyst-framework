<?php

class teamUserInviting extends teamInviting
{
    protected $contact_data = [];

    public function __construct(array $contact_data, array $options = [])
    {
        $this->contact_data = $contact_data;
        parent::__construct($options);
    }

    public function createInvitation()
    {
        $result = parent::createInvitation();
        if (!empty($this->options['generate_waid_code'])
            && !empty($result['status'])
            && !empty($result['details']['token'])
            && (new waWebasystIDClientManager())->isConnected()
        ) {
            $invitation = (new waWebasystIDApi())->installationCode($result['details']['token']);
            if (empty($invitation['error'])) {
                $result['details']['invitation_code'] = $invitation['code'];
                $result['details']['invitation_expire'] = $invitation['expire'];
            } else {
                (new waAppTokensModel())->deleteById($result['details']['token']);
                return $this->fail('token_not_created', [
                    'api_error' => $invitation['error'],
                    'api_description' => ifset($invitation, 'error_description', 'Unknown WAID API error.'),
                ] + (empty($invitation['delay']) ? [] : [
                    'invitation_delay' => $invitation['delay'],
                ]));
            }
        }
        return $result;
    }

    protected function createContactToken($id, array $data = [])
    {
        if (!empty($this->options['generate_waid_code'])) {
            $data['token_type'] = 'waid_invite';
        }
        return parent::createContactToken($id, $data);
    }

    protected function createInvitationToken()
    {
        if (!isset($this->contact_data['id'])) {
            $contact = $this->createContact($this->contact_data);
            $this->contact_data['id'] = $contact;
        } else {
            $contact = $this->getContact($this->contact_data['id']);
        }

        $result = $this->validateContact($contact);

        if (!$result['status']) {
            return $result;
        }

        $token_data = $this->prepareData();

        $token = $this->getContactToken($contact->getId());
        if (!$token) {
            $token = $this->createContactToken($contact->getId(), $token_data);
        }
        if (!$token) {
            return $this->fail('token_not_created');
        }

        $this->ensureTokensLimit($token);

        return $this->ok([
            'token'  => $token,
            'contact_info' => $this->contact_data
        ]);
    }

    /**
     * @param array $data
     * @return waContact
     * @throws waException
     */
    protected function createContact(array $data = [])
    {
        $contact = new waContact();
        if (empty($data['create_method'])) {
            $data['create_method'] = 'invite';
        }
        $contact->save($data);
        return $contact;
    }

    protected function getContact($id)
    {
        return new waContact($id);
    }
}
