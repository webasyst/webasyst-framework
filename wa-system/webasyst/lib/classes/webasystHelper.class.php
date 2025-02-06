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
                'name' => _ws('General'),
                'url'  => $app_url,
            ),
            'email'          => array(
                'name' => _ws('Email'),
                'url'  => $app_url.'email/',
            ),
            'sms'            => array(
                'name' => _ws('SMS'),
                'url'  => $app_url.'sms/',
            ),
            'push'           => array(
                'name' => _ws('Push'),
                'url'  => $app_url.'push/',
            ),
            'maps'           => array(
                'name' => _ws('Maps'),
                'url'  => $app_url.'maps/',
            ),
            'captcha'        => array(
                'name' => _ws('Captcha'),
                'url'  => $app_url.'captcha/',
            ),
            'field'          => array(
                'name' => _ws('Contact fields'),
                'url'  => $app_url.'field/',
            ),
            'regions'        => array(
                'name' => _ws('Countries & regions'),
                'url'  => $app_url.'regions/',
            ),
            'auth'           => array(
                'name' => _ws('Backend authorization'),
                'url'  => $app_url.'auth/',
            ),
            'db'             => array(
                'name' => _w('Database'),
                'url'  => $app_url.'db/',
            ),
            'waid'          => array(
                'name' => _w('Webasyst ID'),
                'url'  => $app_url.'waid/',
            ),
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
