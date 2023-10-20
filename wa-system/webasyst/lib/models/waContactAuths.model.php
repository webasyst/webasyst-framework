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
        wa()->getStorage()->open();
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
        wa()->getStorage()->open();
        $session_id = session_id();
        if ($session_id) {
            $this->query(
                "
                    INSERT INTO wa_contact_auths (contact_id, token, session_id, login_datetime, user_agent)
                    VALUES (:contact_id, :token, :session_id, :time, :user_agent)
                    ON DUPLICATE KEY UPDATE
                        contact_id=VALUES(contact_id),
                        token=VALUES(token),
                        user_agent=VALUES(user_agent),
                        last_datetime=VALUES(login_datetime)
                ",
                [
                    'contact_id' => $contact_id,
                    'session_id' => session_id(),
                    'token' =>  $token,
                    'time' => date('Y-m-d H:i:s'),
                    'user_agent' => wa()->getView()->getHelper()->userAgent()
                ]
            );

            /*
             * Clean up old auth sessions.
             *
             * Some people seem to create a new session with each request. (How?..)
             * Such contact_ids have a lot of auth rows with last_datetime being NULL.
             * We clean those up after 24 hours.
             *
             * Most people have few auth rows, one per browser. Such rows have last_datetime properly set.
             * Those have a lifetime of 30 days.
             */
            $this->exec("
                DELETE FROM wa_contact_auths
                WHERE contact_id=?
                    AND (
                        (last_datetime IS NULL AND login_datetime < ?)
                        OR
                        (last_datetime < ?)
                    )
            ", [
                $contact_id,
                date('Y-m-d H:i:s', time() - 3600*24),
                date('Y-m-d H:i:s', time() - 3600*24*30),
            ]);
        }
    }

    public function updateLastDatetime($contact_id)
    {
        wa()->getStorage()->open();
        $sql = 'UPDATE wa_contact_auths SET last_datetime = :time WHERE contact_id = :contact_id and session_id = :session_id';
        $this->query($sql, [
            'contact_id' => $contact_id,
            'session_id' => session_id(),
            'time' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param $contact_id
     * @return void
     * @throws waDbException
     */
    public function deleteContactAuth($contact_id)
    {
        wa()->getStorage()->open();
        $sql = 'DELETE FROM wa_contact_auths WHERE contact_id = :contact_id and session_id = :session_id';
        $this->query($sql, ['contact_id' => $contact_id, 'session_id' => session_id()]);
    }
}
