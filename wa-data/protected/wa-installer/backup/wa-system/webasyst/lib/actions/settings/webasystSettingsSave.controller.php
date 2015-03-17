<?php 

class webasystSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $app_id = $this->getRequest()->post('app_id', false,  '');
        $name = $this->getRequest()->post('name');
        $value = $this->getRequest()->post('value');
        if ($value === 'now()') {
            $value = date("Y-m-d H:i:s");
        }
        $this->response = $this->getUser()->setSettings($app_id, $name, $value);
    }
}