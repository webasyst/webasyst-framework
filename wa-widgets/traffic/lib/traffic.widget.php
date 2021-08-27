<?php

class trafficWidget extends waWidget
{
    public function defaultAction()
    {
        $city = $this->getSettings('city');
        $use_setting_city = true;

        $map = wa()->getMap('yandex');
        $map->setEnvironment(waMapAdapter::FRONTEND_ENVIRONMENT);
        $yandex_adapter_settings = $map->getSettings();
        $apikey = '';
        if($yandex_adapter_settings){
            $apikey = $yandex_adapter_settings['apikey'];
        }

        if (!$city) {
            $use_setting_city = false;
            $addresses = wa()->getUser()->get('address');
            foreach ($addresses as $address) {
                $city = ifset($address['data']['city'], '');
                if ($city) {
                    break;
                }
            }
        }

        $this->display(array(
            'info' => $this->getInfo(),
            'city' => $city,
            'apikey' => $apikey,
            'use_setting_city' => $use_setting_city
        ));
    }
}