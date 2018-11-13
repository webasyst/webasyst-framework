<?php

class webasystSettingsSMSSaveController extends webasystSettingsJsonController
{
    public function execute()
    {
        $sms = wa()->getRequest()->post('sms');
        if (is_array($sms)) {
            $this->saveSMSAdapters($sms);
        }
    }

    protected function saveSMSAdapters($sms)
    {
        $path = $this->getConfig()->getPath('config', 'sms');
        $save = array();
        foreach ($sms as $s) {
            $from = $s['from'];
            $adapter = $s['adapter'];
            unset($s['from']);
            unset($s['adapter']);
            $empty = true;
            foreach ($s as $v) {
                if ($v) {
                    $empty = false;
                    break;
                }
            }
            if (!$empty) {
                if (!$from) {
                    $from = '*';
                }
                foreach (explode("\n", $from) as $from) {
                    $from = trim($from);
                    $save[$from] = $s;
                    $save[$from]['adapter'] = $adapter;
                }
            }
        }
        waUtils::varExportToFile($save, $path);
    }
}