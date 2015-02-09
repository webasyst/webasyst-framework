<?php

/**
 * Class mailerSubscriberTempModel
 * @desc stores subscribers, who did not confirm their subscription
 */
class mailerSubscriberTempModel extends waModel
{
    protected $table = 'mailer_subscriber_temp';

    public function getByHash($hash)
    {
        if (strlen($hash) != 32) {
            return false;
        }
        else {
            return $this->getByField('hash', $hash);
        }
    }

    public function save($hash, $data)
    {
        return $this->insert(array(
            'hash' => $hash,
            'data' => serialize($data),
            'create_datetime' => date('Y-m-d H:i:s')
            ), 2);
    }

    public function deleteByHash($hash)
    {
        if (strlen($hash) != 32) {
            return false;
        }
        else {
            $this->deleteByField('hash', $hash);
        }
    }
}