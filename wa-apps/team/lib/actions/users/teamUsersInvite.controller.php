<?php

/**
 * Accepts an Email to send new user invitation to or create user right away
 */
class teamUsersInviteController extends teamUsersNewUserController
{
    public function execute()
    {
        $this->invite($this->getEmail(), $this->getGroups());
    }

    public function invite($email, $groups)
    {
        $result = (new teamUserInvitingByEmail($email, ['groups' => $groups]))->invite();

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

        $this->errors[] = $result['details']['error'];
    }

    protected function onSuccessInviting(array $result)
    {
        $this->logAction('user_invite', null, $result['details']['contact_id']);
        $this->response = array(
            'contact_id'  => $result['details']['contact_id'],
            'contact_url' => wa()->getUrl().'id/'.$result['details']['contact_id'].'/',
        );
    }
}
