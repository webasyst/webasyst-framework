<?php
class installerAppsCli extends waCliController
{
    public function execute()
    {
        $params = waRequest::param();
        $installer = new waInstallerApps();
        $list = $installer->getVersions();
        //TODO check it
        $fields = array();
        if (isset($params['full'])) {
            $fields = array(
                'vendor'  => 'Vendor ID',
                'version' => 'Version',
                'id'      => 'Application ID',
            );
        }
        $apps = array();
        foreach ($list as $item) {
            if (!empty($item['enabled']) && !empty($item['installed'])) {
                if ($id = ifset($item['installed']['id'])) {
                    $apps[$id] = array();
                    foreach ($fields as $field => $name) {
                        $apps[$id][$field] = ifset($item['installed'][$field]);
                    }
                }

            }
        }
        switch (waRequest::param('format')) {
            case 'tsv':
                $separator = "\t";
                break;
            case 'csv':
            default:
                $separator = ",";
                break;
        }

        if ($fields) {
            print implode($separator, $fields)."\n";
            foreach ($apps as $data) {
                print implode($separator, $data)."\n";
            }
        } else {
            print implode($separator, array_keys($apps))."\n";
        }
    }
}
