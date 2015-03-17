<?php

class mailerSendersSaveController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        // Save sender data
        $id = waRequest::post('id');
        $data = waRequest::post('data');
        $this->validateArray($data);

        // Validate email
        if (empty($data['email'])) {
            $this->errors = array(
                'data[email]' => _w('Email is required'),
            );
            return;
        } else {
            $ev = new waEmailValidator();
            if (!$ev->isValid($data['email'])) {
                $this->errors = array(
                    'data[email]' => _w('Invalid Email address'),
                );
                return;
            }
        }

        // Sender params
        $params = waRequest::post('params', array());
        $this->validateArray($params);

        $spm = new mailerSenderParamsModel();

        // Try to connect using given settings
        if ($params['type'] == 'smtp') {
            if (!empty($params['login']) && empty($params['password']) && $id) {
                $p = $spm->getById($id);
                $params['password'] = $p['password'];
            }

            try {
                $transport = Swift_SmtpTransport::newInstance(ifempty($params['server']), ifempty($params['port']));
                if (!empty($params['login'])) {
                    $transport->setUsername($params['login']);
                    if (!empty($params['password'])) {
                        $transport->setPassword($params['password']);
                    }
                }
                if (!empty($params['encryption'])) {
                    // Check if SSL is supported
                    // (have to do it here because Swift tends to echo warnings that break our JSON)
                    if (!defined('OPENSSL_VERSION_NUMBER')) {
                        throw new waException(_w('Encryption requires OpenSSL PHP module to be installed.'));
                    }
                    $transport->setEncryption($params['encryption']);
                }
                $transport->start();
                $transport->stop();
            } catch (Exception $e) {
                $this->errors = self::fixWindowsErrormsg($e->getMessage());
                if (!$this->errors || json_encode($this->errors) === 'null') {
                    waLog::log('mailer: Error checking SMTP during transport validation: '.$this->errors);
                    $this->errors = _w('Unknown error');
                }
                $this->errors = array('' => _w('Error checking mail transport:').' '.$this->errors);
                return;
            }
        } else if ($params['type'] == 'sendmail') {
            try {
                if (!empty($params['command'])) {
                    $command = str_replace(' -t', ' -bs', $params['command']);
                    $transport = Swift_SendmailTransport::newInstance($command);
                } else {
                    $transport = Swift_SendmailTransport::newInstance();
                }
                $transport->start();
                $transport->stop();
            } catch (Exception $e) {
                $this->errors = self::fixWindowsErrormsg($e->getMessage());
                if (!$this->errors || json_encode($this->errors) === 'null') {
                    waLog::log('mailer: Error checking Sendmail during transport validation: '.$this->errors);
                    $this->errors = _w('Unknown error');
                }
                $this->errors = array('' => _w('Error checking mail transport:').' '.$this->errors);
                return;
            }
        }

        $sender_model = new mailerSenderModel();
        try {
            if ($id) {
                $sender_model->updateById($id, $data);
            } else {
                $id = $sender_model->insert($data);
            }
        } catch (waDbException $e) {
            if (false !== strpos($e->getMessage(), 'Duplicate entry')) {
                $this->errors = array(
                    'data[email]' => _w('Sender Email must be unique'),
                );
            } else {
                $this->errors = array('' => $e->getMessage());
            }
            return;
        }

        // Save sender params
        $spm->save($id, $params);
        $this->response = $id;
    }

    /**
     * Error messages on windows hostings are known to return messages encoded in non-UTF8.
     * This breaks json_encode() later and results in error message being null.
     * This function does its best to decode such messages into UTF-8.
     */
    protected static function fixWindowsErrormsg($errormsg)
    {
        if ($errormsg && json_encode($errormsg) === 'null') {
            $e = @iconv('windows-1251', 'utf-8//IGNORE', $errormsg);
            if ($e && json_encode($e) !== 'null') {
                $errormsg = $e;
            }
        }
        return $errormsg;
    }

    protected function validateArray(&$arr)
    {
        if (!is_array($arr)) {
            throw new waException('Bad POST parameters.');
        }
        foreach($arr as $k => &$v) {
            if ($k != 'password') {
                $v = trim($v);
            }
        }
    }
}

