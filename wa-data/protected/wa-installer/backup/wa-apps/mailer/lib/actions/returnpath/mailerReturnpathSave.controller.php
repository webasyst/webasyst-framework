<?php

class mailerReturnpathSaveController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $id = waRequest::post('id');
        $data = waRequest::post('data');
        $data['ssl'] = empty($data['ssl']) ? 0 : 1;

        $rpm = new mailerReturnPathModel();
        if ($id) {
            $old_rp = $rpm->getById($id);
            if (!$old_rp) {
                throw new waException('Return-path not found.');
            }
            // Make sure user did not change email
            $data['email'] = $old_rp['email'];
            if (empty($data['password']) && $old_rp['password']) {
                $data['password'] = $old_rp['password'];
            }
        }

        // Try to connect using given settings
        try {

            // Check if SSL is supported
            if (!defined('OPENSSL_VERSION_NUMBER') && !empty($data['ssl'])) {
                throw new waException(_w('Encryption requires OpenSSL PHP module to be installed.'));
            }

            $mail_reader = new waMailPOP3($data);
            $mail_reader->count();
        } catch (Exception $e) {
            $this->errors = $e->getMessage();
            if (!$this->errors || $this->errors == ' ()') {
                $this->errors = _w('Unknown error');
            } else if (FALSE !== strpos($this->errors, 'IMAP')) {
                $this->errors = _w('IMAP is not supported. Please use POP3.');
            }
            return;
        }

        // Reset last error since we know settings are now ok
        $data['last_error'] = null;

        if ($id) {
            unset($data['email']);
            $rpm->updateById($id, $data);
        } else {
            $id = $rpm->insert($data);
        }

        wa()->getStorage()->set('mailer_rp_status_'.$id, true);

        $this->response = $id;
    }
}

