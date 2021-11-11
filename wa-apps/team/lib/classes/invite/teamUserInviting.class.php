<?php

class teamUserInviting extends teamInviting
{
    protected $contact_data = [];

    public function __construct(array $contact_data, array $options = [])
    {
        $this->contact_data = $contact_data;
        parent::__construct($options);
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
        $contact->save($data);
        return $contact;
    }

    protected function getContact($id)
    {
        return new waContact($id);
    }
}
