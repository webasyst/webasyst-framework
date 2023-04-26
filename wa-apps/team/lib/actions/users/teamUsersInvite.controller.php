<?php

/**
 * Принимает электронное письмо или номер телефона
 * для отправки приглашения новому пользователю
 * или немедленного создания пользователя
 */
class teamUsersInviteController extends teamUsersNewUserController
{
    public function execute()
    {
        $this->invite($this->getEmail(), $this->getPhone(), $this->getGroups(), $this->getContactId());
    }

    public function invite($email, $phone, $groups, $contact_id)
    {
        $event_data = compact('email', 'phone', 'groups');
        $this->runInviteUserHook($event_data);
        if ($this->errors) {
            return;
        }
        if (!empty($phone)) {
            $result = (new teamUserInvitingByPhone($phone, ['groups' => $groups]))->invite();
        } else {
            $result = (new teamUserInvitingByEmail($email, [
                'groups' => $groups,
                'contact_id' => $contact_id,
            ]))->invite();
        }

        if (!$result['status']) {
            $this->onFailedInviting($result);
        } else {
            $this->onSuccessInviting($result);
        }
    }

    protected function onFailedInviting(array $result)
    {
        if ($result['details']['error'] === 'token_not_created') {
            throw new waException('Something not found');
        }

        $this->errors[] = ifset($result['details']['description'], $result['details']['error']);
    }

    protected function onSuccessInviting(array $result)
    {
        $this->logAction('user_invite', null, $result['details']['contact_id']);
        $this->response = array(
            'contact_id'  => $result['details']['contact_id'],
            'contact_url' => wa()->getUrl().'id/'.$result['details']['contact_id'].'/',
        );

        $contact_data = $this->getAdditionalContactData();
        if ($contact_data) {
            $c = new waContact($result['details']['contact_id']);
            $c->save($contact_data);
        }
    }

    /**
     * @param array $event_data
     * @return void
     * @throws waException
     */
    protected function runInviteUserHook($event_data)
    {
        $invite_user_event = wa()->event('invite_user', $event_data);
        foreach ($invite_user_event as $message) {
            if ($message) {
                $this->errors[] = [$message, 'general'];
            }
        }
    }
}
