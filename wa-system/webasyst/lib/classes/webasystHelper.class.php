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
            'ai'             => array(
                'name' => _w('AI'),
                'url'  => $app_url.'ai/',
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
            'privacy'        => array(
                'name' => _ws('Privacy'),
                'url'  => $app_url.'privacy/',
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

    public static function getAiParams(): array {
        $remaining_count = 0;
        $waid_is_connected = (new waServicesApi())->isConnected();

        if ($waid_is_connected) {
            try {
                $wa_service_api = new waServicesApi();
            } catch (Throwable $e) {
                return [
                    'waid_is_connected' => false,
                    'remaining_count' => 0,
                ];
            }


            if (method_exists($wa_service_api, 'isBrokenConnection') && $wa_service_api->isBrokenConnection()) {
                return [
                    'waid_is_connected' => false,
                    'remaining_count' => 0,
                ];
            }

            if (!$wa_service_api->isConnected()) {
                return [
                    'waid_is_connected' => false,
                    'remaining_count' => 0,
                ];
            }

            $res = $wa_service_api->getBalance('AI');
            if ($res['status'] != 200) {
                return [
                    'waid_is_connected' => false,
                    'remaining_count' => 0,
                ];
            }

            $balance_amount = ifset($res, 'response', 'amount', 0);
            $price_value = ifset($res, 'response', 'price', 0);
            $remaining_free_calls = ifempty($res, 'response', 'remaining_free_calls', []);
            $remaining_pack = ifset($remaining_free_calls, 'pack', 0);
            unset($remaining_free_calls['pack']);
            if ($balance_amount > 0 && $price_value > 0) {
                $messages_count = intval(floor($balance_amount / $price_value));
            }

            $remaining_count = ifset($messages_count, 0)
                        + ifset($remaining_free_calls, 'total', 0)
                        + ifset($remaining_pack, 0);
        }

        return [
            'waid_is_connected' => $waid_is_connected,
            'remaining_count' => $remaining_count,
        ];
    }

    public static function logAgreementAcceptance($document_name, $document_text, $accept_method, $contact_id = null, $context = null, $app_id = null, $form_url = null)
    {
        try {
            $domain = waRequest::server('HTTP_HOST');
            if (empty($app_id)) {
                $app_id = wa()->getApp();
            }
            if (empty($form_url)) {
                $form_url = waRequest::server('HTTP_REFERER') ?: waRequest::server('REQUEST_URI');
            }

            $document_id = (new waAgreementDocumentModel())->getDocumentId($document_name, $document_text, wa()->getLocale(), $app_id, $context, $domain);

            (new waAgreementLogModel)->insert([
                'create_datetime' => date('Y-m-d H:i:s'),
                'app_id' => $app_id,
                'contact_id' => $contact_id ?: (wa()->getUser()->isAuth() ? wa()->getUser()->getId() : null),
                'ip' => waRequest::getIp(),
                'user_agent' => waRequest::getUserAgent(),
                'context' => $context,
                'document_name' => $document_name,
                'document_id'   => $document_id,
                'accept_method' => $accept_method,
                'domain' => $domain,
                'form_url' => $form_url
            ]);
        } catch (Exception $e) {
            $message = join(PHP_EOL, [
                'Error on saving agreement acceptance log', 
                get_class($e), 
                $e->getMessage(), 
                $e->getTraceAsString()
            ]);
            waLog::log($message);
        }
    }
}
