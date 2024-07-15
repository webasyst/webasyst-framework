<?php

class webasystSettingsSMSAction extends webasystSettingsViewAction
{
    protected $used;

    public function execute()
    {
        $this->view->assign(array(
            'sms_adapters' => $this->getSMSAdapters(),
        ));
    }

    protected function getSMSAdapters()
    {
        $path = $this->getConfig()->getPath('plugins').'/sms/';
        if (!file_exists($path)) {
            return array();
        }
        $dh = opendir($path);

        // Get adapters
        $adapters = array();
        while (($f = readdir($dh)) !== false) {
            if ($f === '.' || $f === '..' || !is_dir($path.$f)) {
                continue;
            } elseif (file_exists($path.$f.'/lib/'.$f.'SMS.class.php')) {
                require_once($path.$f.'/lib/'.$f.'SMS.class.php');
                $class_name = $f.'SMS';
                $adapters[$f] = new $class_name(array());
            }
        }
        closedir($dh);

        if (class_exists('wadebugSMS')) {
            $adapters['wadebug'] = new wadebugSMS();
        }

        $result = array();

        // Get config
        $config = wa()->getConfig()->getConfigFile('sms');

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
            $result[] = $this->getSMSAdapaterInfo($a);
        }

        return $result;
    }

    protected function getSMSAdapaterInfo(waSMSAdapter $a)
    {
        $temp = $a->getInfo();
        $temp['id'] = $a->getId();
        $temp['controls'] = $a->getControls();

        if (ifset($temp['no_settings'], false) && !empty($this->used) && empty($this->used[$a->getId()])) {
            $temp['controls_html'] = '<p class="hint">'.
                sprintf(
                    _ws('%s is not currently used. There are other configured SMS adapters. To use %s, remove settings from all SMS adapters.'),
                    $temp['name'], $temp['name']
                ) . '</p>';
        } else {
            $temp['controls_html'] = $a->getControlsHtml();
        }

        return $temp;
    }

}