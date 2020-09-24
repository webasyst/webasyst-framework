<?php

/**
 * Accepts an Email to send new user invitation to or create user right away
 */
class teamUsersInviteController extends teamUsersNewUserController
{
    public function execute()
    {
        $email = $this->getEmail();
        $error = $this->validateError($email);
        if ($error) {
            $this->errors[] = $error;
            return;
        }

        $groups = $this->getGroups();

        $contact_info = $this->findUserByEmail($email);
        $error = $this->validateContact($contact_info);
        if ($error) {
            $this->errors[] = $error;
            return;
        }

        $this->invite($email, $groups, $contact_info);
    }

    public function invite($email, $groups, $contact_info)
    {
        $data = array('full_access' => false);
        if ($groups) {
            $data_groups = array();
            foreach ($groups as $id) {
                if (teamHelper::hasRights('manage_group.'.$id)) {
                    $data_groups[] = $id;
                }
            }
            $data['groups'] = $data_groups;
        }
        if ($contact_info) {
            $token = teamUser::createContactToken($contact_info['id'], $data);
        } else {
            $token = teamUser::createContactByEmail($email, $data);
        }
        if (!$token) {
            throw new waException('Something not found');
        }
        try {
            $hours = ceil((strtotime($token['expire_datetime']) - time()) / 3600);
            $locale = !empty($contact_info['locale']) ? $contact_info['locale'] : wa()->getLocale();
            teamHelper::sendEmailSimpleTemplate(
                $email,
                'welcome_invite',
                array(
                    '{LOCALE}'       => $locale,
                    '{CONTACT_NAME}' => htmlentities(wa()->getUser()->getName(),ENT_QUOTES,'utf-8'),
                    '{CONTACT_ID}'   => $token['contact_id'],
                    '{COMPANY}'      => htmlentities(wa()->accountName(),ENT_QUOTES,'utf-8'),
                    '{LINK}'         => waAppTokensModel::getLink($token),
                    '{HOURS_LEFT}'   => _w('%d hour', '%d hours', $hours),
                ) // , wa()->getUser()->get('email', 'default')
            );
        } catch (waException $e) {
        }
        $this->logAction('user_invite', null, $token['contact_id']);

        $this->response = array(
            'contact_id'  => $token['contact_id'],
            'contact_url' => wa()->getUrl().'id/'.$token['contact_id'].'/',
        );
    }
}
