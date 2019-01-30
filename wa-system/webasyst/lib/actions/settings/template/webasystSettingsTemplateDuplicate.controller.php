<?php

class webasystSettingsTemplateDuplicateController extends webasystSettingsJsonController
{
    public function execute()
    {
        $channel_id = waRequest::post('id', null, waRequest::TYPE_INT);
        $channel = waVerificationChannel::factory($channel_id);
        if (!$channel->exists()) {
            return $this->errors[] = _ws('Channel not found');
        }

        $duplicate_name = $this->generateDuplicateName($channel);

        $channel->setName($duplicate_name);
        $channel->setSystem(0);

        $new_channel_data = $channel->getInfo();
        unset($new_channel_data['id']);
        $new_channel = waVerificationChannel::factory($channel->getType());
        $new_channel->save($new_channel_data);

        $this->response = $new_channel->getInfo();

    }

    protected function generateDuplicateName(waVerificationChannel $channel)
    {
        $vm = new waVerificationChannelModel();
        $copy_num = 0;

        while (true) {
            $name = $channel->getName();
            $copy_num++;
            $copy_prefix = ' '.sprintf(_ws('(Copy of %s)'), $copy_num);
            $name .= $copy_prefix;
            if (!$vm->getByField(array('name' => $name))) {
                return $name;
            }
        }
    }
}