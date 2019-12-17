<?php

class webasystSettingsTemplateCheckSendingController extends webasystSettingsJsonController
{
    /**
     * @var waVerificationChannel
     */
    protected $channel;

    public function execute()
    {
        $data = waRequest::post('data', null, waRequest::TYPE_ARRAY_TRIM);
        $this->channel = waVerificationChannel::factory(ifempty($data['channel_id'], 0));
        list($errors, $send_templates) = $this->validateData($data);
        if (!empty($errors)) {
            return $this->errors = $errors;
        }

        $send_errors = $this->sendMessages($send_templates, $data['recipient']);
        if (!empty($send_errors)) {
            return $this->errors = $send_errors;
        }
    }

    protected function validateData(array $data)
    {
        $errors = null;
        if (!$this->channel->exists()) {
            $errors[] = array('field' => '[channel_id]', 'message' => _ws('Channel not exists'));
        }

        if (empty($data['recipient'])) {
            $errors[] = array('field' => '[recipient]', 'message' => _ws('This field is required'));
        }

        // Validate template list
        $data['template'] = (!empty($data['template']) && is_array($data['template'])) ? $data['template'] : array();

        $valid_templates = array();
        foreach ($this->channel->getTemplatesList() as $template_id => $template_name) {
            if (array_key_exists($template_id,$data['template'])) {
                $valid_templates[] = $template_id;
            }
        }

        if (empty($valid_templates)) {
            $errors[] = array('field' => 'template', 'message' => _ws('Select at least one template to send'));
        }

        if (empty($errors)) {
            $result = wa('webasyst')->event('backend_before_email_send_test', ref([
                'channel' => $this->channel,
                'data' => $data,
            ]));
            foreach($result as $source => $res) {
                if (!empty($res['errors'])) {
                    $errors = array_merge(ifset($errors, []), $res['errors']);
                }
            }
        }

        return array($errors, $valid_templates);
    }

    /**
     * Templates for sending test messages
     * @param array $send_templates
     * @param string|int $recipient Phone number where to send test messages
     * @return array
     */
    protected function sendMessages(array $send_templates, $recipient)
    {
        $auth_config = waDomainAuthConfig::factory();

        $site_url = $auth_config->getSiteUrl();
        $site_name = $auth_config->getSiteName();

        $login_url = $auth_config->getLoginUrl(array(), true);

        $errors = array();
        foreach ($send_templates as $template) {
            $res = false;

            switch ($template) {

                case 'successful_signup':

                    $res = $this->channel->sendSignUpSuccessNotification($recipient, array(
                        'site_url' => $site_url,
                        'site_name' => $site_name,
                        'login_url' => $login_url,
                        'password' => 'TEST-PASS',
                        'is_test_send' => true
                    ));

                    break;

                case 'confirm_signup':

                    $confirmation_url = $auth_config->getSignUpUrl(array(
                        'get' => array('confirm' => 'confirmation_hash')
                    ), true);
                    $confirmation_url = str_replace('confirmation_hash', '{$confirmation_hash}', $confirmation_url);

                    $res = $this->channel->sendSignUpConfirmationMessage($recipient, array(
                        'site_url' => $site_url,
                        'site_name' => $site_name,
                        'confirmation_url' => $confirmation_url,
                        'is_test_send' => true
                    ));

                    break;

                case 'recovery_password':

                    $res = $this->channel->sendRecoveryPasswordMessage($recipient, array(
                        'site_url' => $site_url,
                        'site_name' => $site_name,
                        'login_url' => $login_url,
                        'recovery_url' => $auth_config->getRecoveryPasswordUrl(array(), true),
                        'is_test_send' => true
                    ));

                    break;

                case 'password':

                    $res = $this->channel->sendPassword($recipient,'TEST-PASS', array(
                        'site_url' => $site_url,
                        'site_name' => $site_name,
                        'login_url' => $login_url,
                        'is_test_send' => true
                    ));

                    break;
                case 'onetime_password':

                    $res = $this->channel->sendOnetimePasswordMessage($recipient, array(
                        'site_url' => $site_url,
                        'site_name' => $site_name,
                        'login_url' => $login_url,
                        'password' => 'TEST-PASS',
                        'is_test_send' => true
                    ));

                    break;

                case 'confirmation_code':

                    $res = $this->channel->sendConfirmationCodeMessage($recipient, array(
                        'site_url' => $site_url,
                        'site_name' => $site_name,
                        'login_url' => $login_url,
                        'code' => 'TEST-CODE',
                        'is_test_send' => true
                    ));

                    break;
            }

            if (!$res) {
                $errors[] = array('field' => '[template]['.$template.']', 'message' => _ws('Error sending message'));
            }
        }

        return $errors;
    }
}
