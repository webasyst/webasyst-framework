<?php

class waPushSubscribersModel extends waModel
{
    protected $table = 'wa_push_subscribers';

    public function insert($data, $type = 0)
    {
        $adapters = wa()->getPushAdapters();
        if (empty($adapters[$data['provider_id']])) {
            throw new waException('The specified adapter was not found');
        }

        if (empty($data['create_datetime'])) {
            $data['create_datetime'] = date("Y-m-d H:i:s");
        }

        if (empty($data['domain'])) {
            $data['domain'] = wa()->getRouting()->getDomain();
        }

        return parent::insert($data, $type);
    }
}