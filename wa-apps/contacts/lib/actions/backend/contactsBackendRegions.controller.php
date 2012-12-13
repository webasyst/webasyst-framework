<?php

/**
 * List of regions for given country for waContactRegionField
 */
class contactsBackendRegionsController extends waJsonController
{
    public function execute()
    {
        $this->response = array(
            'options' => array(),
            'oOrder' => array(),
        );
        $country = waRequest::request('country');
        if (!$country) {
            return;
        }

        $rm = new waRegionModel();
        foreach($rm->getByCountry($country) as $row) {
            $this->response['options'][$row['code']] = $row['name'];
            $this->response['oOrder'][] = $row['code'];
        }
    }
}

