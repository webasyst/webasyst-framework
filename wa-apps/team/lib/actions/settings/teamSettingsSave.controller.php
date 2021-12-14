<?php

class teamSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $data = $this->getData();
        $this->saveData($data);

        $tasm = new teamWaAppSettingsModel();
        $this->response = array(
            'map_info' => $tasm->getMapInfo(),
            'lang' => wa()->getLocale()
        );
    }

    public function saveData($data)
    {
        $tasm = new teamWaAppSettingsModel();
        if (!empty($data['user_name_format'])) {
            $tasm->saveUserNameDisplayFormat($data['user_name_format']);
        }
        $tasm->setMapInfo(ifset($data['map']['adapter']), (array) ifset($data['map']['settings']));
    }

    public function getData()
    {
        return (array)$this->getRequest()->post('data');
    }
}
