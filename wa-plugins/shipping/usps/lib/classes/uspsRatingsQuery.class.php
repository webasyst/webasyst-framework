<?php

class uspsRatingsQuery extends uspsQuery
{
    private $api;

    public function __construct(uspsShipping $plugin, array $params) {
        parent::__construct($plugin, $params);
    }

    private function getServices()
    {
        $country = $this->getAddress('country');
        $type = uspsServices::getTypeByCountry($country);

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

    protected function getAPIName()
    {
        return $this->api;
    }

    protected function getUrl()
    {
        return 'http://production.shippingapis.com/ShippingAPI.dll';
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
                $this->api = 'IntlRateV2';
                $xml = new SimpleXMLElement('<IntlRateV2Request/>');
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
                    $this->prepareDomesticPackage($package, $service);
                    break;
                case 'International':
                    $this->prepareInternationalPackage($package, $service);
                    break;
            }
        }
        return $xml->saveXML();
    }

    /**
     * @param SimpleXMLElement $package
     * @param mixed[string] $service
     * @return mixed
     * @throws waException
     */
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
        $package->addChild('Pounds', $this->getWeight('pounds'));
        $package->addChild('Ounces', $this->getWeight('ounces'));
        $package->addChild('Container');
        $package->addChild('Size', $this->plugin->package_size);
        $package->addChild('Machinable', 'True');

        return $package;
    }

    /**
     * @param SimpleXMLElement $package
     * @param array $service
     * @return mixed
     */
    private function prepareInternationalPackage($package, $service)
    {
        $package->addChild('Pounds',   $this->getWeight('pounds'));
        $package->addChild('Ounces',   $this->getWeight('ounces'));
        $package->addChild('Machinable', 'True');
        $package->addChild('MailType', $service['name']);

        $gxg = $package->addChild('GXG');
        $gxg->addChild('POBoxFlag', 'N');
        $gxg->addChild('GiftFlag',  'N');

        $package->addChild('ValueOfContents', $this->getPrice());
        $package->addChild('Country',  $this->getCountryName($this->getAddress('country')));
        $package->addChild('Container', 'RECTANGULAR');
        $package->addChild('Size', $this->plugin->package_size);

        $package->addChild('Width');
        $package->addChild('Length');
        $package->addChild('Height');
        $package->addChild('Girth');

        return $package;
    }

    protected function parseResponse($response)
    {
        // fix unclosed br tags
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
            case 'IntlRateV2Response':
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

    /**
     * @param SimpleXMLElement $xml
     * @param $errors
     * @return array
     */
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

    /**
     * @param SimpleXMLElement $xml
     * @param array $errors
     * @return array
     */
    private function parseIntlRateResponse($xml, &$errors)
    {
        $rates = array();
        $black_lists = array(
            '/\bgxg/i',
            '/global express guaranteed/i'
        );
        foreach ($xml->xpath('Package') as $package) {

            $package_id = (string) $package['ID'];
            foreach ($package->xpath('Error') as $error) {
                $errors[$package_id] = array(
                        'id'      => $package_id,
                        'rate'    => null,
                        'name'    => (string) $error->Description
                );
            }
            if (empty($errors[$package_id])) {
                foreach ($package->xpath('Service') as $service) {
                    $id = $package_id . sprintf("_%02d", $service['ID']);
                    $description = (string) $service->SvcDescription;

                    $is_black = false;
                    foreach ($black_lists as $pattern) {
                        if (preg_match($pattern, $description)) {
                            $is_black = true;
                            break;
                        }
                    }

                    if (!$is_black) {
                        $name = array_filter(array(
                                (string) $service->MailType,
                                $description,
                        ), 'strlen');
                        $rates[$id] = array(
                                'name'         => strip_tags(html_entity_decode(implode(' - ', $name), ENT_QUOTES, 'UTF-8')),
                                'est_delivery' => (string) $service->SvcCommitments,
                                'rate'         => (float) $service->Postage,
                        );
                    }
                }
            }
        }
        return $rates;
    }
}
