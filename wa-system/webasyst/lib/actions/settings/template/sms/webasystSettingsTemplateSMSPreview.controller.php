<?php

class webasystSettingsTemplateSMSPreviewController extends webasystSettingsJsonController
{
    /**
     * @var array
     */
    protected $template_list;

    public function execute()
    {
        /**
         * @var waVerificationChannelSMS $channel
         */
        $channel = waVerificationChannel::factory(waVerificationChannelModel::TYPE_SMS);

        $this->template_list = $channel->getTemplatesList();

        $data = waRequest::post('data', null,waRequest::TYPE_ARRAY_TRIM);
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            return $this->errors = $errors;
        }

        $data['preview'] = $channel->previewMessage($data['preview']);
        $data['template'] = $this->template_list[$data['template_id']];
        $data['time'] = _ws('Today').' '.date('H:i');

        $this->response = $data;
    }

    protected function validateData(array $data)
    {
        $errors = null;
        $required_fields = array('template_id', 'preview');

        foreach ($required_fields as $f) {
            if (!isset($data[$f]) || strlen($data[$f]) <= 0) {
                $errors[] = array(
                    'field'   => $f,
                    'message' => _ws('This field is required'),
                );
            }
        }

        if (isset($data['template_id']) && !array_key_exists($data['template_id'], $this->template_list)) {
            $errors[] = array(
                'field'   => 'template_id',
                'message' => _ws('Invalid value'),
            );
        }

        return $errors;
    }
}
