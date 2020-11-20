<?php

class boxberryShippingSettingsRegionsValidateController extends waJsonController
{
    public function execute()
    {
        $selected_regions = waRequest::post('selected_regions', array(), waRequest::TYPE_ARRAY);
        $country_code = waRequest::post('country_code', '', waRequest::TYPE_STRING);
        $all_regions = waRequest::post('all_regions', 1, waRequest::TYPE_INT);

        $regions = json_decode(waRequest::post('saved_region_codes', '', waRequest::TYPE_STRING), true);
        if (!is_array($regions)) {
            $regions = array();
        }
        $regions[$country_code] = '';

        $all_country_regions = boxberryShippingCountriesAdapter::getRegions($country_code);
        if ($all_regions || empty($selected_regions) || count($selected_regions) == count($all_country_regions)) {
            $bxb = waShipping::factory('boxberry');
            $this->response['list_saved_regions'] = $bxb->_w('All regions');
        } else {
            $regions[$country_code] = $selected_regions;
            $list_saved_regions = array();
            foreach ($all_country_regions as $region) {
                if (array_search($region['code'], $selected_regions) !== false) {
                    $list_saved_regions[] = $region['name'];
                }
            }
            $this->response['list_saved_regions'] = implode(', ', $list_saved_regions);
        }

        $this->response['regions'] = json_encode($regions);
    }
}