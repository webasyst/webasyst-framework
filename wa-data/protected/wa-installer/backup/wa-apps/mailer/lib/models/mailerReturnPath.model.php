<?php

/**
 * Storage for return-path mailboxes.
 */
class mailerReturnPathModel extends waModel
{
    protected $table = 'mailer_return_path';

    public function getByEmail($email)
    {
        return $this->getByField('email', $email);
    }

    public function updateByEmail($email, $data, $options = null, $return_object = false)
    {
        return $this->updateByField('email', $email, $data, $options, $return_object);
    }

    public function getActive()
    {
        $sql = "SELECT * FROM {$this->table} WHERE last_campaign_date >= :date";
        return $this->query($sql, array('date' => date('Y-m-d', time() - mailerConfig::RETURN_PATH_CHECK_PERIOD)))->fetchAll();
    }

    public function isActive($rp)
    {
        if (empty($rp['last_campaign_date'])) {
            return false;
        }
        return strtotime($rp['last_campaign_date']) >= time() - mailerConfig::RETURN_PATH_CHECK_PERIOD;
    }

    public function getErrors()
    {
        $sql = "SELECT email, last_error FROM {$this->table}";
        return $this->query($sql)->fetchAll('email', true);
    }

    public function logError($id, $error_text)
    {
        if ($error_text) {
            waLog::log('return_path_id='.$id.'; '.$error_text, 'mailer.return_path.log');
        }

        $this->updateById($id, array(
            'last_error' => ifempty($error_text, null),
        ));
    }
}

