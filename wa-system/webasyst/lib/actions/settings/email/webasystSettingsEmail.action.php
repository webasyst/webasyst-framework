<?php

class webasystSettingsEmailAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $email = $model->get('webasyst', 'email', '');
        $sender = $model->get('webasyst', 'sender', '');

        $wamail = new waMail();
        $main_configs = array('default' => array());
        $main_configs = array_merge($main_configs, $wamail->readConfigFile());

        foreach ($main_configs as $key => &$config) {
            $key = explode('@', $key);
            if (ifset($config['dkim']) == 1) {
                $config['domain'] = end($key);
                $config['one_string_key'] = webasystHelper::getOneStringKey(ifset($config['dkim_pub_key']));
            }
        }

        $this->view->assign(array(
            'email'                => $email,
            'sender'               => $sender,
            'main_configs'         => $main_configs,
            'available_transports' => $this->getAvailableTransports(),
            'ssl_is_set'           => extension_loaded('openssl'),
            'php_version_ok'       => version_compare(PHP_VERSION, '5.3') >= 0,
            'php_version'          => PHP_VERSION,
        ));
    }

    protected function getAvailableTransports()
    {
        $senders = array(
            'smtp' => array(
                'name'        => _ws('SMTP'),
                'description' => _ws('A special server for sending email messages. You can send email via any SMTP server for which you have connection credentials: host, port, user name, and password. It may belong either to your web-hosting company or to a public mail service such as Gmail, Yahoo! Mail, Outlook.com, etc.')
            ),
        );

        if (function_exists('mail')) {
            $senders['mail'] = array(
                'name'        => _ws('PHP mail() function'),
                'description' => _ws('Some web-hosting companies allow sending email messages by means of this transport only. If it is required to specify additional parameters for the mail() function enter them below. The default parameters are -f%s.')
            );
        }

        if (function_exists('proc_open')) {
            $senders['sendmail'] = array(
                'name'        => _ws('Sendmail'),
                'description' => _ws('This is a default command for sending email in UNIX-like operating systems. You can edit it to use a different command for sending messages if you are an experienced server administrator.')
            );
        }

        if (class_exists('Swift_WadebugTransport')) {
            $senders['wadebug'] = array(
                'name' => 'wadebug'
            );
        }

        $wa_sender_html = $this->getWaSenderHtml();
        if (!empty($wa_sender_html)) {
            $senders['wasender'] = array(
                'name' => _ws('Webasyst Email'),
                'description' => $wa_sender_html
            );
        }

        ksort($senders);
        return $senders;
    }

    public function getWaSenderHtml()
    {
        $wa_service_api = new waServicesApi();
        if (!$wa_service_api->isConnected()) {
            return '<p>'.
            sprintf_wp(
                '<a href="%s">Connect to Webasyst ID</a> to use the Webasyst Email.',
                wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/waid/'
            ) . '</p>';
        }
        $res = $wa_service_api->getBalance(waServicesApi::EMAIL_MESSAGE_SERVICE);
        if ($res['status'] != 200) {
            return null;
        }
       
        $balance_amount = ifset($res, 'response', 'amount', 0);
        $price_value = ifset($res, 'response', 'price', 0);
        $currency_id = ifset($res, 'response', 'currency_id', wa()->getLocale() === 'ru_RU' ? 'RUB' : 'USD');
        $balance = wa_currency_html($balance_amount, $currency_id);
        $price = wa_currency_html($price_value, $currency_id);
        $free_limits = ifset($res, 'response', 'free_limits', '');
        $remaining_free_calls = ifset($res, 'response', 'remaining_free_calls', []);
        if ($balance_amount > 0 && $price_value > 0) {
            $messages_count = intval(floor($balance_amount / $price_value));
        }

        $res = $wa_service_api->getIpWhiteList();
        $white_list = ifset($res, 'response', 'list', []);
        $is_allowed_ip = ifset($res, 'response', 'is_allowed_ip', true);
        $current_ip = ifset($res, 'response', 'your_ip', '');

        $view = wa()->getView();
        $view->assign([
            'wa_balance'        => ifset($balance, '—'),
            'wa_price'          => ifset($price, '—'),
            'wa_free_limits'    => ifset($free_limits, []),
            'wa_white_list'     => ifset($white_list, []),
            'wa_is_allowed_ip'  => ifset($is_allowed_ip, true),
            'wa_current_ip'     => ifset($current_ip, ''),
            'wa_remaining_free_calls' => ifset($remaining_free_calls, []),
            'wa_messages_count'  => ifset($messages_count, 0),
        ]);
        $template_path = wa()->getConfig()->getAppsPath('webasyst').'/templates/actions/services/balance.html';
        return $view->fetch($template_path);
    }
}
