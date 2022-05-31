<?php

class teamUserInvitingByPhone extends teamInviting
{
    protected $phone;
    protected $options = [];

    /**
     * teamUserInviting constructor.
     * @param string $phone
     * @param array $options
     *      int[]   $options['groups'] - default is empty list
     *      int     $options['tokens_limit'] - max number of tokens that can exist at the same time
     */
    public function __construct($phone, array $options = [])
    {
        $this->phone = $phone;
        parent::__construct($options);
    }

    /**
     * Invite user by sending phone invitation
     * @return array $result
     *      bool $result['status']
     *      array $result['details']
     *
     *      IF $result['status'] === FALSE:
     *          string $result['details']['error']
     *          string $result['details']['description'] [optional]
     *          int    $result['details']['contact_id'] [optional]
     *
     *      IF $result['status'] === TRUE:
     *          int $result['details']['contact_id']
     */
    public function invite()
    {
        if (!(new waWebasystIDClientManager())->isConnected()) {
            return $this->fail('webasyst_id_required');
        }

        $result = $this->createInvitationToken();
        if (!$result['status']) {
            return $result;
        }

        $token = $result['details']['token'];

        return $this->ok([
            'contact_id'  => $token['contact_id']
        ]);
    }

    /**
     * Create invitation (without sending it)
     * @return array $result
     *      bool $result['status']
     *      array $result['details']
     *
     *      IF $result['status'] === FALSE:
     *          string $result['details']['error']
     *          string $result['details']['description'] [optional]
     *          int    $result['details']['contact_id'] [optional]
     *
     *      IF $result['status'] === TRUE:
     *          array   $result['details']['token']
     *          array   $result['details']['contact_info']
     */
    protected function createInvitationToken()
    {
        $error = $this->validatePhone($this->phone);
        if ($error) {
            return $this->fail($error);
        }

        $contact_info = $this->findUserByPhone($this->phone);
        $result = $this->validateContact($contact_info);
        if (!$result['status']) {
            return $result;
        }

        $data = $this->prepareData();

        if ($contact_info) {
            $token = $this->createContactToken($contact_info['id'], $data);
        } else {
            $token = $this->createContactByPhone($data);
        }

        if (!$token) {
            return $this->fail('token_not_created');
        }

        $this->ensureTokensLimit($token);

        return $this->ok([
            'token'        => $token,
            'contact_info' => $contact_info
        ]);
    }

    /**
     * @param array $data
     * @return array|false|null
     */
    protected function createContactByPhone(array $data)
    {
        return teamUser::createContactByPhone($this->phone, $data);
    }

    /**
     * @param $phone
     * @return array[0]array|null Found user or null if not
     * @return array[1]null|string Error
     */
    protected function findUserByPhone($phone)
    {
        $cm = new waContactModel();

        return $cm->getByPhone($phone);
    }
}
