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
                    'code' => $region,
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
        if (!$region_codes || !is_array($region_codes)) {
            $region_codes = array();
        }
        $region_names = $this->getRegionNames();
        if (!$region_names || !is_array($region_names)) {
            $region_names = array();
        }
        $region_favs = $this->getRegionFavs();
        if (!$region_favs || !is_array($region_favs)) {
            $region_favs = array();
        }

        $regions = array();
        foreach($region_codes as $i => $code) {
            $code = trim($code);
            $name = trim(ifempty($region_names[$i], ''));
            $fav = trim(ifempty($region_favs[$i], ''));
            if (!$name || !$code) {
                continue;
            }
            $regions[$code] = empty($fav) ? $name : array(
                'name' => $name,
                'fav_sort' => $fav,
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
        return $this->getRequest()->post('region_codes');
    }

    protected function getRegionNames()
    {
        return $this->getRequest()->post('region_names');
    }

    protected function getRegionFavs()
    {
        return $this->getRequest()->post('region_favs');
    }

    protected function getCountryFav()
    {
        return $this->getRequest()->post('country_fav', null, 'int');
    }
}