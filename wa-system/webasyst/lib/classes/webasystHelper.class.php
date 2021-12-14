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
        $cache_model->deleteInvalid(array('limit' => (int)$limit));
    }

    public static function getSettingsSidebarItems()
    {
        $app_url = wa('webasyst')->getAppUrl().'webasyst/settings/';

        $items = array(
            'general'        => array(
                'name' => _ws('General settings'),
                'url'  => $app_url,
            ),
            'field'          => array(
                'name' => _ws('Contact fields'),
                'url'  => $app_url.'field/',
            ),
            'regions'        => array(
                'name' => _ws('Countries & regions'),
                'url'  => $app_url.'regions/',
            ),
            'maps'           => array(
                'name' => _ws('Maps'),
                'url'  => $app_url.'maps/',
            ),
            'captcha'        => array(
                'name' => _ws('Captcha'),
                'url'  => $app_url.'captcha/',
            ),
            'push'           => array(
                'name' => _ws('Web push notifications'),
                'url'  => $app_url.'push/',
            ),
            'email'          => array(
                'name' => _ws('Email settings'),
                'url'  => $app_url.'email/',
            ),
            'email-template' => array(
                'name' => _ws('Email templates'),
                'url'  => $app_url.'email/template/',
            ),
            'sms'            => array(
                'name' => _ws('SMS providers'),
                'url'  => $app_url.'sms/',
            ),
            'sms-template'   => array(
                'name' => _ws('SMS templates'),
                'url'  => $app_url.'sms/template/',
            ),
            'auth'           => array(
                'name' => _ws('Backend authorization'),
                'url'  => $app_url.'auth/',
            ),
            'waid'          => array(
                'name' => _w('Sign-in with Webasyst ID'),
                'url'  => $app_url.'waid/',
            ),
            'db'             => array(
                'name' => _w('Database'),
                'url'  => $app_url.'db/',
            )
        );

        /**
         * @event settings_sidebar
         * @param array
         */
        wa('webasyst')->event('settings_sidebar', $items);

        return $items;
    }

    /**
     * A method that returns the flag for the availability of editing system SMS templates.
     *
     * Previously used in the Site app (sitePersonalSettingsAction).
     *
     * Needed for backward compatibility. It will be possible to delete the in 2021 year.
     * @return true
     */
    public static function smsTemplateAvailable()
    {
        return true;
    }
}
