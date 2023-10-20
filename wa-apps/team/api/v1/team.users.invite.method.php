<?php

class teamUsersInviteMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $invitation_type = $this->post('type');
        $groups = waRequest::post('groups', [], waRequest::TYPE_ARRAY_TRIM);
        if ($invitation_type === 'code') {
            $contact_data = array_filter([
                'email' => $this->getEmail(false),
                'phone' => $this->getPhone(),
            ]);

            $result = (new teamUserInviting($contact_data, [
                'generate_waid_code' => true,
                'groups' => $groups,
            ]))->createInvitation();

            $result_fields = [
                'contact_id',
                'invitation_code',
                'invitation_expire',
            ];
        } else {
            $phone = $this->getPhone();
            if (!empty($phone)) {
                $result = (new teamUserInvitingByPhone($phone, ['groups' => $groups]))->createInvitation();
            } else {
                $email = $this->getEmail();
                if ($this->needSend()) {
                    $result = (new teamUserInvitingByEmail($email, ['groups' => $groups]))->invite();
                } else {
                    $result = (new teamUserInvitingByEmail($email, ['groups' => $groups]))->createInvitation();
                }
            }
            if ($result['status']) {
                $result['details']['invitation_expire'] = time() + 3600*24*3;
            }
            $result_fields = [
                'contact_id',
                'invitation_link',
                'invitation_expire',
            ];
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

        $this->response = waUtils::extractValuesByKeys($result['details'], $result_fields);
    }

    protected function getEmail($required=true)
    {
        return strval($this->post('email', $required));
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
