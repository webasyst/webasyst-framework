<?php

/**
 * Class boxberryShippingGetSettings
 */
class boxberryShippingGetSettings
{
    /**
     * @var boxberryShipping|null
     */
    protected $bxb = null;

    /**
     * boxberryShippingGetSettings constructor.
     * @param boxberryShipping $bxb
     */
    public function __construct(boxberryShipping $bxb)
    {
        $this->bxb = $bxb;
    }

    /**
     * @param array $params
     * @return string
     */
    public function getHtml($params = array())
    {
        $view = wa()->getView();
        $points_for_parcel = $this->getAllPointsForParcels();

        $saved_country_codes = $this->bxb->getSettings('countries');
        $shop_plugin_settings = new shopPluginSettingsModel();
        $deletable_params = array();
        $country = $this->bxb->getSettings('country');
        if (isset($country)) {
            if (empty($country)) {
                $shop_plugin_settings->del($this->bxb->getPluginKey(), 'country');
            } else {
                $deletable_params['has_field_country'] = true;
                $saved_country_codes = array('rus');
            }
        }
        $saved_countries = boxberryShippingCountriesAdapter::getCountries($saved_country_codes);

        $saved_region_codes = $this->bxb->getSettings('regions');
        if (is_string($saved_region_codes)) {
            $saved_region_codes = json_decode($saved_region_codes, true);
        }
        $region = $this->bxb->getSettings('region');
        if (isset($region)) {
            if (empty($region)) {
                $shop_plugin_settings->del($this->bxb->getPluginKey(), 'region');
            } else {
                if (empty($saved_region_codes['rus'])) {
                    $saved_region_codes['rus'] = array($region);
                } else {
                    $saved_region_codes['rus'] += array($region);
                }
                $deletable_params['has_field_region'] = true;
            }
        }

        $view->assign($deletable_params + array(
            'obj'                   => $this->bxb,
            'settings'              => $this->bxb->getSettings(),
            'namespace'             => waHtmlControl::makeNamespace($params),
            'points_for_parcel'     => $points_for_parcel,
            'points_by_settings'    => $this->getPointsForParcelsBySettings($points_for_parcel),
            'list_saved_countries'  => $this->getListSavedCountries($saved_country_codes, $saved_countries),
            'all_allowed_countries' => $this->getAllAllowedCountries($saved_region_codes, $saved_countries),
            'saved_country_codes'   => !empty($saved_country_codes) ? json_encode($saved_country_codes) : '',
            'saved_region_codes'    => json_encode($saved_region_codes),
            'point_modes'           => $this->getPointModes(),
            'courier_modes'         => $this->getCourierModes(),
            'issuance_options'      => $this->getIssuanceOptions(),
        ));

        $path = $this->bxb->getPluginPath();
        $html = $view->fetch($path.'/templates/settings.html');
        return $html;
    }

    private function getListSavedCountries($saved_country_codes, $saved_countries)
    {
        if (empty($saved_country_codes)) {
            $list_saved_countries = $this->bxb->_w('All countries');
        } else {
            $country_names = array();
            foreach ($saved_countries as $country) {
                $country_names[] = $country['name'];
            }
            $list_saved_countries = implode(', ', $country_names);
        }
        return $list_saved_countries;
    }

    private function getAllAllowedCountries($saved_region_codes, $saved_countries)
    {
        $all_allowed_countries = boxberryShippingCountriesAdapter::getCountries();
        $all_regions = boxberryShippingCountriesAdapter::getRegions();

        foreach ($all_regions as $region) {
            $all_allowed_countries[$region['country_iso3']]['regions'][$region['code']] = $region;
        }
        foreach ($saved_region_codes as $country_iso3 => $region_codes) {
            if (empty($region_codes)) {
                $all_allowed_countries[$country_iso3]['list_saved_regions'] = $this->bxb->_w('All regions');
            } else {
                $list_saved_regions = array();
                foreach ($region_codes as $region_code) {
                    $list_saved_regions[] = $all_allowed_countries[$country_iso3]['regions'][$region_code]['name'];
                }
                $all_allowed_countries[$country_iso3]['list_saved_regions'] = implode(', ', $list_saved_regions);
            }
        }

        foreach ($all_allowed_countries as $key => $country) {
            $all_allowed_countries[$key]['enabled'] = isset($saved_countries[$key]);
        }

        return $all_allowed_countries;
    }

    /**
     * Returns a saved pickup point
     *
     * @param $points_for_parcel
     * @return array
     */
    public function getPointsForParcelsBySettings($points_for_parcel)
    {
        $targetstart = $this->bxb->getSettings('targetstart');

        $result = [
            'targetstart' => $targetstart,
            'city'        => '',
            'points'      => []
        ];

        if ($targetstart) {
            foreach ($points_for_parcel as $city => $points) {
                foreach ($points as $point_data) {
                    if (ifset($point_data, 'code', false) == $targetstart) {
                        $result['city'] = $city;
                        $result['points'] = $points;
                        break 2;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAllPointsForParcels()
    {
        $handbook_manager = new boxberryShippingHandbookPointsForParcels($this->getApiManager());
        $points = $handbook_manager->getHandbook();

        return $points;
    }

    /**
     * @return boxberryShippingApiManager
     */
    protected function getApiManager()
    {
        return new boxberryShippingApiManager($this->bxb->token, $this->bxb->api_url, $this->bxb);
    }

    /**
     * @return array
     */
    public function getPointModes()
    {
        return array(
            array(
                'value' => 'off',
                'title' => $this->bxb->_w('Do not use'),
            ),
            array(
                'value' => 'all',
                'title' => $this->bxb->_w('All'),
            ),
            array(
                'value' => 'prepayment',
                'title' => $this->bxb->_w('With prepayment only'),
            ),
        );
    }

    /**
     * @return array
     */
    public function getCourierModes()
    {
        return array(
            array(
                'value' => 'off',
                'title' => $this->bxb->_w('Do not use'),
            ),
            array(
                'value' => 'all',
                'title' => $this->bxb->_w('All'),
            ),
            array(
                'value' => 'prepayment',
                'title' => $this->bxb->_w('With prepayment only'),
            ),
        );
    }

    /**
     * @return array
     */
    public function getIssuanceOptions()
    {
        return array(
            array(
                'value' => '0',
                'title' => $this->bxb->_w('Delivery without parcel opening'),
            ),
            array(
                'value' => '1',
                'title' => $this->bxb->_w('Delivery with parcel opening and completeness check'),
            ),
            array(
                'value' => '2',
                'title' => $this->bxb->_w('Delivery of a parcel part'),
            ),
        );
    }
}
