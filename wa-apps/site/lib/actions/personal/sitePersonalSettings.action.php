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

        wa('webasyst');
        $this->view->assign(array(
            'auth_config'                => $auth_config->getData(),
            'params'                     => $auth_config->getParams(),
            'auth_adapters'              => $auth_config->getAvailableAuthAdapters(),
            'auth_apps'                  => $auth_config->getAuthApps('all'),
            'auth_types'                 => $auth_config->getAuthTypes(),
            'signup_captcha'             => $auth_config->getSignUpCaptcha(),
            'rememberme'                 => $auth_config->getRememberMe(),
            'login_caption'              => $auth_config->getLoginCaption(),
            'login_placeholder'          => $auth_config->getLoginPlaceholder(),
            'login_captcha'              => $auth_config->getLoginCaptcha(),
            'login_captcha_variants'     => $auth_config->getLoginCaptchaVariants(),
            'demo_captcha'               => wa()->getCaptcha(),
            'available_fields'           => $auth_config->getAvailableFields(),
            'enable_fields'              => $auth_config->getEnableFields(),
            'used_auth_methods'          => $auth_config->getUsedAuthMethods(),
            'sms_channels'               => $sms_channels,
            'email_channels'             => $email_channels,
            'verification_channel_types' => $auth_config->getVerificationChannelTypes(),
            'domain'                     => waIdna::dec($domain),
            'domain_id'                  => siteHelper::getDomainId(),
            'personal_sidebar'           => $personal_sidebar,
            'phone_available'            => webasystHelper::smsTemplateAvailable(),
        ));
    }
}
