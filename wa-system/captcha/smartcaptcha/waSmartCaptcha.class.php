<?php

/**
 * Yandex SmartCaptcha
 *
 * Документация
 * https://cloud.yandex.ru/docs/smartcaptcha/quickstart
 */

class waSmartCaptcha extends waAbstractCaptcha
{
    const VALIDATE_URL = 'https://captcha-api.yandex.ru/validate';
    const SESSION_KEY = 'smart_captcha_passed';

    protected  $required = array(
        'client_key',
        'server_key'
    );

    public function isValid($token = null, &$error = '')
    {
        if (wa()->getStorage()->get(self::SESSION_KEY)) {
            return true;
        }

        if (!$token) {
            $token = waRequest::post('smart-token', '');
        }

        $is_valid = $this->verify($token, $error);
        if ($is_valid) {
            wa()->getStorage()->set(self::SESSION_KEY, true);
        }

        return $is_valid;
    }

    public function getHtml()
    {
        $client_key = ifset($this->options, 'client_key', '');
        $server_key = ifset($this->options, 'server_key', '');
        if (empty($client_key)) {
            return sprintf(
                '<span style="color: red">%s. %s</span>',
                _ws('Error'),
                _ws('Client key not specified.')
            );
        } elseif (empty($server_key)) {
            return sprintf(
                '<span style="color: red">%s. %s</span>',
                _ws('Error'),
                _ws('Server key not specified.')
            );
        }

        $smart_invisible = ifset($this->options['smart_invisible']);
        $view = wa('webasyst')->getView();
        $view->assign([
            'client_key' => $client_key,
        ]);
        $template = wa()->getConfig()->getRootPath().'/wa-system/captcha/smartcaptcha/templates/smartcaptcha.html';
        if ($smart_invisible) {
            $template = wa()->getConfig()->getRootPath().'/wa-system/captcha/smartcaptcha/templates/smartcaptcha_invisible.html';
        }

        return $view->fetch($template);
    }

    public function display()
    {
    }

    /**
     * @param $token
     * @param $error
     * @return bool
     */
    protected function verify($token = null, &$error = '')
    {
        $net_options = [
            'timeout' => 20,
            'format'  => waNet::FORMAT_JSON,
        ];
        $params = [
            'ip'     => waRequest::getIp(),
            'token'  => $token,
            'secret' => ifset($this->options['server_key']),
        ];
        try {
            $net = new waNet($net_options);
            $response_json = $net->query(self::VALIDATE_URL, $params);
        } catch (waNetException | waException $wex) {
            $error = $wex->getMessage();
            $resp_json = json_decode($error);
            if (json_last_error() === JSON_ERROR_NONE && !empty($resp_json->message)) {
                /**
                 * Запрос без ключа сервера:
                 * {"status": "failed", "message": "Authentication failed. Secret has not provided."}
                 *
                 * Запрос без IP-адреса:
                 * {"status": "failed", "message": "Invalid IP."}
                 *
                 * Запрос без токена или с поврежденным токеном:
                 * {"status": "failed", "message": "Token invalid or expired."}
                 */
                $error = $resp_json->message;
            }
        }

        /**
         * Это человек:
         * {"status": "ok", "message": ""}
         *
         * Это робот:
         * {"status": "failed", "message": ""}
         *
         * Запрос с поддельным или поврежденным токеном. Это робот:
         * {"status": "failed", "message": "Token invalid or expired."}
         */
        return ifempty($response_json, 'status', '') === 'ok';
    }
}
