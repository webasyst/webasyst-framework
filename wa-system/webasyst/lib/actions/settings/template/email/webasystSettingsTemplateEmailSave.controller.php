<?php

class webasystSettingsTemplateEmailSaveController extends webasystSettingsTemplateSaveController
{
    protected $template_id;

    public function execute()
    {
        $data = $this->getData();
        $value = join(waVerificationChannelEmail::THEME_SEPARATOR, array($data['subject'], $data['text']));
        $this->channel->setTemplate($data['template_id'], $value);
        $this->channel->commit();
    }

    protected function getData()
    {
        $data = waRequest::post('data', null, waRequest::TYPE_ARRAY_TRIM);

        return array(
            'template_id' => ifempty($data['template']),
            'subject'     => ifempty($data['subject']),
            'text'        => ifempty($data['text']),
        );
    }
}