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
                'name'        => _ws('php mail() function'),
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

        ksort($senders);
        return $senders;
    }
}