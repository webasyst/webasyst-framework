<?php

abstract class teamInviting
{
    protected $options = [];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($options, [
            'tokens_limit' => 5
        ]);

        $this->options['tokens_limit'] = intval($this->options['tokens_limit']);
        if ($this->options['tokens_limit'] <= 1) {
            $this->options['tokens_limit'] = 1;
        }
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
     *          int     $result['details']['contact_id']
     *          string  $result['details']['invitation_link']
     *          string  $result['details']['token']
     */
    public function createInvitation()
    {
        $result = $this->createInvitationToken();
        if (!$result['status']) {
            return $result;
        }
        $token = $result['details']['token'];
        return $this->ok([
            'contact_id' => $token['contact_id'],
            'token' => $token['token'],
            'invitation_link' => waAppTokensModel::getLink($token)
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
    abstract protected function createInvitationToken();

    /**
     * @return array
     */
    protected function prepareData()
    {
        $data = ['full_access' => false];

        $groups = [];
        if (array_key_exists('groups', $this->options) && is_array($this->options['groups'])) {
            $groups = $this->options['groups'];
        }

        if ($groups) {
            $data_groups = [];
            foreach ($groups as $id) {
                if (wa_is_int($id) && $this->canManageGroup($id)) {
                    $data_groups[] = $id;
                }
            }
            $data['groups'] = $data_groups;
        }

        return $data;
    }

    /**
     * @param int $id
     * @throws waException
     */
    protected function getContactToken($id)
    {
        return teamUser::getInviteTokens($id);
    }

    protected function createContactToken($id, array $data = [])
    {
        return teamUser::createContactToken($id, $data);
    }

    protected function canManageGroup($id)
    {
        return teamHelper::hasRights('manage_group.'.$id);
    }

    protected function validateContact($contact_info)
    {
        if ($contact_info && $contact_info['is_user']) {
            if (teamHelper::isBanned($contact_info)) {
                return $this->fail('contact_banned', [
                    'contact_id' => $contact_info['id']
                ]);
            } else {
                return $this->fail('user_in_team', [
                    'contact_id' => $contact_info['id']
                ]);
            }
        }
        return $this->ok();
    }

    protected function validateEmail($email)
    {
        $v = new waEmailValidator();
        $error = null;
        if (!$email) {
            $error = 'email_required';
        } else {
            if (!$v->isValid($email)) {
                $error = 'email_invalid';
            }
        }
        return $error;
    }

    protected function validatePhone($phone)
    {
        $v = new waPhoneNumberValidator();
        $error = null;
        if (!$phone) {
            $error = 'phone_required';
        } else {
            if (!$v->isValid($phone)) {
                $error = 'phone_invalid';
            }
        }

        return $error;
    }

    protected function ensureTokensLimit(array $token)
    {
        $atm = new waAppTokensModel();

        $condition = [
            'app_id' => 'team',
            'type' => 'user_invite',
            'contact_id' => $token['contact_id']
        ];

        $tokens = $atm->query(
            "SELECT token FROM {$atm->getTableName()}
                    WHERE app_id = :app_id AND type = :type AND contact_id = :contact_id
                    ORDER BY create_datetime DESC
                    LIMIT {$this->options['tokens_limit']}",
            $condition)
            ->fetchAll(null, true);

        if ($tokens) {
            $atm->exec(
                "DELETE FROM {$atm->getTableName()}
                    WHERE app_id = :app_id AND type = :type AND contact_id = :contact_id AND token NOT IN (:tokens)",
                array_merge($condition, [
                    'tokens' => $tokens
                ])
            );
        }

    }

    protected function getTime()
    {
        return time();
    }

    protected function ok(array $details = [])
    {
        return [
            'status' => true,
            'details' => $details
        ];
    }

    protected function getErrorDescription($error)
    {
        switch ($error) {
            case 'token_not_created':
                return _w("Invitation token cannot be created.");
            case 'user_in_team':
                return _w('Already in our team!');
            case 'contact_banned':
                return _w('This contact was banned.');
            case 'email_required':
            case 'phone_required':
                return _w('This is a required field.');
            case 'email_invalid':
                return _w('This does not look like a valid email address.');
            case 'phone_invalid':
                return _w('This does not look like a valid phone number.');
            case 'webasyst_id_required':
                return _w('This installation is not connected to Webasyst ID.');
            default:
                return $error;
        }
    }

    protected function fail($error, array $extra_details = [])
    {
        return [
            'status' => false,
            'details' => array_merge([
                'error' => $error,
                'description' => $this->getErrorDescription($error) // default error description
            ], $extra_details)
        ];
    }
}
