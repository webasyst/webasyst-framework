<?php

class webasystSettingsWaIDInviteUsersController extends waLongActionController
{
    /**
     * @var waContactSettingsModel
     */
    protected $csm;

    /**
     * @var waWebasystIDUserInviting
     */
    protected $inviting;

    public function __construct()
    {
        $this->csm = new waContactSettingsModel();

        $sm = new waAppSettingsModel();

        // Try get 'sender' email
        $email = $sm->get('webasyst', 'sender', '');
        $v = new waEmailValidator(array('required'=>true));
        if (!$v->isValid($email)) {
            $email_settings_url = wa()->getAppUrl('webasyst') . 'webasyst/settings/email/';
            $this->sendJson([
                'error' => sprintf(_ws('The system sender email address is not configured. Save it in “<a href="%s">Email settings</a>” section.'), $email_settings_url)
            ]);
        }

        $this->inviting = new waWebasystIDUserInviting([
            'sender' => $email
        ]);
    }

    protected function init()
    {
        $users = $this->getNotInvitedUsers();
        $this->data = [
            'users' => $users,
            'total' => count($users),       // how much users need to invite
            'done' => 0,                    // how much done users (invited)
            'count' => 0,                   // total count of processed users (among them could be not done)
            'sent' => [],                   // id => datetime of users to whom emails has been sent
        ];
    }

    protected function isDone()
    {
        return empty($this->data['users']) || $this->data['count'] >= $this->data['total'];
    }

    protected function step()
    {
        $steps_limit = 10;
        $remaining_limit = 2;

        $step_num = 0;
        foreach ($this->data['users'] as $id => $user) {
            $step_num++;
            if ($step_num > $steps_limit || $this->remaining_exec_time < $remaining_limit) {
                break;
            }

            $this->data['count']++;

            unset($this->data['users'][$id]);

            $sent = $this->inviting->sendInvitation($user);
            if ($sent) {
                $this->data['done']++;

                $sent_datetime = date('Y-m-d H:i:s');
                $this->csm->set($user['id'], 'webasyst', 'waid_invite_datetime', $sent_datetime);
                $this->data['sent'][$user['id']] = wa_date('humandatetime', $sent_datetime);
            }
        }

        return false;
    }



    protected function finish($filename)
    {
        return $this->isDone() && $this->getRequest()->post('cleanup');
    }

    protected function infoReady($filename)
    {
        $report = sprintf( _ws('%d invitation email has been sent.', '%d invitation emails have been sent.', $this->data['done'], false), $this->data['done'] );

        $this->info([
            'ready' => true,
            'report' => $report
        ]);
    }

    protected function info(array $params = [])
    {
        $result = [
            'processId' => $this->processId,
            'total' => $this->data['total'],
            'done' => $this->data['done'],
            'count' => $this->data['count'],
            'progress' => $this->isDone() ? 100 : $this->data['count'] * 100 / $this->data['total'],
            'sent' => $this->data['sent'],
            'ready' => false,
        ];

        $result = array_merge($result, $params);
        $this->sendJson($result);
    }

    protected function sendJson(array $data = [])
    {
        $this->getResponse()->addHeader('Content-type', 'application/json', true);
        $this->getResponse()->sendHeaders();
        die (json_encode($data));
    }

    protected function getNotInvitedUsers()
    {
        $datetime = date('Y-m-d H:i:s', strtotime('-10 min'));

        $col = new waContactsCollection("search/is_user=1");

        $col->addLeftJoin('wa_contact_waid', null, ':table.contact_id IS NULL');
        $col->addLeftJoin('wa_contact_settings', ":table.contact_id = c.id AND :table.app_id='webasyst' AND :table.name='waid_invite_datetime'",
            ":table.value IS NULL OR STR_TO_DATE(:table.value, '%Y-%m-%d %H:%i:%s') < '{$datetime}'");

        $current_user_id = $this->getUserId();
        $col->addWhere("c.id != {$current_user_id}");

        $count = $col->count();

        $col->orderBy('name');

        $users = $col->getContacts("id,firstname,middlename,lastname,name,login,is_user,email,locale,photo_url_32,photo", 0, $count);
        foreach ($users as $id => &$user) {
            if (empty($user['email'])) {
                unset($users[$id]);
                continue;
            }
            $emails = (array)$user['email'];
            $user['email'] = reset($emails);
        }
        unset($user);

        return $users;
    }
}
