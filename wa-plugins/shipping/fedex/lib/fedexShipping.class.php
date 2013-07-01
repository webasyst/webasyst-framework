<?php

/**
 * @property int $test_mode 0|1
 * @property string $account_number
 * @property string $metter_number
 * @property string $developer_key
 * @property string $developer_password
 * @property string $packaging
 * @property string $carrier
 * @property string $country
 * @property string $region
 * @property string $zip
 * @property string $city
 * @property string $address
 */
class fedexShipping extends waShipping
{

    public function getSettingsHTML($params = array())
    {
        $values = $this->getSettings();
        if (!empty($params['value'])) {
            $values = array_merge($values, $params['value']);
        }

        $view = wa()->getView();

        $cm = new waCountryModel();
        $view->assign('countires', $cm->all());

        if (!empty($values['country'])) {
            $rm = new waRegionModel();
            $view->assign('regions', $rm->getByCountry($values['country']));
        }

        $namespace = '';
        if (!empty($params['namespace'])) {
            if (is_array($params['namespace'])) {
                $namespace = array_shift($params['namespace']);
                while (($namspace_chunk = array_shift($params['namespace'])) !== null) {
                    $namespace .= "[{$namspace_chunk}]";
                }
            } else {
                $namespace = $params['namespace'];
            }
        }
        $view->assign('namespace', $namespace);
        $view->assign('values', $values);
        $view->assign('p', $this);
        $view->assign('xhr_url', wa()->getAppUrl('webasyst').'?module=backend&action=regions');

        $html = $view->fetch($this->path.'/templates/settings.html');
        $html.= parent::getSettingsHTML($params);
        return $html;
    }

    public function allowedCurrency()
    {
        return 'USD';
    }

    public function allowedWeightUnit()
    {
        return 'lbs';
    }

    protected function calculate()
    {
        try {
            $query = $this->prepareQuery();
            $answer = $this->sendQuery($query);
            $parsed_answer = $this->parseAnswer($answer);
        } catch (Exception $e) {
            $error = $e->getMessage();
            //$this->log($error);
            return $error;
        }

        $rates = array();
        foreach ($parsed_answer as $code => $items) {
            foreach ($items as $k => $item) {
                $item['id'] = sprintf("%s%02d", $code, $k);
                $item['currency'] = 'USD';
                $rates[$item['id']] = $item;
            }
        }

        if (empty($rates)) {
            return $this->_w("FedEx web service return an empty response");
        }

        return $rates;

    }

    private function prepareQuery()
    {
        $weight = max(0.1, $this->getTotalWeight());
        //$address = $this->getAddress();
        $request = array(
            'WebAuthenticationDetail' => array(
                'UserCredential' => array(
                    'Key' => $this->developer_key,
                    'Password' => $this->developer_password,
                )
            ),
            'ClientDetail' => array(
                'AccountNumber' => $this->account_number,
                'MeterNumber' => $this->meter_number,
            ),
            'TransactionDetail' => array(
                'CustomerTransactionId' => ''
            ),
            'Version' => array(
                'ServiceId' => 'crs',
                'Major' => '13',
                'Intermediate' => '0',
                'Minor' => '0',
            ),
            'ReturnTransitAndCommit' => 1
        );

        $shipment = array(
            'DropoffType'   => 'REGULAR_PICKUP',   // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
            'ShipTimestamp' => date('c'),
            'PackagingType' => $this->packaging
        );

        if ($this->carrier != 'ALL') {
            $request['CarrierCodes'] = $this->carrier;
        }

        $shipment['Shipper']['Address'] = array(
            'StreetLines'         => $this->address,
            'City'                => $this->city,
            'StateOrProvinceCode' => $this->region,
            'PostalCode'          => $this->zip,
            'CountryCode'         => strtoupper($this->getISO2CountryCode($this->country)),
        );

        $shipment['Recipient']['Address'] = array(
            'StreetLines'         => $this->getAddress('address'),
            'City'                => $this->getAddress('city'),
            'StateOrProvinceCode' => $this->getAddress('region'),
            'PostalCode'          => $this->getAddress('zip'),
            'CountryCode'         => strtoupper($this->getISO2CountryCode($this->getAddress('country'))),
        );

        $shipment['ShippingChargesPayment'] = array(
            'PaymentType' => 'SENDER',
            'Payor' => array(
                'AccountNumber' => $this->account_number,
                'CountryCode'   => strtoupper($this->getISO2CountryCode($this->country)),
            ),
            'RateRequestTypes'  => 'LIST'
        );

        $shipment['PackageCount'] = '1';
        $shipment['PackageDetail'] = 'INDIVIDUAL_PACKAGES'; // Or PACKAGE_SUMMARY
        $shipment['RequestedPackageLineItems'] = array(
            array(
                'SequenceNumber' => 1,
                'GroupNumber'    => 1,
                'GroupPackageCount' => 1,
                'Weight' => array(
                    'Value' => $weight,
                    'Units' => 'LB'
                )
            )
        );

        $request['RequestedShipment'] = $shipment;
        return $request;
    }

    private function sendQuery($data)
    {
        if (!$data) {
            throw new waException($this->_w("Empty query"));
        }

        @ini_set("soap.wsdl_cache_enabled", "0");
        $path_to_wsdl = $this->path.'/lib/config/RateService_v13.wsdl';
        $client = new SoapClient($path_to_wsdl,
            array(
                'trace' => 1,
                'exceptions' => 1
            )
        );
        // Refer to http://www.php.net/manual/en/class.soapclient.php
        if (false && $this->test_mode) {
            $client->__setLocation('https://wsbeta.fedex.com/web-services/rate');
        }

        $response = $client->__soapCall("getRates", array($data));
        //$this->writeToLog($client); // Write to log file

        return $response;
    }

    private function parseAnswer($answer)
    {
        if (!$answer) {
            throw new waException($this->_w("Empty answer"));
        }

        $rates = array();
        $services = $this->getShippingServices();

        if (!empty($answer->RateReplyDetails)) {
            foreach ($answer->RateReplyDetails as $rateReply) {
                $rate = array();
                $type = (string)$rateReply->ServiceType;
                $shipment = $rateReply->RatedShipmentDetails[0];
                $amount = (float)$shipment->ShipmentRateDetail->TotalNetCharge->Amount;
                $rate = array(
                    'name' => $services[$type],
                    'rate' => $amount
                );
                if (isset($rateReply->DeliveryTimestamp)) {
                    $est_delivery = $rateReply->DeliveryTimestamp;
                } else {
                    $est_delivery = $rateReply->TransitTime;
                }
                $rate['est_delivery'] = $est_delivery;
                $rates[$type][] = $rate;
            }
        } else {
            if (!empty($answer->RateReplyDetails)) {
                $severity = strtolower((string)$answer->HighestSeverity);
                if (in_array($severity, array('failure', 'error'))) {
                    foreach ($answer->Notifications as $item) {
                        $rates[$severity][$item['Code']] = array(
                            'rate' => null,
                            'comment' => $item['Message'],
                            'est_delivery' => ''
                        );
                    }
                }
            } else {
                if (is_object($answer->Notifications)) {
                    $notifications = array($answer->Notifications);
                } else {
                    $notifications = $answer->Notifications;
                }
                throw new waException($this->showErrors($notifications));
            }
        }

        return $rates;
    }

    private function showErrors($errors)
    {
        return implode('<br>', array_map(create_function('$item',
            'return $item->Message;'
        ), $errors));
    }

    /**
     * @param string $iso3
     * @throws waException
     * @return string
     */
    private function getISO2CountryCode($iso3)
    {
        $country_model = new waCountryModel();
        $iso2 = $country_model->select('iso2letter')->where(
            'iso3letter = :iso3', array('iso3' => $iso3)
        )->fetchField('iso2letter');
        if (!$iso2) {
            throw new waException($this->_w("Unknown country"));
        }
        return $iso2;
    }

    private function getShippingServices()
    {
        if (!$this->services) {
            $this->services = include($this->path.'/lib/config/services.php');
        }
        return $this->services;
    }

    public function requestedAddressFields()
    {
        return array(
            'zip' =>     array('cost' => true),
            'country' => array('cost' => true)
        );
    }

    public function allowedAddress()
    {
        return array();
    }
}
