<?php

/**
 * Query implements Express Mail® Labels of Print Shipping Labels API
 *
 * Generate a single-ply Express Mail shipping label complete
 * with return and delivery addresses, a barcode, and a mailing record for your use.
 */
class uspsLabelsExpressMailQuery extends uspsLabelsQuery
{
    protected static $services = array('Express');

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
        return !$this->plugin->test_mode ? 'ExpressMailLabel' : 'ExpressMailLabelCertify';
    }

    /**
     * @see uspsQuery::prepareRequest()
     */
    protected function prepareRequest()
    {
        $xml = new SimpleXMLElement("<{$this->getAPIName()}Request/>");
        $xml->addAttribute('USERID', $this->plugin->user_id);
        $xml->addChild('Option');
        $xml->addChild('Revision', 2);
        $xml->addChild('EMCAAccount');
        $xml->addChild('EMCAPassword');
        $xml->addChild('ImageParameters');
        $this->addSenderInfo($xml);
        $this->addRecipientInfo($xml);
        $xml->addChild('WeightInOunces', $this->getWeight('ounces'));
        $xml->addChild('FlatRate');
        $xml->addChild('SundayHolidayDelivery');
        $xml->addChild('StandardizeAddress');
        $xml->addChild('WaiverOfSignature');
        $xml->addChild('NoHoliday');
        $xml->addChild('NoWeekend');
        $xml->addChild('SeparateReceiptPage');
        $xml->addChild('POZipCode', $this->plugin->po_zip);
        $xml->addChild('ImageType', 'PDF');

        $xml->addChild('CustomerRefNo', $this->params['order_id']);
        $xml->addChild('SenderName');
        $xml->addChild('SenderEMail');
        $xml->addChild('RecipientName');
        $xml->addChild('RecipientEMail');
        $xml->addChild('HoldForManifest');
        $xml->addChild('CommercialPrice', false);
        $xml->addChild('InsuredAmount');
        $xml->addChild('Container');
        $xml->addChild('Size', 'REGULAR');
        $xml->addChild('Width');
        $xml->addChild('Length');
        $xml->addChild('Height');
        $xml->addChild('Girth');

        // cut out xml header
        $xml = preg_replace("!^<\?xml .*?\?>!", "", $xml->saveXML());

        return $xml;
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function addSenderInfo(&$xml)
    {
        $p = $this->plugin;
        $xml->addChild('FromFirstName');
        $xml->addChild('FromLastName');
        $xml->addChild('FromFirm', $p->name);
        $xml->addChild('FromAddress1');
        $xml->addChild('FromAddress2', $p->address);
        $xml->addChild('FromCity',  $p->region_zone['city']);
        $xml->addChild('FromState', $p->region_zone['region']);

        $zip = $this->parseZip($p->zip);
        $xml->addChild('FromZip5', $zip['zip5']);
        $xml->addChild('FromZip4', $zip['zip4']);
        $xml->addChild('FromPhone', $p->phone);
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function addRecipientInfo(&$xml)
    {
        $name = $this->getAddress('name');
        $xml->addChild('ToFirstName');
        $xml->addChild('ToLastName');
        $xml->addChild('ToFirm', $name ? $name : 'Unknown name');
        $xml->addChild('ToAddress1');
        $xml->addChild('ToAddress2', $this->getAddress('street'));
        $xml->addChild('ToCity', ucfirst($this->getAddress('city')));
        $xml->addChild('ToState', strtoupper($this->getAddress('region')));
        $zip = $this->parseZip($this->getAddress('zip'));
        $xml->addChild('ToZip5', $zip['zip5']);
        $xml->addChild('ToZip4', $zip['zip4']);
        $xml->addChild('ToPhone');
    }

    /**
     * @see uspsLabelsQuery::getConfirmationNumberTagName()
     */
    protected function getConfirmationNumberTagName()
    {
        return 'EMConfirmationNumber';
    }

    /**
     * @see uspsLabelsQuery::getLabelTagName()
     */
    protected function getLabelTagName()
    {
        return 'EMLabel';
    }
}
