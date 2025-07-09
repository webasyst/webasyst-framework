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
            'wa_sender_html'       => $this->getWaSenderHtml(false, $main_configs),
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

        if ($this->getWaSenderHtml(true) === true) {
            $senders['wasender'] = array(
                'name' => _ws('Webasyst Email'),
                'description' => ''
            );
        }

        ksort($senders);
        return $senders;
    }

    public function getWaSenderHtml($only_check_auth, $main_configs = [])
    {
        try {
            $wa_service_api = new waServicesApi();
        } catch (Throwable $e) {
            return null;
        }

        static $res = [];

        if (empty($res)) {
            if ($wa_service_api->isBrokenConnection()) {
                return '<p class="state-caution-hint"><i class="fas fa-exclamation-circle"></i> '.
                _ws('Connection to Webasyst ID server is broken. Please re-connect your account to continue using Webasyst Email service.') . ' ' .
                sprintf_wp(
                    'To do so, open the <a href="%s">Webasyst ID settings</a>, disable sign-in with Webasyst ID and enable it again.',
                    wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/waid/'
                ) . '</p>';
            }
            if (!$wa_service_api->isConnected()) {
                return '<p class="small">'.
                sprintf_wp(
                    '<a href="%s">Connect to Webasyst ID</a> to use the Webasyst Email.',
                    wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/waid/'
                ) . '</p>';
            }
            $res = $wa_service_api->getBalance(waServicesApi::EMAIL_MESSAGE_SERVICE);
        }
        if ($res['status'] != 200) {
            return null;
        }
        if ($only_check_auth) {
            return true;
        }

        $balance_amount = ifset($res, 'response', 'amount', 0);
        $price_value = ifset($res, 'response', 'price', 0);
        $free_limits = ifset($res, 'response', 'free_limits', []);
        $remaining_free_calls = ifempty($res, 'response', 'remaining_free_calls', []);
        $remaining_pack = ifset($remaining_free_calls, 'pack', 0);
        unset($remaining_free_calls['pack']);
        if ($balance_amount > 0 && $price_value > 0) {
            $messages_count = intval(floor($balance_amount / $price_value));
        }

        $res = $wa_service_api->getIpWhiteList();
        $white_list = ifset($res, 'response', 'list', []);
        $is_allowed_ip = ifset($res, 'response', 'is_allowed_ip', true);
        $current_ip = ifset($res, 'response', 'your_ip', '');

        $is_show_connect = !array_filter($main_configs, function($config) {
            return ifset($config['type']) == 'wasender';
        });
        $waid_balance_show_email_notice = !$is_show_connect && ifset($main_configs, 'default', 'type', '') !== 'wasender';

        $view = wa()->getView();
        $view->assign([
            'wa_total'          => ifset($messages_count, 0)
                                    + ifset($remaining_free_calls, 'total', 0) // min(array_values($remaining_free_calls) ?: [0])
                                    + ifset($remaining_pack, 0),
            'wa_free_limits'    => ifset($free_limits, []),
            'wa_white_list'     => ifset($white_list, []),
            'wa_is_allowed_ip'  => ifset($is_allowed_ip, true),
            'wa_current_ip'     => ifset($current_ip, ''),
            'wa_remaining_free_calls' => ifset($remaining_free_calls, []),
            'service'           => waServicesApi::EMAIL_MESSAGE_SERVICE,
            'is_show_connect'   => $is_show_connect,
            'waid_balance_show_email_notice' => $waid_balance_show_email_notice,
        ]);
        $template_path = wa()->getConfig()->getAppsPath('webasyst').'/templates/actions/services/balance.html';
        return $view->fetch($template_path);
    }
}
