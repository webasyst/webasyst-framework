<?php

class siteSystemSettingsEmailSaveController extends waJsonController
{
    protected $config_params = array(
        'mail'     => array('type', 'options'),
        'smtp'     => array('type', 'host', 'port', 'login', 'password', 'encryption', 'pop3_host', 'pop3_port'),
        'sendmail' => array('type', 'command'),
    );

    protected $dkim_params = array('dkim_pvt_key', 'dkim_pub_key', 'dkim_selector');

    public function execute()
    {
        $email = waRequest::post('email', null, waRequest::TYPE_STRING_TRIM);
        $sender = waRequest::post('sender', null, waRequest::TYPE_STRING_TRIM);

        if ($email && !$this->isValidEmail($email)) {
            $this->errors[] = array(
                'field'   => 'email',
                'message' => _w('Invalid e-mail'),
            );
        }

        if ($sender && !$this->isValidEmail($sender)) {
            $this->errors[] = array(
                'field'   => 'sender',
                'message' => _w('Invalid e-mail'),
            );
        }

        if ($this->errors) {
            return $this->errors;
        }

        $model = new waAppSettingsModel();
        $model->set('webasyst', 'email', $email);
        $model->set('webasyst', 'sender', $sender);

        $data = waRequest::post('data',null,waRequest::TYPE_ARRAY_TRIM);
        $data = $this->prepareDate($data);
        $wa_mail = new waMail();
        $wa_mail->saveConfigFile($data);
    }

    protected function prepareDate(array $data)
    {
        $configs = array();
        foreach ($data as $key => $config) {
            if (($key !== 'default' && !$this->isValidEmail($key) && !$this->isValidDomain($key)) || !isset($this->config_params[ifempty($config['type'])])) {
                continue;
            }

            foreach ($this->config_params[$config['type']] as $param) {
                $configs[$key][$param] = ifset($config[$param]);
            }

            if (!empty($config['dkim'])) {
                $configs[$key]['dkim'] = true;
                foreach ($this->dkim_params as $param) {
                    $configs[$key][$param] = ifempty($config[$param]);
                }
            }
        }
        return $configs;
    }

    protected function isValidEmail($email) {
        if (!preg_match('~^[^\s@]+@[^\s@]+\.[^\s@\.]{2,6}$~u', $email)) {
            return false;
        }
        return true;
    }

    protected function isValidDomain($domain)
    {
        if (!preg_match('~^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$~u', $domain)) {
            return false;
        }
        return true;
    }
}