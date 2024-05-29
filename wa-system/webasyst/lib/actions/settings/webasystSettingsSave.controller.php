<?php

class webasystSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $app_id = $this->getRequest()->post('app_id', false,  '');
        $name = $this->getRequest()->post('name');
        $value = $this->getRequest()->post('value');

        if ($app_id === 'webasyst') {
            if ($name === 'announcement_close') {
                $value = $this->getUser()->getSettings($app_id, $name);
                if (!$value) {
                    $value = '{}';
                }
                if ($value[0] === '{') {
                    $value = @json_decode($value, true);
                    if (!$value) {
                        $value = [];
                    }
                } else {
                    $value = [
                        '' => @date('Y-m-d H:i:s', strtotime($value)),
                    ];
                }
                $contact_id = waRequest::post('contact_id', null, 'int');
                $value[ifset($contact_id, '')] = date("Y-m-d H:i:s");
                $value = json_encode($value);
            } else if (!in_array($name, ['apps', 'wa_announcement_seen', 'webasyst_id_announcement_close'])) {
                return; // only explicitly listed keys are allowed for 'webasyst' app
            }
        }

        if ($value === 'now()') {
            $value = date("Y-m-d H:i:s");
        }
        $this->response = $this->getUser()->setSettings($app_id, $name, $value);
    }
}
