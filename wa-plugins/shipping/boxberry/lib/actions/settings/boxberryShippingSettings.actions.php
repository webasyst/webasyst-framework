<?php

class boxberryShippingSettingsActions extends waSystemPluginActions
{

    public function countryDialogAction()
    {
        $countries = boxberryShippingCountriesAdapter::getCountries();
        $selected_countries = json_decode(waRequest::post('saved_country_codes', '', waRequest::TYPE_STRING), true);
        if (!empty($selected_countries) && is_array($selected_countries)) {
            $selected_countries = array_flip($selected_countries);
        }

        $this->display(array(
            'obj' => $this->plugin,
            'countries' => $countries,
            'selected_countries' => $selected_countries,
        ));
    }

    public function regionDialogAction()
    {
        $country_code = waRequest::post('country', '', waRequest::TYPE_STRING);

        $regions = boxberryShippingCountriesAdapter::getRegions($country_code);
        $selected_regions = json_decode(waRequest::post('saved_region_codes', '', waRequest::TYPE_STRING), true)[$country_code];
        if (!empty($selected_regions) && is_array($selected_regions)) {
            $selected_regions = array_flip($selected_regions);
        }

        $this->display(array(
            'obj' => $this->plugin,
            'country_code' => $country_code,
            'regions' => $regions,
            'selected_regions' => $selected_regions,
            'saved_region_codes' => waRequest::post('saved_region_codes', '', waRequest::TYPE_STRING),
        ));
    }
}