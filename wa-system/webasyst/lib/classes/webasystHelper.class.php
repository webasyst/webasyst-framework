<?php

class webasystHelper
{
    public static function getOneStringKey($dkim_pub_key)
    {
        $one_string_key = trim(preg_replace('/^\-{5}[^\-]+\-{5}(.+)\-{5}[^\-]+\-{5}$/s', '$1', trim($dkim_pub_key)));
        //$one_string_key = str_replace('-----BEGIN PUBLIC KEY-----', '', $dkim_pub_key);
        //$one_string_key = trim(str_replace('-----END PUBLIC KEY-----', '', $one_string_key));
        $one_string_key = preg_replace('/\s+/s', '', $one_string_key);
        return $one_string_key;
    }

    public static function getDkimSelector($email)
    {
        $e = explode('@', $email);
        return trim(preg_replace('/[^a-z0-9]/i', '', $e[0])).'wamail';
    }

    public static function backgroundClearCache($limit = 20)
    {
        $cache_model = new waCacheModel();
        $cache_model->deleteInvalid(array('limit' => (int) $limit));
    }

    public static function getSettingsSidebarItems()
    {
        $app_url = wa('webasyst')->getAppUrl().'webasyst/settings/';

        $items = array(
            'general' => array(
                'name' => _ws('General settings'),
                'url'  => $app_url,
            ),
            'field' => array(
                'name' => _ws('Contact fields'),
                'url'  => $app_url.'field/',
            ),
            'regions' => array(
                'name' => _ws('Countries & regions'),
                'url'  => $app_url.'regions/',
            ),
            'maps'    => array(
                'name' => _ws('Maps'),
                'url'  => $app_url.'maps/',
            ),
            'captcha' => array(
                'name' => _ws('Captcha'),
                'url'  => $app_url.'captcha/',
            ),
            'email'   => array(
                'name' => _ws('Email settings'),
                'url'  => $app_url.'email/',
            ),
            'email-template' => array(
                'name' => _ws('Email templates'),
                'url'  => $app_url.'email/template/',
            ),
            'sms' => array(
                'name' => _ws('SMS providers'),
                'url'  => $app_url.'sms/',
            ),
            'auth' => array(
                'name' => _ws('Backend authorization'),
                'url'  => $app_url.'auth/',
            ),
        );

        /**
         * @event settings_sidebar
         * @param array
         */
        wa('webasyst')->event('settings_sidebar', $items);

        return $items;
    }

    public static function smsTemplateAvailable()
    {
        $sidebar_items = self::getSettingsSidebarItems();
        return isset($sidebar_items['sms-template']);
    }
}