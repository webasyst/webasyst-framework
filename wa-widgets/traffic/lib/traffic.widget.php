<?php

class trafficWidget extends waWidget
{
    public function defaultAction()
    {
        $city = $this->getSettings('city');
        $use_setting_city = true;

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
            'use_setting_city' => $use_setting_city
        ));
    }
}