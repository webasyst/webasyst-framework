<?php

class teamWelcomeSaveController extends waJsonController
{
    public function execute()
    {
        if (!teamHelper::hasRights('add_users')) {
            throw new waRightsException();
        }
        $post_data = waRequest::post('data', array(), waRequest::TYPE_ARRAY_TRIM);
        if (!$post_data) {
            throw new waException('Data not found');
        }
        $create = array();

        $validator = new waEmailValidator();
        foreach ($post_data as $i => $row) {
            if (!$validator->isValid($row['email'])) {
                $this->errors[] = array('name' => "data[$i][email]", 'text' => _w('Invalid email address'));
            }
            $cm = new waContactModel();
            $contact_info = $cm->getByEmail($row['email']);
            if ($contact_info) {
                if ($contact_info['is_user']) {
                    $this->errors[] = array(
                        'name' => "data[$i][email]",
                        'text' => !teamHelper::isBanned($contact_info) ? _w('Already in our team!') : _w('This contact was banned'),
                    );
                } else {
                    $create[] = array(
                        'contact_id' => $contact_info['id'],
                        'email'      => $row['email'],
                        'access'     => $row['access'] == 'true'
                    );
                }
            } else {
                $create[] = array('contact_id' => null, 'email' => $row['email'], 'access' => $row['access'] == 'true');
            }
        }

        if ($this->errors || !$create) {
            return;
        }
        foreach ($create as $c) {

            if ($c['contact_id']) {
                $token = teamUser::createContactToken($c['contact_id'], array('full_access' => $c['access']));
            } else {
                $token = teamUser::createContactByEmail($c['email'], array('full_access' => $c['access']));
            }
            if ($token) {
                try {
                    $hours = ceil((strtotime($token['expire_datetime']) - time()) / 3600);
                    teamHelper::sendEmailSimpleTemplate(
                        $c['email'],
                        'welcome_invite',
                        array(
                            '{LOCALE}'       => wa()->getLocale(),
                            '{CONTACT_NAME}' => htmlentities(wa()->getUser()->getName(),ENT_QUOTES,'utf-8'),
                            '{CONTACT_ID}'   => $token['contact_id'],
                            '{COMPANY}'      => htmlentities(wa()->accountName(),ENT_QUOTES,'utf-8'),
                            '{LINK}'         => waAppTokensModel::getLink($token),
                            '{HOURS_LEFT}'   => _w('%d hour', '%d hours', $hours),
                            '{DOMAIN}'       => waRequest::server('HTTP_HOST'),
                            '{EXPIRE_DATE}'  => waDateTime::format('date', strtotime('-1 day', $token['expire_datetime'])),
                        ) // , wa()->getUser()->get('email', 'default')
                    );
                } catch (waException $e) {
                }
            }
        }
    }
}
