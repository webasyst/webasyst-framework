<?php

/**
 * With the USPS's Signature Confirmation, you (or your customers) can access information on the Internet
 * about the delivery status of First-Class Mail parcels, Priority Mail and Package Services
 * (Standard Post, Media Mail, and Library Mail), including the date, time, and ZIP Code of delivery,
 * as well as attempted deliveries, forwarding, and returns.
 * Signature Confirmation service is not available to APO/FPO addresses, foreign countries, or
 * most U.S. territories.
 *
 */
class uspsLabelsSignatureConfirmationQuery extends uspsLabelsQuery
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
        return !$this->plugin->test_mode ? 'SignatureConfirmationV4' : 'SignatureConfirmationCertifyV4';
    }

    /**
     * @see uspsQuery::prepareRequest()
     */
    protected function prepareRequest()
    {
        $p = $this->plugin;
        $xml = new SimpleXMLElement(!$p->test_mode ?
            '<SignatureConfirmationV4.0Request/>' :
            '<SigConfirmCertifyV4.0Request/>'
        );
        $xml->addAttribute('USERID', $p->user_id);
        $xml->addChild('Option');
        $xml->addChild('Revision', 2);
        $xml->addChild('ImageParameters');

        $this->addSenderInfo($xml);
        $this->addRecipientInfo($xml);

        $xml->addChild('WeightInOunces', $this->getWeight('ounces'));
        $xml->addChild('ServiceType',    $this->service['name']);
        //$xml->addChild('InsuredAmount',  $this->getPrice());
        $xml->addChild('SeparateReceiptPage');
        $xml->addChild('POZipCode', $p->po_zip);
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
        return 'SignatureConfirmationNumber';
    }

    /**
     * @see uspsLabelsQuery::getLabelTagName()
     */
    protected function getLabelTagName()
    {
        return 'SignatureConfirmationLabel';
    }
}
