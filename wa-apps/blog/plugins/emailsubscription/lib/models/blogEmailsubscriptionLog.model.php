<?php

class blogEmailsubscriptionLogModel extends waModel
{
    protected $table = "blog_emailsubscription_log";

    public function setStatus($id, $status, $error = null)
    {
        $data = array(
            'status' => $status,
            'datetime' => date('Y-m-d H:i:s')
        );
        if ($error) {
            $data['error'] = $error;
        }
        $this->updateById($id, $data);
    }
}