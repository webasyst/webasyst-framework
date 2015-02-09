<?php

class mailerSendersDialogAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $id = waRequest::get('id');
        $sender_model = new mailerSenderModel();
        $sender = $sender_model->getById($id);

        $params_model = new mailerSenderParamsModel();
        $params = $params_model->getBySender($id);

        if (!isset($params['type'])) {
            $params['type'] = 'mail';
        }

        $params['ssl_is_set'] = extension_loaded('openssl');
        $params['php_version_ok'] = version_compare(PHP_VERSION, '5.3') >= 0;
        $params['php_version'] = PHP_VERSION;

        if ($sender) {
            $email = explode('@', $sender['email']);
            $sender['domain'] = array_pop($email);
            $sender['one_string_key'] = mailerHelper::getOneStringKey(ifset($params['dkim_pub_key']));
            $params['dkim_selector'] = mailerHelper::getDkimSelector($sender['email']);
        }

        $this->assignSenderTypes(waSystemConfig::isDebug() || $params['type'] == 'test');
        $this->view->assign('show_delete_button', $id && $sender && $sender_model->countAll() > 1);
        $this->view->assign('sender', $sender);
        $this->view->assign('params', $params);
    }

    protected function assignSenderTypes($debug)
    {
        static $senders = null;
        if ($senders === null) {
            $senders = array(
                'default' => array(
                    'name' => _w('System Default'),
                    'description' => _w('When this option is selected messages are sent by the default transport specified in the Webasyst framework configuration.')
                ),
                'mail' => array(
                    'name' => _w('php mail() function'),
                    'description' => _w('Some web-hosting companies allow sending email message by means of this transport only. If it is required to specify additional parameters for the mail() function you can enter them in the provided text field. The default parameters are -f%s.')
                ),
                'smtp' => array(
                    'name' => _w('SMTP'),
                    'description' => _w('Special server which is specifically used for sending email messages. You can send newsletters via any SMTP server for which you have connection credentials: host, port, user name, and password. It can be the SMTP server of your web-hosting company or that of a public mail service such as Gmail, Yahoo! Mail, Outlook.com, etc.')
                )
            );
            if (function_exists('proc_open')) {
                $senders['sendmail'] = array(
                    'name' => _w('Sendmail'),
                    'description' => _w('This is the web server\'s system command for sending email in UNIX-like operating systems. The "Sendmail" option will allow you to specify a non-standard system command for sending messages if you are an experienced server administrator.')
                );
            }
            if ($debug) {
                $senders['test'] = array(
                    'name' => _w('Debug mailer'),
                    'description' => ""
                );
            }

            /**
             * !!! TODO: docs for sender.types event
             */
            wa()->event('sender.types', $senders);
        }

        $this->view->assign('sender_types', $senders);
    }
}

