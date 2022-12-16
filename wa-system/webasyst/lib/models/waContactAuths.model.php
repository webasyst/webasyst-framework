<?php

class waContactAuthsModel extends waModel
{
    protected $table = 'wa_contact_auths';


    /**
     * @param $contact_id
     * @return bool
     * @throws waDbException
     */
    public function getSessionAuth($contact_id)
    {
        $sql = 'SELECT * FROM wa_contact_auths WHERE contact_id = :contact_id AND session_id = :session_id';
        return (boolean) $this->query($sql, ['contact_id' => $contact_id, 'session_id' => session_id()])->fetch();
    }

    /**
     * @param $id
     * @throws waDbException
     * @throws waException Insert row in wa_contact_auth
     */
    public function insertContactAuth($contact_id, $token)
    {
        $session_id = session_id();
        if ($session_id) {
            $this->query(
                "
                    INSERT IGNORE INTO wa_contact_auths (contact_id, token, session_id, login_datetime, user_agent)
                    VALUES (:contact_id, :token, :session_id, current_timestamp, :user_agent)
                ",
                [
                    'contact_id' => $contact_id,
                    'session_id' => session_id(),
                    'token' =>  $token,
                    'user_agent' => wa()->getView()->getHelper()->userAgent()
                ]
            );
        }
    }

    public function updateLastDatetime($contact_id)
    {
        $sql = 'UPDATE wa_contact_auths SET last_datetime = current_timestamp WHERE contact_id = :contact_id and session_id = :session_id';
        $this->query($sql, ['contact_id' => $contact_id, 'session_id' => session_id()]);
    }

    /**
     * @param $contact_id
     * @return void
     * @throws waDbException
     */
    public function deleteContactAuth($contact_id)
    {
        $sql = 'DELETE FROM wa_contact_auths WHERE contact_id = :contact_id and session_id = :session_id';
        $this->query($sql, ['contact_id' => $contact_id, 'session_id' => session_id()]);
    }
}
