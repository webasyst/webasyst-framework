<?php

class webasystSettingsTemplateSMSNewController extends webasystSettingsJsonController
{
    public function execute()
    {
        $data = waRequest::post('data', null, waRequest::TYPE_ARRAY_TRIM);
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            return $this->errors = $errors;
        }

        $channel = new waVerificationChannelSMS(0);
        $channel->save($data);

        $this->response = $channel->getInfo();
    }

    protected function validateData($data = array())
    {
        $errors = null;
        $required_fields = array('name', 'address');
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