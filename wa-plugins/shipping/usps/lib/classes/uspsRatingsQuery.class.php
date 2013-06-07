<?php

class uspsRatingsQuery extends uspsQuery
{
    private $api;

    public function __construct(uspsShipping $plugin, array $params) {
        $weight = max(0.1, $params['weight']);
        $pounds = floor($weight);
        $ounces = round(16.0 * ($weight - $pounds), 2);
        $params['weight'] = array(
            'pounds' => $pounds,
            'ounces' => $ounces
        );
        parent::__construct($plugin, $params);
    }

    private function getServices()
    {
        $country = $this->getAddress('country');
        $type = uspsServices::getTypeByCountry($country);

        $filter = array();
        if ($type == uspsServices::TYPE_DOMESTIC) {
            $filter = array_keys($this->plugin->services_domestic);
        } else {
            $filter = array_keys($this->plugin->services_international);
        }

        $services = uspsServices::getServicesFiltered($type, $filter);

        if (empty($services)) {
            throw new waException(
                $this->plugin->_w("There's no any service for this country: " . $country)
            );
        }

        return $services;
    }

    protected function getUrl()
    {
        return $this->api ? 'http://production.shippingapis.com/ShippingAPI.dll?API='.$this->api : '';
    }

    /**
     * @see uspsQuery::prepareRequest()
     * @throws waException
     */
    protected function prepareRequest() {

        $services = $this->getServices();
        $type = uspsServices::getServiceType($services[0]['id']);

        if (!$this->plugin->zip) {
            throw new waException(
                $this->plugin->_w("Cannot calculate shipping rate because origin (sender's) ZIP code is not defined in USPS module settings")
            );
        }

        switch ($type) {
            case uspsServices::TYPE_DOMESTIC:
                $this->api = 'RateV4';
                $xml = new SimpleXMLElement('<RateV4Request/>');
                $xml->addChild('Revision');
                break;
            case uspsServices::TYPE_INTERNATIONAL:
                $this->api = 'IntlRate';
                $xml = new SimpleXMLElement('<IntlRateRequest/>');
                break;
            default:
                throw new waException($this->plugin->_w("Unknown type of service"));
        }

        $xml->addAttribute('USERID', $this->plugin->user_id);
        $xml->addAttribute('PASSWORD', '');

        foreach ($services as $service) {
            $package = $xml->addChild('Package');
            $package->addAttribute('ID', str_replace(' ', '_', $service['code']));
            switch ($type) {
                case 'Domestic':
                    $package = $this->prepareDomesticPackage($package, $service);
                    break;
                case 'International':
                    $package = $this->prepareInternationalPackage($package, $service);
                    break;
            }
        }

        return $xml->saveXML();
    }

    private function prepareDomesticPackage($package, $service)
    {
        $code = strtoupper($service['code']);
        $package->addChild('Service', $code);

        if (in_array($code, array('FIRST CLASS', 'FIRST CLASS HFP COMMERCIAL'))) {
            $package->addChild('FirstClassMailType', 'FLAT');
        }

        $package->addChild('ZipOrigination', $this->plugin->zip);

        $zip = $this->getAddress('zip');
        if (preg_match('/([\d]{1,5})/', $zip, $m)) {
            $zip = $m[1];
        }
        if (!$zip) {
            throw new waException($this->plugin->_w(
                    "Enter ZIP code to get shipping estimate"
            ));
        }

        $package->addChild('ZipDestination', $zip);
        $package->addChild('Pounds', str_replace(',', '.', $this->getWeight('pounds')));
        $package->addChild('Ounces', str_replace(',', '.', $this->getWeight('ounces')));
        $package->addChild('Container');
        $package->addChild('Size', $this->plugin->package_size);
        $package->addChild('Machinable', 'True');

        return $package;
    }

    private function prepareInternationalPackage($package, $service)
    {
        $package->addChild('Pounds',   $this->getWeight('pounds'));
        $package->addChild('Ounces',   $this->getWeight('ounces'));
        $package->addChild('MailType', $service['name']);
        $package->addChild('Country',  $this->getCountryName($this->getAddress('country')));
        //$package->addChild('ValueOfContents',round($this->getTotalPrice(), 2));
        return $package;
    }

    protected function parseResponse($response)
    {
        // fix unclose br tags
        $response = preg_replace("/<br\s*?>/i", '', $response);

        $errors = array();
        $rates = array();
        try {
            $xml = new SimpleXMLElement($response);
        } catch (Exception $ex) {
            throw new waException($this->plugin->_w("Xml isn't well-formed"));
        }

        switch ($xml->getName()) {
            case 'RateV4Response':
                $rates = $this->parseRateV4Response($xml, $errors);
                break;
            case 'IntlRateResponse':
                $rates = $this->parseIntlRateResponse($xml, $errors);
                break;
            case 'Error':
                throw new waException((string) $xml->Description, (int) $xml->Number);
                break;
        }

        $services = uspsServices::getServices();
        foreach ($errors as $error) {
            if (isset($services[$error['id']])) {
                $error['comment'] = $services[$error['id']]['name'] . ': '. $error['name'];
            }
            $rates[$error['id']] = $error;
        }
        foreach ($rates as &$rate) {
            $rate['est_delivery'] = isset($rate['est_delivery']) ? $rate['est_delivery'] : '';
            $rate['currency'] = 'USD';
        }
        unset($rate);

        return $rates;
    }

    private function parseRateV4Response($xml, &$errors)
    {
        $rates = array();
        foreach ($xml->xpath('/RateV4Response/Package') as $package) {
            $id = (string) $package['ID'];

            foreach ($package->xpath('Error') as $error) {
                $errors[$id] = array(
                    'id'      => $id,
                    'rate'    => 0,
                    'name'    => (string) $error->Description
                );
            }
            if (empty($errors[$id])) {
                $rates[$id] = array();
                foreach ($package->xpath('Postage') as $service) {
                    $rates[$id] = array(
                        'id'           => 0,
                        'name'         => strip_tags(html_entity_decode($service->MailService, ENT_QUOTES, 'UTF-8')),
                        'rate'         => (float) $service->Rate,
                    );
                }
            }
        }
        return $rates;
    }

    private function parseIntlRateResponse($xml, &$errors)
    {
        $rates = array();
        foreach ($xml->xpath('/IntlRateResponse/Package') as $package) {

            $id = (string) $package['ID'];
            foreach ($package->xpath('Error') as $error) {
                $errors[$id] = array(
                        'id'      => $id,
                        'rate'    => null,
                        'name'    => (string) $error->Description
                );
            }
            if (empty($errors[$id])) {
                foreach ($package->xpath('Service') as $service) {
                    $id .= sprintf("_%02d", $service['ID']);
                    $name = array_filter(array(
                            (string) $service->MailType,
                            (string) $service->SvcDescription,
                    ), 'strlen');
                    $rates[$id] = array(
                            'name'         => strip_tags(html_entity_decode(implode(' - ', $name), ENT_QUOTES, 'UTF-8')),
                            'est_delivery' => (string) $service->SvcCommitments,
                            'rate'         => (float) $service->Postage,
                    );
                }
            }
        }
        return $rates;
    }

    /**
     * @param string $code iso3 code
     * @throws waException
     * @return string
     */
    private function getCountryName($code) {
        $country_model = new waCountryModel();
        $country = $country_model->get($code);
        if (!$country) {
            throw new waException($this->plugin->_w("Unknow country: "). $code);
        }
        $iso2letter = strtoupper($country['iso2letter']);
        $country_list = $this->getCountryList();
        if (!isset($country_list[$iso2letter])) {
            throw new waException($this->plugin->_w("Unknow country: "). $code);
        }
        return $country_list[$iso2letter];
    }

    private function getCountryList()
    {
        return include($this->plugin->getPluginPath().'/lib/config/countries.php');
    }
}