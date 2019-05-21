<?php

class webasystSettingsSMSSaveController extends webasystSettingsJsonController
{
    public function execute()
    {
        $sms = wa()->getRequest()->post('sms');
        if (is_array($sms)) {
            $errors = $this->saveSMSAdapters($sms);
            if ($errors) {
                $this->errors = $errors;
            }
        }
    }

    protected function saveSMSAdapters($sms)
    {
        $path = $this->getConfig()->getPath('config', 'sms');

        $errors = array();

        $adapter_indexes = array();

        $save = array();
        foreach ($sms as $index => $s) {

            // validate errors has occurred - stop looping
            if ($errors) {
                break;
            }

            $from = $s['from'];
            $adapter = $s['adapter'];

            $adapter_indexes[$adapter] = $index;

            unset($s['from'], $s['adapter']);

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
                    if (isset($save[$from])) {
                        $used_adapter = $save[$from]['adapter'];
                        $used_adapter_index = $adapter_indexes[$used_adapter];
                        $errors["sms[{$index}][from]"] = sprintf(_ws('Sender ID “%s” is already in use by “%s” provider. You can save each sender ID for one provider only.'), $from, $used_adapter);
                        $errors["sms[{$used_adapter_index}][from]"] = sprintf(_ws('Sender ID “%s” is already in use by “%s” provider. You can save each sender ID for one provider only.'), $from, $adapter);
                        break;
                    }
                    $save[$from] = $s;
                    $save[$from]['adapter'] = $adapter;
                }
            }
        }

        if (!$errors) {
            waUtils::varExportToFile($save, $path);
        }

        return $errors;


    }
}
