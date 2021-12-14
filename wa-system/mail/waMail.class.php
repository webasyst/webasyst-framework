<?php

require_once realpath(dirname(__FILE__).'/../').'/vendors/swift/swift_required.php';

class waMail extends Swift_Mailer
{
    protected static $wa_config = array();
    private $wa_set_transport = false;

    public function __construct(Swift_Transport $transport = null)
    {
        if (!$transport) {
            $transport = Swift_MailTransport::newInstance();
            // set transport from config (see method send)
            $this->wa_set_transport = true;
        }
        parent::__construct($transport);
    }

    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        if (!$message->getFrom()) {
            if ($from = self::getDefaultFrom()) {
                $message->setFrom($from);
            }
        }

        if ($this->wa_set_transport) {
            $this->_transport = self::getTransportByEmail(key($message->getFrom()));
        }

        // Add DKIM Signature
        if (!$message->getHeaders()->get('DKIM-Signature')) {
            $mail_config = $this->readConfigFile();
            $sender_email = key($message->getFrom());
            $e = explode('@', ifset($sender_email));
            $domain_name = end($e);
            $mail_data = false;

            // Find email:
            if (isset($mail_config[$sender_email])) {
                $mail_data = $mail_config[$sender_email];
                // Find domain:
            } elseif (isset($mail_config[$domain_name])) {
                $mail_data = $mail_config[$domain_name];
            }

            if ($mail_data && ifset($mail_data['dkim']) == 1) {
                $dkim_signer = new Swift_Signers_DKIMSigner(
                    ifset($mail_data['dkim_pvt_key']), $domain_name, ifset($mail_data['dkim_selector'])
                );
                $dkim_signer->ignoreHeader('Return-Path');
                $dkim_signer->ignoreHeader('Bcc');
                $message->attachSigner($dkim_signer);
            }
        }

        /**
         * Run before send notification
         * @param Swift_Mime_Message $message
         *
         * @event mail_send.before
         */

        $params = [
            'message' => $message,
        ];

        wa('webasyst')->event('mail_send.before', $params);

        $result = false;
        try {
            $result = parent::send($message, $failedRecipients);
            $params['result'] = $result;
        } catch (Exception $e) {
            $log = [];
            $log[] = sprintf('Error sending email from "%s" to "%s" with subject "%s"',
                     implode('", "', array_keys($message->getFrom())),
                     implode('", "', array_keys($message->getTo())),
                     $message->getSubject()
            );
            $log[] = $e->getMessage();
            $log[] = $e->getTraceAsString();
            waLog::log(implode("\n", $log), 'mail.log');
            $params['exception'] = $e->getMessage();
        }

        /**
         * Run after send notification
         *
         * @param Swift_Mime_Message message
         * @param int|bool result
         * @param exception exception
         * @event mail_send.after
         */

        wa('webasyst')->event('mail_send.after', $params);

        return $result;
    }

    /**
     * @static
     * @param string $email
     * @return Swift_Transport
     */
    public static function getTransportByEmail($email)
    {
        $email = mb_strtolower($email);
        if (!isset(self::$wa_config['transport'])) {
            self::$wa_config['transport'] = wa()->getConfig()->getConfigFile('mail');
        }

        $config = array();
        if (isset(self::$wa_config['transport'][$email])) {
            $config = self::$wa_config['transport'][$email];
        } else {
            $email_parts = explode('@', $email);
            if (isset($email_parts[1]) && isset(self::$wa_config['transport'][$email_parts[1]])) {
                $config = self::$wa_config['transport'][$email_parts[1]];
            } elseif (isset(self::$wa_config['transport']['default'])) {
                $config = self::$wa_config['transport']['default'];
            }
        }
        if (!$config || !isset($config['type'])) {
            return Swift_MailTransport::newInstance();
        }
        if ($config['type'] == 'smtp') {
            if (empty($config['port'])) {
                $config['port'] = 25;
            }
            $transport = Swift_SmtpTransport::newInstance($config['host'], $config['port']);
            if (isset($config['login'])) {
                $transport->setUsername($config['login']);
                $transport->setPassword($config['password']);
            }
            if (isset($config['encryption'])) {
                $transport->setEncryption($config['encryption']);
            }
            return $transport;
        } else {
            $class_name = "Swift_".ucfirst($config['type'])."Transport";
            if (class_exists($class_name)) {
                if (isset($config['options'])) {
                    return new $class_name($config['options']);
                } else {
                    return new $class_name();
                }
            } else {
                return Swift_MailTransport::newInstance();
            }
        }
    }

    public static function getDefaultFrom($sender = true)
    {
        if (!isset(self::$wa_config['from'])) {
            $app_settings_model = new waAppSettingsModel();
            $email = $app_settings_model->get('webasyst', 'sender');
            if ($email) {
                self::$wa_config['from'] = array(
                    $email => $app_settings_model->get('webasyst', 'name')
                );
            } else {
                self::$wa_config['from'] = array();
            }
        }
        return self::$wa_config['from'];
    }

    public function readConfigFile()
    {
        return wa()->getConfig()->getConfigFile('mail', array());
    }

    public function saveConfigFile($data)
    {
        waUtils::varExportToFile($data, wa()->getConfig()->getPath('config', 'mail'));
    }
}
