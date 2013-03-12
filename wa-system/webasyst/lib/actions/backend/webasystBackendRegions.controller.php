<?php

/**
 * List of regions for given country for waContactRegionField
 */
class webasystBackendRegionsController extends waJsonController
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
        foreach($rm->getByCountryWithFav($country) as $row) {
            if ($row['name'] === '') {
                $this->response['options'][''] = ' ';
                $this->response['oOrder'][] = '';
            } else {
                $this->response['options'][$row['code']] = $row['name'];
                $this->response['oOrder'][] = $row['code'];
            }
        }
    }
}
