<?php

/**
 * Storage for sender settings.
 */
class mailerSenderModel extends waModel
{
    protected $table = 'mailer_sender';

    public function deleteById($id)
    {
        $spm = new mailerSenderParamsModel();
        $spm->deleteByField('sender_id', $id);
        return parent::deleteById($id);
    }
}