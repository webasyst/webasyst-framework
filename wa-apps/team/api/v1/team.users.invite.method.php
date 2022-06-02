<?php

class teamUsersInviteMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $phone = $this->getPhone();
        if (!empty($phone)) {
            $result = (new teamUserInvitingByPhone($phone))->createInvitation();
        } else {
            $email = $this->getEmail();
            if ($this->needSend()) {
                $result = (new teamUserInvitingByEmail($email))->invite();
            } else {
                $result = (new teamUserInvitingByEmail($email))->createInvitation();
            }
        }

        if (!$result['status']) {
            $status_code = $this->getStatusCodeByError($result['details']['error']);
            throw new waAPIException(
                $result['details']['error'],
                $result['details']['description'],
                $status_code,
                $this->extractFieldsExcept($result['details'], ['error', 'description'])
            );
        }

        $this->response = waUtils::extractValuesByKeys($result['details'], [
            'contact_id',
            'invitation_link',
        ]);
    }

    protected function getEmail()
    {
        return strval($this->post('email', true));
    }

    protected function getPhone()
    {
        return strval($this->post('phone'));
    }

    protected function needSend()
    {
        $send = waRequest::post('send');
        $send = strtolower(is_scalar($send) ? trim(strval($send)) : '');
        if ($send === 'true') {
            return true;
        } elseif ($send === 'false') {
            return false;
        } else {
            return boolval($send);
        }
    }

    protected function getStatusCodeByError($error)
    {
        switch ($error) {
            case 'token_not_created':
                return 500;
            case 'user_in_team':
            case 'contact_banned':
                return 409;
            default:
                return 400;
        }
    }

    protected function extractFieldsExcept(array $input, array $fields)
    {
        $map = array_fill_keys($fields, true);
        foreach ($input as $key => $_) {
            if (isset($map[$key])) {
                unset($input[$key]);
            }
        }
        return $input;
    }
}
