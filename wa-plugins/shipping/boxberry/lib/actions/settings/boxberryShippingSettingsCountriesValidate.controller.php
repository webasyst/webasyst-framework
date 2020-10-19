<?php

class boxberryShippingSettingsCountriesValidateController extends waJsonController
{
    public function execute()
    {
        $selected_countries = waRequest::post('selected_countries', array(), waRequest::TYPE_ARRAY);
        $all_countries = waRequest::post('all_countries', 1, waRequest::TYPE_INT);

        $countries = '';
        $allowed_countries = boxberryShippingCountriesAdapter::getAllowedCountries();
        if ($all_countries == false && !empty($selected_countries)) {
            foreach ($selected_countries as $country_code) {
                if (!isset($allowed_countries[$country_code])) {
                    unset($selected_countries[$country_code]);
                }
            }
        }

        if ($all_countries || empty($selected_countries) || count($selected_countries) == count($allowed_countries)) {
            $this->response['list_saved_countries'] = _w('All countries');
        } else {
            $countries = $selected_countries;
            $saved_countries = boxberryShippingCountriesAdapter::getCountries($selected_countries);
            $list_saved_countries = array();
            foreach ($saved_countries as $country) {
                $list_saved_countries[] = $country['name'];
                $this->response['country_codes'][] = $country['iso3letter'];
            }
            $this->response['list_saved_countries'] = implode(', ', $list_saved_countries);
        }

        $this->response['countries'] = !empty($countries) ? json_encode($countries) : '';
    }
}