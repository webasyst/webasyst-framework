<?php

class webasystSettingsRegionsAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $cm = new waCountryModel();
        $rm = new waRegionModel();

        $country = $this->getCountry();
        if ($country && $this->getPost()) {
            $this->saveFromPost($rm, $cm, $country);
        }

        $countries = $cm->all();

        if (!$country || empty($countries[$country])) {
            $country = wa()->getSetting('country');
        }
        if (!$country || empty($countries[$country])) {
            $fav = null;
            foreach ($countries as $k => $country) {
                if ($country['fav_sort'] !== null) {
                    $fav = $k;
                    break;
                }
            }
            $country = $fav ? $fav : key($countries);
        }

        $regions = $country ? $rm->getByCountry($country) : array();

        $this->view->assign(array(
            'countries' => $cm->allWithFav($countries),
            'country'   => ifset($countries[$country], $cm->getEmptyRow()),
            'regions'   => $regions
        ));
    }

    /**
     * @param waRegionModel $rm
     * @param waCountryModel $cm
     * @param $country
     */
    protected function saveFromPost($rm, $cm, $country)
    {
        if ($this->getFav()) {
            $region = $this->getRegion();
            $fav_sort = $this->getFavSort();
            if ($fav_sort === '') {
                $fav_sort = null;
            }
            if ($region) {
                $rm->updateByField(array(
                    'country_iso3' => $country,
                    'code'         => $region,
                ), array(
                    'fav_sort' => $fav_sort,
                ));
            } else {
                $cm->updateByField('iso3letter', $country, array(
                    'fav_sort' => $fav_sort,
                ));
            }
            wa()->getResponse()->setStatus(200);
            wa()->getResponse()->addHeader('Content-type', 'application/json; charset=utf-8');
            wa()->getResponse()->sendHeaders();
            echo json_encode(array(
                'status' => 'ok',
                'data'   => 'ok',
            ));
            exit;
        }

        $region_codes = $this->getRegionCodes();
        $region_names = $this->getRegionNames();
        $region_favs = $this->getRegionFavs();
        $region_centers = $this->getRegionCenters();

        $regions = array();
        foreach ($region_codes as $i => $code) {
            $code = trim($code);
            $name = trim(ifempty($region_names[$i], ''));

            if (!$name || !$code) {
                continue;
            }

            // Because the empty string in mysql turns to 0
            $fav_sort = trim(ifempty($region_favs, $i, null));
            $fav_sort = $fav_sort ? $fav_sort : null;

            $regions[$code] = array(
                'name'          => $name,
                'fav_sort'      => $fav_sort,
                'region_center' => trim(ifset($region_centers, $i, null)),
            );
        }

        $rm->saveForCountry($country, $regions);

        $country_fav = $this->getCountryFav();
        $cm->updateByField('iso3letter', $country, array(
            'fav_sort' => ifempty($country_fav),
        ));
    }

    protected function getCountry()
    {
        return $this->getRequest()->request('country');
    }

    protected function getPost()
    {
        return $this->getRequest()->post();
    }

    protected function getFav()
    {
        return $this->getRequest()->post('fav');
    }

    protected function getRegion()
    {
        return $this->getRequest()->post('region');
    }

    protected function getFavSort()
    {
        return $this->getRequest()->post('fav_sort');
    }

    protected function getRegionCodes()
    {
        return waRequest::post('region_codes', [], waRequest::TYPE_ARRAY);
    }

    /**
     * @return array
     */
    protected function getRegionNames()
    {
        return waRequest::post('region_names', [], waRequest::TYPE_ARRAY);
    }

    protected function getRegionCenters()
    {
        return waRequest::post('region_centers', [], waRequest::TYPE_ARRAY);
    }

    protected function getRegionFavs()
    {
        return waRequest::post('region_favs', [], waRequest::TYPE_ARRAY);
    }

    protected function getCountryFav()
    {
        return $this->getRequest()->post('country_fav', null, 'int');
    }
}