<?php

/**
 * Query implements USPS Tracking™ of Print Shipping Labels API
 *
 * Generate USPS Tracking barcoded labels for
 * Priority Mail®, First-Class Mail® parcels, and package services parcels,
 * including Standard Post™, Media Mail®, and Library Mail.
 */
class uspsLabelsUspsTrackingQuery extends uspsLabelsQuery
{
    protected static $services = array(
        'Priority',
        'First Class',
        'Standard Post',
        'Media',
        'Library'
    );
    /**
     * @see uspsLabelsQuery::getSupportedServices()
     */
    public function getSupportedServices()
    {
        return self::$services;
    }

    /**
     * @param $service
     * @return bool
     */
    public static function isSupportedService($service)
    {
        return in_array($service, self::$services);
    }

    protected function getAPIName()
    {
        return !$this->plugin->test_mode ? 'DeliveryConfirmationV4' : 'DelivConfirmCertifyV4';
    }

    /**
     * @see uspsQuery::prepareRequest()
     */
    protected function prepareRequest()
    {
        $xml = new SimpleXMLElement("<{$this->getAPIName()}.0Request/>");
        $xml->addAttribute('USERID', $this->plugin->user_id);
        $xml->addChild('Option');
        $xml->addChild('Revision', 2);

        $this->addSenderInfo($xml);
        $this->addRecipientInfo($xml);

        $xml->addChild('WeightInOunces', $this->getWeight('ounces'));
        $xml->addChild('ServiceType',    $this->service['name']);
        //$xml->addChild('InsuredAmount',  $this->getPrice());
        $xml->addChild('SeparateReceiptPage');
        $xml->addChild('POZipCode', $this->plugin->po_zip);
        $xml->addChild('ImageType', 'PDF');
        $xml->addChild('CustomerRefNo', $this->params['order_id']);
        $xml->addChild('Size', 'REGULAR');

        // cut out xml header
        $xml = preg_replace("!^<\?xml .*?\?>!", "", $xml->saveXML());
        return $xml;
    }

    /**
     * @see uspsLabelsQuery::getConfirmationNumberTagName()
     */
    protected function getConfirmationNumberTagName()
    {
        return 'DeliveryConfirmationNumber';
    }

    /**
     * @see uspsLabelsQuery::getLabelTagName()
     */
    protected function getLabelTagName()
    {
        return 'DeliveryConfirmationLabel';
    }
}
