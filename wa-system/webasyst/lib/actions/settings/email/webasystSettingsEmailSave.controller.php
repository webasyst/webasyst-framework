<?php

class webasystSettingsEmailSaveController extends webasystSettingsJsonController
{
    protected $config_params = array(
        'mail'     => array('type', 'options'),
        'smtp'     => array('type', 'host', 'port', 'login', 'password', 'encryption', 'pop3_host', 'pop3_port'),
        'sendmail' => array('type', 'command'),
        'wadebug'  => array('type'),
    );

    protected $config_default_values = array(
        'smtp' => array('port' => 25),
    );

    protected $dkim_params = array('dkim_pvt_key', 'dkim_pub_key', 'dkim_selector');

    public function execute()
    {
        $email = waRequest::post('email', null, waRequest::TYPE_STRING_TRIM);
        $sender = waRequest::post('sender', null, waRequest::TYPE_STRING_TRIM);

        if ($email && !$this->isValidEmail($email)) {
            $this->errors[] = array(
                'field'   => 'email',
                'message' => _ws('Invalid Email'),
            );
        }

        if ($sender && !$this->isValidEmail($sender)) {
            $this->errors[] = array(
                'field'   => 'sender',
                'message' => _ws('Invalid Email'),
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
                if (empty($configs[$key][$param]) && !empty($this->config_default_values[$config['type']][$param])) {
                    $configs[$key][$param] = $this->config_default_values[$config['type']][$param];
                }
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
        if (!preg_match('~^[^\s@]+@[^\s@]+(\.+[а-яА-ЯЁёA-Za-z]{2,6})?$~u', $email)) {
            return false;
        }
        return true;
    }

    protected function isValidDomain($domain)
    {
        if (!preg_match('~^(?:[^-.][-а-яА-ЯЁёA-Za-z0-9]{0,61}[^-.]\.)+([а-яА-ЯЁёA-Za-z]{2,6})$|^([а-яА-ЯЁёA-Za-z]+)$~u', $domain)) {
            return false;
        }
        return true;
    }
}