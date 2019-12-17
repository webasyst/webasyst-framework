<?php

class sitePersonalSettingsAction extends waViewAction
{
    public function execute()
    {
        $domain = siteHelper::getDomain();
        $auth_config = waDomainAuthConfig::factory($domain);

        $personal_sidebar = wa('site')->event('backend_personal');
        foreach ($personal_sidebar as &$items) {
            foreach ($items as &$item) {
                $item['url'] .= '&domain='.urlencode($domain);
            }
        }

        $verification_channels = $auth_config->getAvailableVerificationChannels();
        $email_channels = $sms_channels = array();
        foreach ($verification_channels as $id => $channel) {
            if ($channel['type'] == waVerificationChannelModel::TYPE_EMAIL) {
                $email_channels[$id] = $channel;
            } else {
                $sms_channels[$id] = $channel;
            }
        }

        $used_auth_methods = $auth_config->getUsedAuthMethods();

        // If there are no email channels and no SMS is used as
        // a verification channel, will limit some functionality. #51.5712
        $no_channels = (empty($email_channels) && !in_array(waVerificationChannelModel::TYPE_SMS, $used_auth_methods));

        // Prepare auth endpoints
        $auth_endpoints = $auth_config->getAuthEndpoints();
        foreach ($auth_endpoints as $route_url => &$auth_endpoint) {
            $auth_endpoint['login_url'] = waIdna::dec($auth_endpoint['login_url']);
            $auth_endpoint['signup_url'] = waIdna::dec($auth_endpoint['signup_url']);
        }
        unset($auth_endpoint);

        wa('webasyst');
        $this->view->assign(array(
            'auth_config'                => $auth_config->getData(),
            'params'                     => $auth_config->getParams(),
            'auth_adapters'              => $auth_config->getAvailableAuthAdapters(),
            'auth_endpoints'             => $auth_endpoints,
            'auth_types'                 => $auth_config->getAuthTypes(),
            'signup_captcha'             => $auth_config->getSignUpCaptcha(),
            'rememberme'                 => $auth_config->getRememberMe(),
            'login_captcha_variants'     => $auth_config->getLoginCaptchaVariants(),
            'demo_captcha'               => wa()->getCaptcha(),
            'available_fields'           => $auth_config->getAvailableFields(),
            'enable_fields'              => $auth_config->getEnableFields(),
            'used_auth_methods'          => $used_auth_methods,
            'email_channels'             => $email_channels,
            'sms_channels'               => $sms_channels,
            'no_channels'                => $no_channels,
            'verification_channel_types' => $auth_config->getVerificationChannelTypes(),
            'domain'                     => waIdna::dec($domain),
            'domain_id'                  => siteHelper::getDomainId(),
            'personal_sidebar'           => $personal_sidebar,
        ));
    }
}
