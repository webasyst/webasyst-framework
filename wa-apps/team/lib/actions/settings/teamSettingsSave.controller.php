<?php

class teamSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $data = $this->getData();
        $this->saveData($data);

        $tasm = new teamWaAppSettingsModel();
        $this->response = array(
            'map_provider' => $tasm->getMapProvider(),
            'google_map_key' => $tasm->getGoogleMapKey(),
            'lang' => wa()->getLocale()
        );
    }

    public function saveData($data)
    {
        $tasm = new teamWaAppSettingsModel();
        if (!empty($data['user_name_format'])) {
            $tasm->saveUserNameDisplayFormat($data['user_name_format']);
        }
        $tasm->setMap(ifset($data['map']['map_provider']), ifset($data['map']['google_map_key']));
    }

    public function getData()
    {
        $data = (array) $this->getRequest()->post('data');
        return $data;
    }
}
