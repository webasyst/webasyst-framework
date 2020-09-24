<?php

class webasystSettingsWaIDInviteUserController extends waJsonController
{
    public function execute()
    {
        $user_to_invite = $this->getUserToInvite();
        if (!$user_to_invite) {
            $this->errors = _ws('User not found.');
            return;
        }

        $sm = new waAppSettingsModel();

        // Try get 'sender' email
        $email = $sm->get('webasyst', 'sender', '');
        $v = new waEmailValidator(array('required'=>true));
        if (!$v->isValid($email)) {
            $email_settings_url = wa()->getAppUrl('webasyst') . 'webasyst/settings/email/';
            $this->errors = sprintf(_ws('The system sender email address is not configured. Save it in “<a href="%s">Email settings</a>” section.'), $email_settings_url);
            return;
        }

        $inviting = new waWebasystIDUserInviting([
            'sender' => $email
        ]);

        $sent = $inviting->sendInvitation($user_to_invite);
        if (!$sent) {
            $this->errors = _ws('Sending failure.');
            return;
        }

        $csm = new waContactSettingsModel();

        $sent_datetime = date('Y-m-d H:i:s');
        $csm->set($user_to_invite['id'], 'webasyst', 'waid_invite_datetime', $sent_datetime);

        $this->response = [
            'sent' => wa_date('humandatetime', $sent_datetime)
        ];

    }

    protected function getUserToInvite()
    {
        $id = $this->getRequest()->post('id');

        $col = new waContactsCollection("id/" . $id);
        $col->addLeftJoin('wa_contact_waid', null, ':table.contact_id IS NULL');
        $col->addWhere('c.is_user=1');

        $users = $col->getContacts("id,firstname,middlename,lastname,name,login,is_user,email,locale,photo_url_32,photo");

        $user = reset($users);
        $emails = (array)$user['email'];
        $user['email'] = reset($emails);

        return $user;
    }
}
