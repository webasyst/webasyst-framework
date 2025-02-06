<?php

class webasystSettingsSMSAction extends webasystSettingsViewAction
{
    protected $used;

    public function execute()
    {
        $sms_adapters = $this->getSMSAdapters();

        $install_wa_sms_link = null;
        if (wa()->appExists('installer') && wa()->getUser()->getRights('installer', 'backend')) {
            $wa_sms_installed = !!array_filter($sms_adapters, function($a) {
                return $a['id'] == 'webasystsms';
            });
            if (!$wa_sms_installed) {
                $install_wa_sms_link = wa()->getAppUrl('installer').'store/plugin/sms/webasystsms/';
            }
        }

        $this->view->assign(array(
            'sms_adapters' => $sms_adapters,
            'install_wa_sms_link' => $install_wa_sms_link,
        ));
    }

    protected function getSMSAdapters()
    {
        $path = $this->getConfig()->getPath('plugins').'/sms/';
        if (!file_exists($path)) {
            return array();
        }
        $dh = opendir($path);

        // Load config for SMS adapters
        $config = wa()->getConfig()->getConfigFile('sms');
        $config_by_plugin = [];
        foreach ($config as $c) {
            if (!empty($c['adapter'])) {
                $config_by_plugin[$c['adapter']] = $c;
            }
        }

        // Get adapters
        $adapters = array();
        while (($f = readdir($dh)) !== false) {
            if ($f === '.' || $f === '..' || !is_dir($path.$f)) {
                continue;
            } elseif (file_exists($path.$f.'/lib/'.$f.'SMS.class.php')) {
                require_once($path.$f.'/lib/'.$f.'SMS.class.php');
                $class_name = $f.'SMS';
                $adapters[$f] = new $class_name(ifset($config_by_plugin, $f, []));
            }
        }
        closedir($dh);

        if (class_exists('wadebugSMS')) {
            $adapters['wadebug'] = new wadebugSMS();
        }

        $result = array();

        // Get config
        $this->used = [];
        foreach ($config as $c_from => $c) {
            if (isset($adapters[$c['adapter']])) {
                $this->used[$c['adapter']] = 1;
                if (!isset($result[$c['adapter']])) {
                    $temp = $this->getSMSAdapaterInfo($adapters[$c['adapter']]);
                    $temp['config'] = $c;
                    $temp['config']['from'] = array($c_from);
                    $result[$c['adapter']] = $temp;
                } else {
                    $result[$c['adapter']]['config']['from'][] = $c_from;
                }
            }
        }
        $result = array_values($result);

        foreach ($adapters as $id => $a) {
            /**
             * @var waSMSAdapter $a
             */
            if (!empty($this->used[$a->getId()])) {
                continue;
            }
            $info = $this->getSMSAdapaterInfo($a);
            if ($id == 'webasystsms') {
                array_unshift($result, $info);
            } else {
                $result[] = $info;
            }
        }

        return $result;
    }

    protected function getSMSAdapaterInfo(waSMSAdapter $a)
    {
        $temp = $a->getInfo();
        $temp['id'] = $a->getId();
        $temp['controls'] = $a->getControls();

        if (ifset($temp['no_settings'], false) && !empty($this->used) && empty($this->used[$a->getId()])) {
            $mode = ifset($temp, 'no_settings_controls_mode', 'warning');
            $temp['controls_html'] = '';
            if ($mode == 'warning' || $mode == 'both') {
                $temp['controls_html'] .= '<p class="hint">'.
                    sprintf(
                        _ws('%s is not currently used. There are other configured SMS adapters. To use %s, remove settings from all SMS adapters.'),
                        $temp['name'], $temp['name']
                    ) . "</p>\n\n";
            }
            if ($mode == 'controls' || $mode == 'both') {
                $temp['controls_html'] .= $a->getControlsHtml();
            }
        } else {
            $temp['controls_html'] = $a->getControlsHtml();
        }

        return $temp;
    }

}