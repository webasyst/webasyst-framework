<?php

class webasystSettingsTemplateEmailAction extends webasystSettingsTemplateAction
{
    public function execute()
    {
        $channels = $this->getVerificationChannelModel()->getByType(waVerificationChannelModel::TYPE_EMAIL);
        $template_id = $this->getTemplateId();

        $request_id = $this->getRequestId();
        if (!$request_id && !empty($channels)) {
            $redirect_url = $this->getConfig()->getBackendUrl(true).$this->getAppId().'/settings/email/template/'.key($channels).'/'.$template_id.'/';
            $this->redirect($redirect_url);
        }

        if (!$this->channel instanceof waVerificationChannelEmail) {
            $this->channel = new waVerificationChannelNull();
        }

        $template = $this->channel->getTemplate($template_id);
        $default_template = $this->channel->getDefaultTemplates($template_id);

        $this->view->assign(array(
            'channel'          => $this->channel,
            'template_id'      => $template_id,
            'template'         => $template,
            'default_template' => $default_template,
            'channels'         => $channels,
            'emails'           => $this->getEmails(),
            'user'             => wa()->getUser(),
        ));
    }

    protected function getTemplateId()
    {
        $all_templates = $this->channel->getTemplatesList();
        $default_template = key($all_templates);
        $template_id = waRequest::param('template', $default_template, waRequest::TYPE_STRING_TRIM);
        if (!array_key_exists($template_id, $all_templates)) {
            $template_id = $default_template;
        }
        return $template_id;
    }

    protected function getEmails()
    {
        $wa_mail = new waMail();
        $mail_config = $wa_mail->readConfigFile();
        $config_items = array_keys($mail_config);
        foreach ($config_items as $i => $item) {
            if (!$this->isValidEmail($item)) {
                unset($config_items[$i]);
            }
        }

        $model = new waAppSettingsModel();
        $sender = $model->get('webasyst', 'sender', '');
        if (!empty($sender)) {
            $config_items[] = $sender;
        }

        $config_items = array_unique($config_items);

        return $config_items;
    }

    protected function isValidEmail($email)
    {
        if (!preg_match('~^[^\s@]+@[^\s@]+(\.[^\s@\.]{2,6})?$~u', $email)) {
            return false;
        }
        return true;
    }
}