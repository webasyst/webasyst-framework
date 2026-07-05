<?php

class webasystSettingsEmailMakeDefaultController extends webasystSettingsJsonController
{
    public function execute()
    {
        $new_default_config_key = waRequest::post('key', null, waRequest::TYPE_STRING_TRIM);
        if (!$new_default_config_key || $new_default_config_key == 'default') {
            return;
        }

        $mail_config = [];
        $mail_config_path = wa()->getConfig()->getPath('config', 'mail');
        if (is_readable($mail_config_path)) {
            $mail_config = include($mail_config_path);
        }

        if (!isset($mail_config[$new_default_config_key])) {
            // @ allows to set default transport by type or create a new one
            if ($new_default_config_key[0] === '@') {
                $transport_type = substr($new_default_config_key, 1);
                $new_default_config_key = null;
                foreach ($mail_config as $k => $config) {
                    if ($transport_type === ifset($config, 'type', '')) {
                        $new_default_config_key = $k;
                        break;
                    }
                }
                if (!$new_default_config_key && $transport_type) {
                    $new_default_config_key = $this->generateLegacyConfigKey($mail_config);
                    $mail_config[$new_default_config_key] = [
                        'type' => $transport_type,
                    ];
                }
            }
            if (!$new_default_config_key || !isset($mail_config[$new_default_config_key])) {
                return;
            }
        }
        $new_default_transport = $mail_config[$new_default_config_key];
        $new_default_transport['_domain'] = $new_default_config_key;

        if (isset($mail_config['default'])) {
            $old_default_transport = $mail_config['default'];
            if (isset($old_default_transport['_domain'])) {
                $old_default_domain = $old_default_transport['_domain'];
            } else if ($old_default_transport['type'] == 'smtp' && isset($old_default_transport['login']) && false !== strpos($old_default_transport['login'], '@')) {
                list($_, $old_default_domain) = explode('@', $old_default_transport['login'], 2);
            } else {
                $old_default_domain = $this->generateLegacyConfigKey($mail_config);
            }
            $mail_config[$old_default_domain] = $old_default_transport;
        }

        unset($mail_config[$new_default_config_key]);
        $mail_config['default'] = $new_default_transport;

        waUtils::varExportToFile($mail_config, $mail_config_path);
    }

    protected function generateLegacyConfigKey($mail_config)
    {
        $old_default_domain = 'legacy-'.date('Ymd');
        while (isset($mail_config[$old_default_domain.'.fake'])) {
            $old_default_domain .= mt_rand(0, 9);
        }
        $old_default_domain .= '.fake';
        return $old_default_domain;
    }
}
