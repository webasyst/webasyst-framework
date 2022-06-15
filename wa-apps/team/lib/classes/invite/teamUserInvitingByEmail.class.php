<?php

class teamUserInvitingByEmail extends teamInviting
{
    protected $email;
    protected $options = [];

    /**
     * teamUserInviting constructor.
     * @param string $email
     * @param array $options
     *      int[]   $options['groups'] - default is empty list
     *      int     $options['tokens_limit'] - max number of tokens that can exist at the same time
     */
    public function __construct($email, array $options = [])
    {
        $this->email = $email;
        parent::__construct($options);
    }

    /**
     * Invite user by sending email invitation
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
        $result = $this->createInvitationToken();
        if (!$result['status']) {
            return $result;
        }

        $email = $this->email;
        $token = $result['details']['token'];
        $contact_info = $result['details']['contact_info'];
        $app_info = wa()->getAppInfo();

        try {
            $hours = ceil((strtotime($token['expire_datetime']) - $this->getTime()) / 3600);
            $locale = $contact_info && !empty($contact_info['locale']) ? $contact_info['locale'] : wa()->getLocale();
            $this->sendInvitationEmail($email, [
                '{LOCALE}'       => $locale,
                '{CONTACT_NAME}' => htmlentities(wa()->getUser()->getName(),ENT_QUOTES,'utf-8'),
                '{CONTACT_ID}'   => $token['contact_id'],
                '{COMPANY_SUB}'  => wa()->accountName(),
                '{COMPANY}'      => htmlentities(wa()->accountName(),ENT_QUOTES,'utf-8'),
                '{LINK}'         => waAppTokensModel::getLink($token),
                '{HOURS_LEFT}'   => _w('%d hour', '%d hours', $hours),
                '{WA_URL}'       => wa()->getRootUrl(true),
                '{WA_APP_NAME}'  => htmlentities($app_info['name'],ENT_QUOTES,'utf-8'),
            ]);
        } catch (waException $e) {
            return $this->fail('email_send_fail', [
                'contact_id'  => $token['contact_id'],
                'description' => $e->getMessage()
            ]);
        }

        return $this->ok([
            'contact_id'  => $token['contact_id'],
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
        $email = $this->email;
        $error = $this->validateEmail($email);
        if ($error) {
            return $this->fail($error);
        }

        $contact_info = $this->findUserByEmail($email);
        $result = $this->validateContact($contact_info);
        if (!$result['status']) {
            return $result;
        }

        $data = $this->prepareData();

        if ($contact_info) {
            $token = $this->createContactToken($contact_info['id'], $data);
        } else {
            $token = $this->createContactByEmail($email, $data);
        }

        if (!$token) {
            return $this->fail('token_not_created');
        }

        $this->ensureTokensLimit($token);

        return $this->ok([
            'token'  => $token,
            'contact_info' => $contact_info
        ]);
    }

    protected function sendInvitationEmail($email, array $vars = [])
    {
        return teamHelper::sendEmailSimpleTemplate(
            $email,
            'welcome_invite',
            $vars
        );
    }

    protected function createContactByEmail($email, array $data)
    {
        return teamUser::createContactByEmail($email, $data);
    }

    /**
     * @param $email
     * @return array[0]array|null Found user or null if not
     * @return array[1]null|string Error
     */
    protected function findUserByEmail($email)
    {
        $cm = new waContactModel();
        return $cm->getByEmail($email);
    }
}
