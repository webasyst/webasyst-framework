<?php

class webasystSettingsTemplateEmailEditController extends webasystSettingsJsonController
{
    /**
     * @var waVerificationChannel
     */
    protected $channel;

    public function execute()
    {
        $channel_id = waRequest::get('id', null, waRequest::TYPE_INT);
        $this->channel = waVerificationChannel::factory($channel_id);

        if (!$this->channel->exists()) {
            return;
        }

        $data = waRequest::post('data', null, waRequest::TYPE_ARRAY_TRIM);
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            return $this->errors = $errors;
        }

        if (!$this->channel->isSystem()) {
            $this->channel->setName($data['name']);
        }
        $this->channel->setAddress($data['address']);
        $this->channel->commit();
    }

    protected function validateData($data = array())
    {
        $errors = null;
        $required_fields = array('address');
        if (!$this->channel->isSystem()) {
            $required_fields[] = 'name';
        }

        foreach ($required_fields as $f) {
            if (!isset($data[$f]) || strlen($data[$f]) <= 0) {
                $errors[] = array(
                    'field'   => $f,
                    'message' => _ws('This field is required'),
                );
            }
        }

        return $errors;
    }
}