<?php

/**
 * Query implements USPS Tracking™ of Print Shipping Labels API
 *
 * Generate USPS Tracking barcoded labels for
 * Priority Mail®, First-Class Mail® parcels, and package services parcels,
 * including Standard Post™, Media Mail®, and Library Mail.
 */
class uspsLabelsUspsTrackingQuery extends uspsQuery
{
    /**
     * @var array
     */
    private $service;

    public function __construct(uspsShipping $plugin, array $params)
    {
        // shipping rate ID is abligatory
        if (!isset($params['shipping_rate_id'])) {
            throw new waException($plugin->_w("Empty shipping rate id"));
        }

        $code = str_replace('_', ' ', $params['shipping_rate_id']);
        $this->service = uspsServices::getServiceByCode($code);
        if (!$this->service
                || !in_array($code, $this->getAvailableServices()))
        {
            throw new waException($plugin->_w("Unsupported service: ") . $code .
                $plugin->_w(". Supported services by this API are: ").
                    implode(", ", $this->getAvailableServices())
            );
        }

        $params['weight'] = 16.0 * max(0.1, $params['weight']);
        parent::__construct($plugin, $params);
    }

    public function getAvailableServices()
    {
        return array(
            'Priority',
            'First Class',
            'Standard Post',
            'Media',
            'Library'
        );
    }

    protected function getUrl()
    {
        return 'https://secure.shippingapis.com/ShippingAPI.dll?API=DeliveryConfirmationV4';
    }

    /**
     * @see uspsQuery::prepareRequest()
     */
    protected function prepareRequest()
    {
        $xml = new SimpleXMLElement('<DeliveryConfirmationV4.0Request/>');
        $xml->addAttribute('USERID', $this->plugin->user_id);
        $this->addSenderInfo($xml);
        $this->addRecipientInfo($xml);
        $xml->addChild('WeightInOunces', $this->params['weight']);
        $xml->addChild('ServiceType', $this->service['name']);
        $xml->addChild('InsuredAmount', $this->params['price']);
        $xml->addChild('SeparateReceiptPage');
        $xml->addChild('ImageType', 'PDF');

        //parent::dumpXml($xml->saveXML());
        return $xml->saveXML();
    }

    private function addSenderInfo(&$xml)
    {
        $xml->addChild('FromFirm', 'TestFirm');
        $xml->addChild('FromAddress1');
        $xml->addChild('FromAddress2', 'From Addres 10');
        $xml->addChild('FromCity', 'New Your');
        $xml->addChild('FromState', 'ST');

        $zip = $this->parseZip('01234');
        $xml->addChild('FromZip5', $zip['zip5']);
        $xml->addChild('FromZip4', $zip['zip4']);
    }

    private function addRecipientInfo(&$xml)
    {
        $xml->addChild('ToName', $this->getAddress('name'));
        $xml->addChild('ToFirm');
        $xml->addChild('ToAddress1');
        $xml->addChild('ToAddress2', $this->getAddress('address'));
        $xml->addChild('ToCity', ucfirst($this->getAddress('city')));
        $xml->addChild('ToState', strtoupper($this->getAddress('region')));
        $zip = $this->parseZip($this->getAddress('zip'));
        $xml->addChild('ToZip5', $zip['zip5']);
        $xml->addChild('ToZip4', $zip['zip4']);
    }

    /**
     * Taking into account zip+4 format
     */
    private function parseZip($zip)
    {
        // tacking into account zip+4 format
        $zip = array('zip5' => $zip);
        $zip['zip5'] = trim($zip['zip5']);
        if (preg_match('/(\d{5})([-\s]*(\d{4}))*/', $zip['zip5'], $m)) {
            $zip['zip5'] = $m[1];
            $zip['zip4'] = isset($m[3]) ? $m[3] : null;
        } else {
            $zip['zip4'] = null;
        }
        return $zip;
    }

    /**
     * @see uspsQuery::parseResponse()
     */
    protected function parseResponse($response)
    {
        //return $response;
    }
}